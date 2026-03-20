<?php
$pageTitle = $pageTitle ?? 'GovTrackr';
$userName  = $_SESSION['user_name'] ?? 'Student';
$userId    = $_SESSION['student_number'] ?? '';
$userPic   = $_SESSION['profile_pic'] ?? '';
// Get initials for avatar fallback
$parts    = explode(' ', trim($userName));
$initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
?>
<header class="topbar">
    <div class="topbar-left">
        <p class="topbar-welcome">Welcome to <span class="brand-gov">Gov</span><span class="brand-trackr">Trackr</span></p>
        <h1 class="topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>
    <div class="topbar-right">
        <form method="GET" action="/govtrackr/candidates.php" class="search-form">
            <input type="text" name="search" placeholder="Search candidates..."
                   class="search-input" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </form>
        <a href="/govtrackr/profile.php" class="user-chip">
            <?php if ($userPic): ?>
                <img src="<?= htmlspecialchars($userPic) ?>" alt="avatar" class="user-avatar-img">
            <?php else: ?>
                <div class="user-avatar-icon"><?= $initials ?></div>
            <?php endif; ?>
            <div class="user-chip-info">
                <strong><?= htmlspecialchars($userName) ?></strong>
                <small><?= htmlspecialchars($userId) ?></small>
            </div>
        </a>
    </div>
</header>
