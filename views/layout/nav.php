<?php 
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$pid = $profile['id'] ?? ($profile_id ?? 0); 
$isLoggedIn = isset($_SESSION['user_id']);
?>

<?php if ($isLoggedIn): ?>
<nav class="sidebar">
    <div class="sidebar-header" style="flex-direction: column; align-items: flex-start; gap: 16px; padding-bottom: 24px; border-bottom: 1px solid var(--border); width: 100%;">
        
        <?php $userName = $_SESSION['user_name'] ?? 'User'; ?>
        
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="profile-badge" style="background: <?= htmlspecialchars($profile['color'] ?? '#4F7BF7') ?>; width: 36px; height: 36px; border: 2px solid var(--bg-primary); outline: 2px solid <?= htmlspecialchars($profile['color'] ?? '#4F7BF7') ?>50; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
                    <?= strtoupper(substr($userName, 0, 1)) ?>
                </div>
                <div>
                    <?php 
                    $hour = date('H');
                    $greeting = 'Good Evening';
                    if ($hour < 12) $greeting = 'Good Morning';
                    elseif ($hour < 18) $greeting = 'Good Afternoon';
                    ?>
                    <span style="display: block; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;"><?= $greeting ?></span>
                    <h2 style="font-size: 16px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;"><?= htmlspecialchars($userName) ?></h2>
                </div>
            </div>
            <button id="mobile-nav-toggle" class="icon-btn ghost mobile-only">☰</button>
        </div>
    </div>

    <ul class="nav-links">
        <?php if (!$pid): ?>
            <li style="margin-top: 4px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">Global Portfolio</li>
            <li><a href="<?= $basePath ?>/" class="nav-item <?= $_SERVER['REQUEST_URI'] === $basePath . '/' ? 'active' : '' ?>">🌍 All Profiles</a></li>
            
            <li style="margin-top: 16px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">Financial Toolbox</li>
            <li><a href="<?= $basePath ?>/tools/compound" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/tools/compound') !== false ? 'active' : '' ?>">📈 Compound Forecaster</a></li>
            <li><a href="<?= $basePath ?>/tools/loan" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/tools/loan') !== false ? 'active' : '' ?>">🚗 Loan Sandbox</a></li>
            
            <li style="margin-top: 16px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">System Security</li>
            <li><a href="<?= $basePath ?>/system/security" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/system/security') !== false ? 'active' : '' ?>">🛡️ System Security</a></li>
            <li><a href="<?= $basePath ?>/account" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/account') !== false ? 'active' : '' ?>">👤 Account Management</a></li>
        <?php else: ?>
            <li><a href="<?= $basePath ?>/" class="nav-item" style="color: var(--text-secondary);">🔙 Back to Profiles</a></li>
            <li style="margin-top: 24px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: <?= htmlspecialchars($profile['color'] ?? 'var(--accent-blue)') ?>; padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">Active: <?= htmlspecialchars($profile['name']) ?></li>
            <li><a href="<?= $basePath ?>/dashboard/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : '' ?>">📊 Dashboard</a></li>
            <li><a href="<?= $basePath ?>/entries/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/entries') !== false ? 'active' : '' ?>">📝 Entries</a></li>
            <li><a href="<?= $basePath ?>/vault/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/vault') !== false ? 'active' : '' ?>">🏦 The Vault</a></li>
            <li><a href="<?= $basePath ?>/insights/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/insights') !== false ? 'active' : '' ?>">📈 Insights</a></li>
            <li><a href="<?= $basePath ?>/radar/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/radar') !== false ? 'active' : '' ?>">💳 Radar</a></li>
            <li><a href="<?= $basePath ?>/forecast/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/forecast') !== false ? 'active' : '' ?>">🔮 Forecast</a></li>
            
            <li style="margin-top: 16px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">System</li>
            <li><a href="<?= $basePath ?>/categories/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/categories') !== false ? 'active' : '' ?>">🏷 Categories</a></li>
            <li><a href="<?= $basePath ?>/backups/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/backups') !== false ? 'active' : '' ?>">💾 Export Node</a></li>
            <li><a href="<?= $basePath ?>/preferences/<?= $pid ?>" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/preferences') !== false ? 'active' : '' ?>">🎨 Preferences</a></li>
            <li><a href="<?= $basePath ?>/profile/<?= $pid ?>/edit" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/edit') !== false ? 'active' : '' ?>">⚙ Settings</a></li>
        <?php endif; ?>
        
        <li style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
            <a href="<?= $basePath ?>/logout" class="nav-item" style="color: var(--accent-red);">🚪 Secure Logout</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<main class="main-content" style="<?= !$isLoggedIn ? 'margin-left: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh;' : '' ?>">