<?php
session_start();
require_once 'config.php';
require_once 'includes/positions.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$activePage = 'partylists';
$pageTitle  = 'Partylists';

$conn = getDatabaseConnection();

// ── SINGLE PARTYLIST DETAIL VIEW ───────────────────────────
if (isset($_GET['id'])) {
    $pl_id = (int)$_GET['id'];
    $stmt  = $conn->prepare("SELECT * FROM partylists WHERE id = ?");
    $stmt->bind_param("i", $pl_id);
    $stmt->execute();
    $pl = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$pl) { header("Location: partylists.php"); exit(); }

    // Load all candidates in this partylist, ordered by position hierarchy then college
    $field_expr = position_field_expr('c.position');
    $members_res = $conn->query("
        SELECT c.id, c.full_name, c.position, c.college, c.photo, c.year_level, c.course
        FROM   candidates c
        WHERE  c.partylist_id = $pl_id
        ORDER BY $field_expr, COALESCE(c.college, ''), c.full_name
    ");
    $members = [];
    while ($r = $members_res->fetch_assoc()) { $members[] = $r; }
}

// ── LIST VIEW — all partylists ──────────────────────────────
$partylists = $conn->query("
    SELECT p.*, COUNT(c.id) AS member_count
    FROM   partylists p
    LEFT JOIN candidates c ON c.partylist_id = p.id
    GROUP BY p.id
    ORDER BY p.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovTrackr — Partylists</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="dash-body">

<?php include 'includes/sidebar.php'; ?>

<div style="flex:1; display:flex; flex-direction:column;">
    <?php include 'includes/topbar.php'; ?>

    <main class="dash-content">

    <?php if (isset($pl)): ?>
    <!-- ── DETAIL VIEW ── -->
    <a href="partylists.php" class="btn btn-outline btn-sm" style="align-self:flex-start;">← Back to Partylists</a>

    <!-- Header card -->
    <div class="card" style="background:var(--purple-dark); color:#fff; padding:32px; border-bottom:4px solid var(--gold);">
        <div style="display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
            <?php if ($pl['logo']): ?>
                <img src="<?= htmlspecialchars($pl['logo']) ?>" alt=""
                     style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--gold);flex-shrink:0;">
            <?php else: ?>
                <div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:2.5rem;border:3px solid var(--gold);flex-shrink:0;">🏛️</div>
            <?php endif; ?>
            <div style="flex:1;">
                <h2 style="font-size:1.8rem; font-weight:900; margin-bottom:6px;"><?= htmlspecialchars($pl['name']) ?></h2>
                <?php if ($pl['description']): ?>
                <p style="opacity:.85; font-size:.95rem; line-height:1.7;"><?= nl2br(htmlspecialchars($pl['description'])) ?></p>
                <?php endif; ?>
                <p style="margin-top:10px; font-size:.82rem; opacity:.65;">
                    <?= count($members) ?> candidate<?= count($members) !== 1 ? 's' : '' ?>
                </p>
            </div>
        </div>
    </div>

    <?php if (empty($members)): ?>
    <div class="card" style="text-align:center; padding:40px; color:var(--muted);">
        No candidates have been filed under this partylist yet.
    </div>

    <?php else:
        // Group members: University-Wide first, then per college
        $grouped = [];
        foreach ($members as $m) {
            $key = $m['college'] ?: 'University-Wide';
            $grouped[$key][] = $m;
        }

        // Sort groups: University-Wide first, colleges alphabetically
        uksort($grouped, function($a, $b) {
            if ($a === 'University-Wide') return -1;
            if ($b === 'University-Wide') return  1;
            return strcmp($a, $b);
        });
    ?>

    <?php foreach ($grouped as $group_name => $group_members): ?>
    <div>
        <h3 style="font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
                   color:var(--purple-light); padding-bottom:8px; border-bottom:2px solid var(--gold);
                   margin-bottom:16px;">
            <?= htmlspecialchars($group_name) ?>
        </h3>
        <div class="candidate-grid">
            <?php foreach ($group_members as $m): ?>
            <a href="candidates.php?id=<?= $m['id'] ?>" class="candidate-card">
                <?php if ($m['photo']): ?>
                    <img src="<?= htmlspecialchars($m['photo']) ?>" alt="" class="photo">
                <?php else: ?>
                    <div class="photo">👤</div>
                <?php endif; ?>
                <div class="info">
                    <div class="name"><?= htmlspecialchars($m['full_name']) ?></div>
                    <div class="position"><?= htmlspecialchars($m['position']) ?></div>
                    <?php if ($m['year_level'] || $m['course']): ?>
                    <div style="font-size:.75rem; color:var(--muted); margin-top:2px;">
                        <?= htmlspecialchars(trim(($m['year_level'] ? $m['year_level'] . ' · ' : '') . ($m['course'] ?? ''))) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php else: ?>
    <!-- ── LIST VIEW ── -->
    <?php if ($partylists->num_rows === 0): ?>
    <div class="card" style="text-align:center; padding:48px; color:var(--muted);">
        No partylists have been added yet.
    </div>
    <?php else: ?>
    <div class="grid-2">
        <?php while ($item = $partylists->fetch_assoc()): ?>

        <?php
        // Load a quick preview of members for this card
        $preview = $conn->query("
            SELECT c.id, c.full_name, c.position, c.photo
            FROM   candidates c
            WHERE  c.partylist_id = {$item['id']}
            ORDER BY " . position_field_expr('c.position') . ", c.full_name
            LIMIT 4
        ");
        ?>

        <a href="partylists.php?id=<?= $item['id'] ?>" class="partylist-card"
           style="text-decoration:none; cursor:pointer; transition:box-shadow .2s;"
           onmouseover="this.style.boxShadow='0 8px 32px rgba(80,0,39,.18)'"
           onmouseout="this.style.boxShadow=''">

            <div class="pl-header">
                <?php if ($item['logo']): ?>
                    <img src="<?= htmlspecialchars($item['logo']) ?>" alt="" class="pl-logo">
                <?php else: ?>
                    <div class="pl-logo" style="font-size:1.8rem;">🏛️</div>
                <?php endif; ?>
                <div>
                    <div class="pl-name"><?= htmlspecialchars($item['name']) ?></div>
                    <?php if ($item['description']): ?>
                    <div class="pl-desc"><?= htmlspecialchars(mb_strimwidth($item['description'], 0, 90, '…')) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pl-body">
                <p class="pl-members-count">
                    👥 <?= $item['member_count'] ?> candidate<?= $item['member_count'] !== 1 ? 's' : '' ?>
                    &nbsp;·&nbsp; <span style="color:var(--purple-mid); font-size:.8rem; font-weight:600;">View all →</span>
                </p>

                <?php if ($preview->num_rows > 0): ?>
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <?php while ($m = $preview->fetch_assoc()): ?>
                    <div class="mini-cand" style="pointer-events:none;">
                        <?php if ($m['photo']): ?>
                            <img src="<?= htmlspecialchars($m['photo']) ?>" alt="" class="mc-photo">
                        <?php else: ?>
                            <div class="mc-photo">👤</div>
                        <?php endif; ?>
                        <span class="mc-name"><?= htmlspecialchars($m['full_name']) ?></span>
                    </div>
                    <?php endwhile; ?>
                    <?php if ($item['member_count'] > 4): ?>
                    <div class="mini-cand" style="pointer-events:none;">
                        <div class="mc-photo" style="background:var(--purple-soft); font-size:.85rem; font-weight:700; color:var(--purple-dark);">
                            +<?= $item['member_count'] - 4 ?>
                        </div>
                        <span class="mc-name">more</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <p style="color:var(--muted); font-size:.88rem;">No candidates yet.</p>
                <?php endif; ?>
            </div>
        </a>

        <?php endwhile; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    </main>
</div>

</body>
</html>
