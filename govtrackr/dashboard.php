<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit();
}


$activePage = 'dashboard';
$pageTitle  = 'Dashboard';

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}
$conn = getDatabaseConnection();

// Recent candidates 
$recent_cands = $conn->query("
    SELECT c.id, c.full_name, c.position, c.photo, p.name AS partylist
    FROM   candidates c
    LEFT JOIN partylists p ON c.partylist_id = p.id
    ORDER BY c.created_at DESC
    LIMIT 6
");

// Upcoming events
$events = $conn->query("
    SELECT title, description, event_date, event_time, location
    FROM   events
    WHERE  event_date >= CURDATE()
    ORDER  BY event_date ASC
    LIMIT  5
");

// Nearest HAULALAN event date
$haulalan = $conn->query("SELECT event_date FROM events WHERE LOWER(title) LIKE '%haulalan%' OR LOWER(title) LIKE '%election day%' ORDER BY event_date ASC LIMIT 1")->fetch_assoc();

// Checks if user already voted 
$voted = $conn->query("SELECT COUNT(*) AS n FROM votes WHERE voter_id = {$_SESSION['user_id']}")->fetch_assoc()['n'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovTrackr — Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="dash-body">

<?php include 'includes/sidebar.php'; ?>

<div style="flex:1; display:flex; flex-direction:column;">
    <?php include 'includes/topbar.php'; ?>

    <main class="dash-content">

        <!-- ── Stats row ── -->
        <div class="grid-4">
            <?php
            $total_cands  = $conn->query("SELECT COUNT(*) AS n FROM candidates")->fetch_assoc()['n'];
            $total_pl     = $conn->query("SELECT COUNT(*) AS n FROM partylists")->fetch_assoc()['n'];
            $total_events = $conn->query("SELECT COUNT(*) AS n FROM events WHERE event_date >= CURDATE()")->fetch_assoc()['n'];
            $election_open = $conn->query("SELECT is_open FROM election_settings LIMIT 1")->fetch_assoc()['is_open'];
            ?>
            <div class="stat-card">
                <div class="num"><?= $total_cands ?></div>
                <div class="lbl">Candidates</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $total_pl ?></div>
                <div class="lbl">Partylists</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $total_events ?></div>
                <div class="lbl">Upcoming Events</div>
            </div>
            <div class="stat-card">
                <div class="num" style="font-size:1.2rem; padding-top:6px"><?= $election_open ? 'OPEN' : 'CLOSED' ?></div>
                <div class="lbl">Mock Election</div>
            </div>
        </div>

        <!-- ── HAULALAN countdown banner ── -->
        <?php if ($haulalan): ?>
        <div class="card card-gold" style="display:flex; align-items:center; justify-content:space-between; padding:18px 26px;">
            <div>
                <p style="font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#856404;">Next Election Day</p>
                <p style="font-size:1.5rem; font-weight:900; color:var(--purple-dark);">
                     <?= date('F j, Y', strtotime($haulalan['event_date'])) ?>
                </p>
            </div>
            <?php
            $today = new DateTime();
            $eday  = new DateTime($haulalan['event_date']);
            $diff  = $today->diff($eday)->days;
            ?>
            <div style="text-align:center;">
                <div style="font-size:2.4rem; font-weight:900; color:var(--purple-dark);"><?= $diff ?></div>
                <div style="font-size:.8rem; color:#856404; font-weight:700;">days away</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Main grid ── -->
        <div class="grid-dash">

            <!-- Recent candidates -->
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 class="card-title" style="margin:0">Recently Filed Candidates</h3>
                    <a href="candidates.php" class="btn btn-outline btn-sm">View All →</a>
                </div>
                <div class="mini-cand-row">
                    <?php while ($c = $recent_cands->fetch_assoc()): ?>
                    <a href="candidates.php?id=<?= $c['id'] ?>" class="mini-cand">
                        <?php if ($c['photo']): ?>
                            <img src="<?= htmlspecialchars($c['photo']) ?>" alt="<?= htmlspecialchars($c['full_name']) ?>" class="mc-photo">
                        <?php else: ?>
                            <div class="mc-photo"></div>
                        <?php endif; ?>
                        <span class="mc-name"><?= htmlspecialchars($c['full_name']) ?></span>
                    </a>
                    <?php endwhile; ?>
                </div>

                <!-- Quick candidate table -->
                <div style="margin-top:20px; overflow-x:auto;">
                    <?php
                    $tbl = $conn->query("
                        SELECT c.full_name, c.position, p.name AS partylist
                        FROM   candidates c
                        LEFT JOIN partylists p ON c.partylist_id = p.id
                        ORDER BY c.created_at DESC LIMIT 6
                    ");
                    ?>
                    <table class="data-table">
                        <thead><tr><th>Candidate</th><th>Position</th><th>Partylist</th></tr></thead>
                        <tbody>
                            <?php while ($r = $tbl->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['full_name']) ?></td>
                                <td><?= htmlspecialchars($r['position']) ?></td>
                                <td><?= htmlspecialchars($r['partylist'] ?? '—') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right column -->
            <div style="display:flex; flex-direction:column; gap:20px;">

                <!-- Reminders -->
                <div class="reminders-box">
                    <h3>Reminders</h3>
                    <p class="sub">Things to bring on election day:</p>
                    <ul>
                        <li>Valid ID (Reg)</li>
                        <li>Precinct Information</li>
                        <li>Black Ballpen (sometimes provided)</li>
                        <li>Small alcohol / hand sanitizer</li>
                    </ul>
                    <span class="reminders-exclamation">!</span>
                </div>

                <!-- Upcoming events -->
                <div class="card card-dark">
                    <h3 class="card-title"> Calendar of Events</h3>
                    <?php while ($ev = $events->fetch_assoc()): ?>
                    <div class="event-item">
                        <div class="event-date-box">
                            <div class="day"><?=   date('j',   strtotime($ev['event_date'])) ?></div>
                            <div class="month"><?= date('M',   strtotime($ev['event_date'])) ?></div>
                        </div>
                        <div class="event-info">
                            <div class="etitle"><?= htmlspecialchars($ev['title']) ?></div>
                            <?php if ($ev['location']): ?>
                            <div class="eloc"> <?= htmlspecialchars($ev['location']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <a href="mock_election.php" class="btn btn-gold btn-full" style="margin-top:16px">
                        Go to Mock Election
                    </a>
                </div>

            </div>
        </div>

    </main>
</div>

</body>
</html>