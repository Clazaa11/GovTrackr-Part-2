<?php
// includes/sidebar.php
// Usage: include 'includes/sidebar.php';
// Requires $activePage variable set before including, e.g. $activePage = 'dashboard';

$pages = [
    'dashboard'    => ['label' => 'Dashboard',      'href' => '/govtrackr/dashboard.php'],
    'candidates'   => ['label' => 'Candidates',      'href' => '/govtrackr/candidates.php'],
    'partylists'   => ['label' => 'Partylists',      'href' => '/govtrackr/partylists.php'],
    'mock-election'=> ['label' => 'Mock Election',   'href' => '/govtrackr/mock_election.php'],
    'profile'      => ['label' => 'Profile',         'href' => '/govtrackr/profile.php'],
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
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($pages as $key => $p): ?>
        <a href="<?= $p['href'] ?>"
           class="nav-item <?= $active === $key ? 'active' : '' ?>"
           title="<?= $p['label'] ?>">
            <span class="nav-label"><?= $p['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <a href="/govtrackr/logout.php" class="nav-item nav-logout" title="Logout">
        <span class="nav-label">Logout</span>
    </a>
</aside>
