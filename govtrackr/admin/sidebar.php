<?php
// admin/sidebar.php
$admin_pages = [
    'dashboard'  => ['label' => 'Dashboard',    'href' => '/govtrackr/admin/dashboard.php',  'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>'],
    'candidates' => ['label' => 'Candidates',   'href' => '/govtrackr/admin/candidates.php', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
    'partylists' => ['label' => 'Partylists',   'href' => '/govtrackr/admin/partylists.php', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>'],
    'events'     => ['label' => 'Events',        'href' => '/govtrackr/admin/events.php',     'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'],
    'election'   => ['label' => 'Election Ctrl','href' => '/govtrackr/admin/election.php',   'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>'],
    'users'      => ['label' => 'Students',      'href' => '/govtrackr/admin/users.php',      'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
];
$active = $activePage ?? '';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kodchasan:wght@400;600;700&display=swap" rel="stylesheet">

<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-gov">G</span><span class="brand-trackr">T</span>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($admin_pages as $key => $p): ?>
        <a href="<?= $p['href'] ?>"
           class="nav-item <?= $active === $key ? 'active' : '' ?>"
           title="<?= $p['label'] ?>">
            <span class="nav-icon"><?= $p['icon'] ?></span>
            <span class="nav-label"><?= $p['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <a href="/govtrackr/logout.php" class="nav-item nav-logout" title="Logout">
        <span class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </span>
        <span class="nav-label">Logout</span>
    </a>
</aside>
