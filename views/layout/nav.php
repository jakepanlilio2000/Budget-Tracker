<?php 
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$pid = $profile['id'] ?? ($profile_id ?? 0); 
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <div class="profile-badge" style="background: <?= htmlspecialchars($profile['color'] ?? '#4F7BF7') ?>"></div>
        <h2><?= htmlspecialchars($profile['name'] ?? 'Tracker') ?></h2>
        <button id="mobile-nav-toggle" class="icon-btn ghost mobile-only">☰</button>
    </div>
    <ul class="nav-links">
        <li><a href="<?= $basePath ?>/" class="nav-item">🏠 Profiles</a></li>
        <?php if ($pid): ?>
        <li><a href="<?= $basePath ?>/dashboard/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : '' ?>">📊 Dashboard</a></li>
        <li><a href="<?= $basePath ?>/entries/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/entries') !== false ? 'active' : '' ?>">📝 Entries</a></li>
        <li><a href="<?= $basePath ?>/categories/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/categories') !== false ? 'active' : '' ?>">🏷 Categories</a></li>
        <li><a href="<?= $basePath ?>/calculator/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/calculator') !== false ? 'active' : '' ?>">🧮 Calculator</a></li>
        <li><a href="<?= $basePath ?>/profile/<?= $pid ?>/edit" class="nav-item">⚙ Settings</a></li>
        <?php endif; ?>
    </ul>
</nav>
<main class="main-content">