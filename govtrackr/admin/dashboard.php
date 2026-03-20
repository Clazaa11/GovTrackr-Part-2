<?php
require_once 'auth_guard.php';
$activePage = 'dashboard';
$pageTitle  = 'Admin Dashboard';

$total_students   = $conn->query("SELECT COUNT(*) AS n FROM users WHERE role='student'")->fetch_assoc()['n'];
$total_candidates = $conn->query("SELECT COUNT(*) AS n FROM candidates")->fetch_assoc()['n'];
$total_votes      = $conn->query("SELECT COUNT(*) AS n FROM votes")->fetch_assoc()['n'];
$total_events     = $conn->query("SELECT COUNT(*) AS n FROM events WHERE event_date >= CURDATE()")->fetch_assoc()['n'];
$election_open    = $conn->query("SELECT is_open FROM election_settings LIMIT 1")->fetch_assoc()['is_open'];

$recent_users = $conn->query("SELECT first_name, last_name, student_number, created_at FROM users WHERE role='student' ORDER BY created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovTrackr — Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="dash-body">

<?php include 'sidebar.php'; ?>

<div style="flex:1; display:flex; flex-direction:column;">
<header class="topbar">
    <div class="topbar-left">
        <p class="topbar-welcome">Welcome to <span class="brand-gov">Gov</span><span class="brand-trackr">Trackr</span></p>
        <h1 class="topbar-title">Admin Dashboard</h1>
    </div>
    <div class="topbar-right">
        <div class="user-chip">
            <span class="user-avatar-icon"></span>
            <div class="user-chip-info">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <small>HAU COMELEC</small>
            </div>
        </div>
    </div>
</header>

<main class="dash-content">

    <!-- Stats -->
    <div class="grid-4">
        <div class="stat-card"><div class="num"><?= $total_students ?></div><div class="lbl">Registered Students</div></div>
        <div class="stat-card"><div class="num"><?= $total_candidates ?></div><div class="lbl">Candidates</div></div>
        <div class="stat-card"><div class="num"><?= $total_votes ?></div><div class="lbl">Votes Cast</div></div>
        <div class="stat-card">
            <div class="num" style="font-size:1.1rem; padding-top:8px;"><?= $election_open ? 'OPEN' : 'CLOSED' ?></div>
            <div class="lbl">Mock Election</div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="grid-2" style="align-items:start;">
        <div class="card">
            <h3 class="card-title">Quick Actions</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <a href="candidates.php?action=add" class="btn btn-purple">Add Candidate</a>
                <a href="partylists.php?action=add" class="btn btn-purple">Add Partylist</a>
                <a href="events.php?action=add"     class="btn btn-purple">Add Event</a>
                <a href="election.php"              class="btn btn-gold">Election Control</a>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Recent Registrations</h3>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Student #</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php while ($r = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
                            <td><?= htmlspecialchars($r['student_number']) ?></td>
                            <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>
</div>
</body>
</html>
