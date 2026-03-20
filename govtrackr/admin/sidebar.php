<?php
$admin_pages = [
    'dashboard'  => ['label' => 'Dashboard',    'href' => '/govtrackr/admin/dashboard.php'],
    'candidates' => ['label' => 'Candidates',    'href' => '/govtrackr/admin/candidates.php'],
    'partylists' => ['label' => 'Partylists',    'href' => '/govtrackr/admin/partylists.php'],
    'events'     => ['label' => 'Events',        'href' => '/govtrackr/admin/events.php'],
    'election'   => ['label' => 'Election Ctrl', 'href' => '/govtrackr/admin/election.php'],
    'users'      => ['label' => 'Students',      'href' => '/govtrackr/admin/users.php'],
];
$active = $activePage ?? '';
?>
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Kodchasan:wght@400;600&display=swap" rel="stylesheet">
</head>
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-gov">Gov</span><span class="brand-trackr">Trackr</span>
        <div style="font-size:.65rem; color:var(--gold); margin-top:2px; opacity:.8;">ADMIN</div>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($admin_pages as $key => $p): ?>
        <a href="<?= $p['href'] ?>"
           class="nav-item <?= $active === $key ? 'active' : '' ?>"
           title="<?= $p['label'] ?>">
            <span class="nav-label"><?= $p['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <a href="/govtrackr/logout.php" class="nav-item nav-logout">
        <span class="nav-label">Logout</span>
    </a>
</aside>
