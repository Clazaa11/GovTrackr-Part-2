<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit();
}

$user_id    = $_SESSION['user_id'];
$activePage = 'mock-election';
$pageTitle  = 'Mock Election';
$message    = '';
$msg_type   = '';

$conn = getDatabaseConnection();

// ── DETERMINE ELECTION STATUS (AUTOMATIC) ──────────────────
$settings = $conn->query("SELECT * FROM election_settings LIMIT 1")->fetch_assoc();
$pht = new DateTimeZone('Asia/Manila');
$now = new DateTime('now', $pht);

if (!empty($settings['start_date']) && !empty($settings['end_date'])) {
    // Automatic mode: derive open/closed from the scheduled window
    $start_dt = new DateTime($settings['start_date'], $pht);
    $end_dt   = new DateTime($settings['end_date'], $pht);
    $is_open  = ($now >= $start_dt && $now <= $end_dt);

    // Keep is_open column in sync so admin panel reflects reality
    $auto_state = $is_open ? 1 : 0;
    if ((int)$settings['is_open'] !== $auto_state) {
        $conn->query("UPDATE election_settings SET is_open=$auto_state WHERE id=1");
    }
} else {
    // Manual fallback: use admin toggle if no dates are configured
    $is_open = (bool)($settings['is_open'] ?? false);
    $start_dt = null;
    $end_dt   = null;
}

// Check if user already voted
$already_voted = $conn->query("SELECT COUNT(*) AS n FROM votes WHERE voter_id = $user_id")->fetch_assoc()['n'] > 0;

// ── HANDLE LINEUP SAVE ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lineup'])) {
    $conn->query("DELETE FROM lineups WHERE user_id = $user_id");
    if (!empty($_POST['picks'])) {
        $ins = $conn->prepare("INSERT INTO lineups (user_id, candidate_id, position, college) VALUES (?,?,?,?)");
        foreach ($_POST['picks'] as $slot_key => $cand_id) {
            $cand_id = (int)$cand_id;
            // slot_key format: "position||college" (college may be empty for university-wide)
            [$position, $college] = array_pad(explode('||', $slot_key, 2), 2, null);
            $college  = $college ?: null;
            $position = $conn->real_escape_string($position);
            $col_safe = $college ? $conn->real_escape_string($college) : null;
            $chk_sql  = "SELECT id FROM candidates WHERE id=$cand_id AND position='$position'"
                      . ($col_safe ? " AND college='$col_safe'" : " AND (college IS NULL OR college='')");
            $chk = $conn->query($chk_sql);
            if ($chk->num_rows > 0) {
                $ins->bind_param("iiss", $user_id, $cand_id, $position, $college);
                $ins->execute();
            }
        }
        $ins->close();
        $message  = "Lineup saved! Review your choices below before casting your vote.";
        $msg_type = 'success';
    }
}

// ── HANDLE FINAL VOTE CAST ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cast_vote']) && $is_open && !$already_voted) {
    $lineup = $conn->query("SELECT candidate_id, position, college FROM lineups WHERE user_id = $user_id");
    if ($lineup->num_rows === 0) {
        $message  = "Please save your lineup first before casting your vote.";
        $msg_type = 'error';
    } else {
        $ins = $conn->prepare("INSERT IGNORE INTO votes (voter_id, candidate_id, position, college) VALUES (?,?,?,?)");
        while ($lrow = $lineup->fetch_assoc()) {
            $ins->bind_param("iiss", $user_id, $lrow['candidate_id'], $lrow['position'], $lrow['college']);
            $ins->execute();
        }
        $ins->close();
        $already_voted = true;
        $message  = "Your vote has been cast! Thank you for participating.";
        $msg_type = 'success';
    }
}

// ── LOAD POSITIONS & CANDIDATES ────────────────────────────
require_once 'includes/positions.php';

// Get the logged-in student's college (stored as abbreviation e.g. 'SOC')
$student_row    = $conn->query("SELECT college FROM users WHERE id = $user_id")->fetch_assoc();
$student_abbr   = $student_row['college'] ?? null;                    // e.g. 'SOC'
$student_college = $student_abbr ? college_full($student_abbr) : null; // e.g. 'School of Computing'

// Load positions:
//   - University-wide (college IS NULL): always shown
//   - Per-college: only show the student's own college
$field_expr = position_field_expr('c.position');

if ($student_college) {
    $safe_col = $conn->real_escape_string($student_college);
    $col_filter = "AND (c.college IS NULL OR c.college = '$safe_col')";
} else {
    // No college set on account — show only university-wide
    $col_filter = "AND c.college IS NULL";
}

$positions_res = $conn->query("
    SELECT DISTINCT c.position, c.college
    FROM   candidates c
    WHERE  1=1 $col_filter
    ORDER BY $field_expr, COALESCE(c.college, ''), c.position
");
// Build: [ 'President' => [null], 'Governor' => ['School of Computing'], ... ]
$positions_map = [];
while ($r = $positions_res->fetch_assoc()) {
    $positions_map[$r['position']][] = $r['college'];
}

// Load user's saved lineup
$saved_lineup = [];
$sl = $conn->query("SELECT candidate_id, position, college FROM lineups WHERE user_id = $user_id");
while ($r = $sl->fetch_assoc()) {
    $key = $r['position'] . '||' . ($r['college'] ?? '');
    $saved_lineup[$key] = (int)$r['candidate_id'];
}

// Load votes cast (for confirmation screen)
$cast_votes = [];
if ($already_voted) {
    $cv = $conn->query("
        SELECT v.position, v.college, c.full_name, c.photo, p.name AS partylist
        FROM   votes v
        JOIN   candidates c ON v.candidate_id = c.id
        LEFT JOIN partylists p ON c.partylist_id = p.id
        WHERE  v.voter_id = $user_id
    ");
    while ($r = $cv->fetch_assoc()) {
        $key = $r['position'] . '||' . ($r['college'] ?? '');
        $cast_votes[$key] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovTrackr — Mock Election</title>
    <link rel="stylesheet" href="css/styles.css">
    <?php if ($is_open && !$already_voted && $end_dt): ?>
    <!-- Auto-refresh every 60s so the page closes itself when the window ends -->
    <meta http-equiv="refresh" content="60">
    <?php elseif (!$is_open && $start_dt && $now < $start_dt): ?>
    <!-- Refresh every 30s so the page opens itself when the window starts -->
    <meta http-equiv="refresh" content="30">
    <?php endif; ?>
</head>
<body class="dash-body">

<?php include 'includes/sidebar.php'; ?>

<div style="flex:1; display:flex; flex-direction:column;">
    <?php include 'includes/topbar.php'; ?>

    <main class="dash-content">

        <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Election status banner -->
        <?php if (!$is_open): ?>
        <div class="card card-gold" style="text-align:center; padding:28px;">
            <h2 style="color:var(--purple-dark); margin:8px 0 6px;">Election is Currently Closed</h2>
            <?php if ($start_dt && $now < $start_dt): ?>
            <p style="color:#856404; font-size:.92rem;">
                Voting opens automatically when the scheduled time is reached. You can still build your lineup now.
            </p>
            <p style="margin-top:10px; font-weight:700; color:var(--purple-dark);">
                Opens: <?= $start_dt->format('F j, Y · g:i A') ?>
            </p>
            <?php elseif ($end_dt && $now > $end_dt): ?>
            <p style="color:#856404; font-size:.92rem;">
                The voting window has ended.
            </p>
            <p style="margin-top:10px; font-weight:700; color:var(--purple-dark);">
                Closed: <?= $end_dt->format('F j, Y · g:i A') ?>
            </p>
            <?php else: ?>
            <p style="color:#856404; font-size:.92rem;">
                The mock election is not open yet. You can still build your lineup and it will be ready when voting opens.
            </p>
            <?php if ($settings['start_date']): ?>
            <p style="margin-top:10px; font-weight:700; color:var(--purple-dark);">
                Opens: <?= date('F j, Y · g:i A', strtotime($settings['start_date'])) ?>
            </p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php elseif ($is_open && !$already_voted && $end_dt): ?>
        <!-- Countdown banner while voting is open -->
        <div class="card" style="background:var(--purple-dark); color:#fff; text-align:center; padding:14px;">
            <p style="font-size:.88rem; color:var(--gold); font-weight:700;">
                🗳️ Election is OPEN &nbsp;·&nbsp; Closes: <?= $end_dt->format('F j, Y · g:i A') ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if ($already_voted): ?>
        <!-- ── CONFIRMATION SCREEN ── -->
        <div class="card" style="text-align:center; padding:32px;">
            <h2 style="color:var(--purple-dark); margin:10px 0 6px;">You Have Already Voted!</h2>
            <p style="color:var(--muted);">Here are the candidates you voted for.</p>
        </div>
        <div class="grid-2">
            <?php foreach ($cast_votes as $slot_key => $cv): ?>
            <div class="card" style="display:flex; align-items:center; gap:16px;">
                <?php if ($cv['photo']): ?>
                    <img src="<?= htmlspecialchars($cv['photo']) ?>" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:3px solid var(--gold);">
                <?php else: ?>
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--purple-soft);display:flex;align-items:center;justify-content:center;font-size:1.6rem;border:3px solid var(--gold);">👤</div>
                <?php endif; ?>
                <div>
                    <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">
                        <?= htmlspecialchars($cv['position']) ?>
                        <?php if (!empty($cv['college'])): ?>
                        <span style="font-weight:400;opacity:.7;"> — <?= htmlspecialchars($cv['college']) ?></span>
                        <?php endif; ?>
                    </p>
                    <p style="font-weight:700;"><?= htmlspecialchars($cv['full_name']) ?></p>
                    <?php if ($cv['partylist']): ?>
                    <span class="party-badge"><?= htmlspecialchars($cv['partylist']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- College scope notice -->
        <?php if ($student_college): ?>
        <div class="card" style="padding:14px 20px; display:flex; align-items:center; gap:14px; border-left:4px solid var(--gold);">
            <div style="font-size:1.6rem;">🏫</div>
            <div>
                <p style="font-size:.82rem; font-weight:700; color:var(--purple-dark); margin-bottom:2px;">
                    Your Voting Scope
                </p>
                <p style="font-size:.85rem; color:var(--muted); line-height:1.5;">
                    You are voting as a student of <strong style="color:var(--text);"><?= htmlspecialchars($student_college) ?></strong>
                    <span class="badge badge-gold" style="margin-left:6px;"><?= htmlspecialchars($student_abbr) ?></span>.
                    You can vote for <strong>University-Wide</strong> positions and
                    <strong><?= htmlspecialchars($student_college) ?></strong> positions only.
                    You can still view all candidates and partylists from the Candidates and Partylists pages.
                </p>
            </div>
        </div>
        <?php else: ?>
        <div class="card" style="padding:14px 20px; border-left:4px solid var(--danger);">
            <p style="font-size:.85rem; color:var(--danger);">
                ⚠️ Your account does not have a college assigned. Please contact HAU COMELEC to update your profile.
                You can only vote for University-Wide positions at this time.
            </p>
        </div>
        <?php endif; ?>

        <!-- ── LINEUP + VOTING FORM ── -->
        <form method="POST" action="">
            <?php foreach ($positions_map as $pos => $colleges): ?>
            <div class="position-section">
                <!-- One big position header for all colleges -->
                <div class="position-label"><?= htmlspecialchars($pos) ?></div>

                <?php foreach ($colleges as $col): ?>
                <?php
                $col_safe = $col ? $conn->real_escape_string($col) : null;
                $slot_key = $pos . '||' . ($col ?? '');

                $where_col = $col_safe
                    ? "AND c.college = '$col_safe'"
                    : "AND (c.college IS NULL OR c.college = '')";

                $cands_for_slot = $conn->query("
                    SELECT c.id, c.full_name, c.photo, c.college, p.name AS partylist
                    FROM   candidates c
                    LEFT JOIN partylists p ON c.partylist_id = p.id
                    WHERE  c.position = '" . $conn->real_escape_string($pos) . "' $where_col
                    ORDER BY c.full_name
                ");
                $saved_pick = $saved_lineup[$slot_key] ?? 0;
                ?>

                <?php while ($cand = $cands_for_slot->fetch_assoc()): ?>
                <label>
                    <div class="vote-candidate-card <?= $saved_pick === (int)$cand['id'] ? 'picked' : '' ?>"
                         id="vc-<?= htmlspecialchars($slot_key) ?>-<?= $cand['id'] ?>">
                        <?php if ($cand['photo']): ?>
                            <img src="<?= htmlspecialchars($cand['photo']) ?>" alt="" class="vc-photo">
                        <?php else: ?>
                            <div class="vc-photo">👤</div>
                        <?php endif; ?>
                        <div style="flex:1;">
                            <div class="vc-name"><?= htmlspecialchars($cand['full_name']) ?></div>
                            <?php if ($cand['partylist']): ?>
                            <div class="vc-party"><?= htmlspecialchars($cand['partylist']) ?></div>
                            <?php endif; ?>
                            <?php if ($cand['college']): ?>
                            <div class="vc-party" style="color:var(--gold-dark); font-weight:600;">
                                <?= htmlspecialchars($cand['college']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="vc-pick-dot"></div>
                        <input type="radio"
                               name="picks[<?= htmlspecialchars($slot_key) ?>]"
                               value="<?= $cand['id'] ?>"
                               style="display:none"
                               <?= $saved_pick === (int)$cand['id'] ? 'checked' : '' ?>>
                    </div>
                </label>
                <?php endwhile; ?>
                <?php endforeach; // colleges ?>
            </div>
            <?php endforeach; // positions_map ?>

            <div style="display:flex; gap:14px; flex-wrap:wrap; margin-top:8px;">
                <button type="submit" name="save_lineup" class="btn btn-purple">
                    Save Lineup
                </button>
                <?php if ($is_open): ?>
                <button type="submit" name="cast_vote"
                        class="btn btn-gold"
                        onclick="return confirm('Are you sure? This cannot be undone!')">
                    Cast My Vote
                </button>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>

    </main>
</div>

<script>
document.querySelectorAll('.vote-candidate-card').forEach(card => {
    card.addEventListener('click', () => {
        const radio = card.querySelector('input[type=radio]');
        const name  = radio.name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
            r.closest('.vote-candidate-card').classList.remove('picked');
        });
        card.classList.add('picked');
    });
});
</script>
</body>
</html>
