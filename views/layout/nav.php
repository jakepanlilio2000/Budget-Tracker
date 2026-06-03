<?php 
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$pid = $profile['id'] ?? ($profile_id ?? 0); 
$isLoggedIn = isset($_SESSION['user_id']);
$uri = $_SERVER['REQUEST_URI'];
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
        </div>
    </div>

    <ul class="nav-links">
        <?php if (!$pid): ?>
            <li style="margin-top: 4px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">Global Portfolio</li>
            <li><a href="<?= $basePath ?>/" class="nav-item <?= $uri === $basePath . '/' ? 'active' : '' ?>">🌍 All Profiles</a></li>
            
            <li style="margin-top: 16px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">Financial Toolbox</li>
            <li><a href="<?= $basePath ?>/tools/compound" class="nav-item <?= strpos($uri, '/tools/compound') !== false ? 'active' : '' ?>">📈 Compound Forecaster</a></li>
            <li><a href="<?= $basePath ?>/tools/loan" class="nav-item <?= strpos($uri, '/tools/loan') !== false ? 'active' : '' ?>">🚗 Loan Sandbox</a></li>
            
            <li style="margin-top: 16px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">System Security</li>
            <li><a href="<?= $basePath ?>/system/security" class="nav-item <?= strpos($uri, '/system/security') !== false ? 'active' : '' ?>">🛡️ System Security</a></li>
            <li><a href="<?= $basePath ?>/account" class="nav-item <?= strpos($uri, '/account') !== false ? 'active' : '' ?>">👤 Account Management</a></li>
        <?php else: ?>
            <li><a href="<?= $basePath ?>/" class="nav-item" style="color: var(--text-secondary);">🔙 Back to Profiles</a></li>
            <li style="margin-top: 24px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: <?= htmlspecialchars($profile['color'] ?? 'var(--accent-blue)') ?>; padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">Active: <?= htmlspecialchars($profile['name'] ?? '') ?></li>
            
            <li><a href="<?= $basePath ?>/dashboard/<?= $pid ?>" class="nav-item <?= strpos($uri, '/dashboard') !== false ? 'active' : '' ?>">📊 Dashboard</a></li>
            <li><a href="<?= $basePath ?>/entries/<?= $pid ?>" class="nav-item <?= strpos($uri, '/entries') !== false ? 'active' : '' ?>">📝 Entries</a></li>
            <li><a href="<?= $basePath ?>/vault/<?= $pid ?>" class="nav-item <?= strpos($uri, '/vault') !== false ? 'active' : '' ?>">🏦 The Vault</a></li>
            <li><a href="<?= $basePath ?>/income/<?= $pid ?>" class="nav-item <?= strpos($uri, '/income') !== false ? 'active' : '' ?>">💰 Income & Revenue</a></li>
            <li><a href="<?= $basePath ?>/shopping/<?= $pid ?>" class="nav-item <?= strpos($uri, '/shopping') !== false ? 'active' : '' ?>">🛍️ Daily Spends</a></li>
            <li><a href="<?= $basePath ?>/insights/<?= $pid ?>" class="nav-item <?= strpos($uri, '/insights') !== false ? 'active' : '' ?>">📈 Insights</a></li>
            <li><a href="<?= $basePath ?>/radar/<?= $pid ?>" class="nav-item <?= strpos($uri, '/radar') !== false ? 'active' : '' ?>">💳 Radar</a></li>
            <li><a href="<?= $basePath ?>/forecast/<?= $pid ?>" class="nav-item <?= strpos($uri, '/forecast') !== false ? 'active' : '' ?>">🔮 Forecast</a></li>
            
            <li style="margin-top: 16px; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; color: var(--text-muted); padding-left: 16px; font-weight: bold; letter-spacing: 0.5px;">System</li>
            <li><a href="<?= $basePath ?>/categories/<?= $pid ?>" class="nav-item <?= strpos($uri, '/categories') !== false ? 'active' : '' ?>">🏷 Categories</a></li>
            <li><a href="<?= $basePath ?>/backups/<?= $pid ?>" class="nav-item <?= strpos($uri, '/backups') !== false ? 'active' : '' ?>">💾 Export Node</a></li>
            <li><a href="<?= $basePath ?>/preferences/<?= $pid ?>" class="nav-item <?= strpos($uri, '/preferences') !== false ? 'active' : '' ?>">🎨 Preferences</a></li>
            <li><a href="<?= $basePath ?>/profile/<?= $pid ?>/edit" class="nav-item <?= strpos($uri, '/edit') !== false ? 'active' : '' ?>">⚙ Settings</a></li>
        <?php endif; ?>
        
        <li style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
            <a href="<?= $basePath ?>/logout" class="nav-item" style="color: var(--accent-red);">🚪 Secure Logout</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<main class="main-content" style="<?= !$isLoggedIn ? 'margin-left: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh;' : '' ?>">

    <?php if ($isLoggedIn): ?>
    <div class="mobile-only" style="align-items: center; justify-content: space-between; padding: 12px 16px; background: rgba(22, 27, 34, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); margin: -12px -12px 16px -12px; position: sticky; top: 0; z-index: 900; border-radius: 0 0 12px 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="profile-badge" style="background: <?= htmlspecialchars($profile['color'] ?? '#4F7BF7') ?>; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; color: #fff; border-radius: 50%;">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
            </div>
            <h2 style="font-size: 16px; margin: 0; color: var(--text-primary);">Budget<span style="color: var(--accent-blue);">Suite</span></h2>
        </div>
        <button id="mobile-nav-toggle" class="icon-btn ghost" style="font-size: 24px; color: var(--text-primary); padding: 4px 8px; border: 1px solid var(--border); background: var(--bg-elevated);">☰</button>
    </div>
    <?php endif; ?>
<!-- <script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    
    // 1. SCROLL MEMORY (Failsafe for hard reloads like Form Submissions)
    if (sidebar) {
        const savedScroll = localStorage.getItem('sidebarScroll');
        if (savedScroll) sidebar.scrollTop = savedScroll;
        
        sidebar.addEventListener('scroll', () => {
            localStorage.setItem('sidebarScroll', sidebar.scrollTop);
        });
    }

    // 2. THE SPA TURBO ENGINE
    document.body.addEventListener('click', async (e) => {
        // Only intercept clicks on the sidebar navigation links
        const link = e.target.closest('a.nav-item');
        if (!link) return;

        const url = link.getAttribute('href');
        
        // Let the secure Logout bypass the SPA engine to safely destroy PHP sessions
        if (url.includes('logout') || link.target === '_blank') return;
        
        e.preventDefault();

        // Instantly update the blue "active" highlight for zero-latency feedback
        document.querySelectorAll('a.nav-item').forEach(n => n.classList.remove('active'));
        link.classList.add('active');

        // Optional: Show a brief loading state on the mouse cursor
        document.body.style.cursor = 'wait';

        try {
            // Fetch the HTML of the page we want to navigate to
            const response = await fetch(url);
            const htmlText = await response.text();
            
            // Parse the response into a virtual DOM
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            
            // Extract the newly generated content
            const currentMain = document.querySelector('.main-content');
            const newMain = doc.querySelector('.main-content');
            
            if (currentMain && newMain) {
                // Swap the content seamlessly (Sidebar is completely untouched!)
                currentMain.innerHTML = newMain.innerHTML;
                
                // Update the browser URL and Tab Title quietly
                window.history.pushState({}, '', url);
                document.title = doc.title;
                
                // CRITICAL FIX: Re-execute Javascript on the new page
                // Browsers block <script> tags from running when injected via innerHTML.
                // We must manually recreate them so your Chart.js and Calculators still work.
                const scripts = currentMain.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.innerHTML = oldScript.innerHTML;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            } else {
                // Failsafe: If the page structure is vastly different, do a normal redirect
                window.location.href = url; 
            }
        } catch (error) {
            console.error('SPA Engine Failed:', error);
            window.location.href = url; // Failsafe on network errors
        } finally {
            document.body.style.cursor = 'default';
        }
    });

    // Handle the Browser's physical "Back" and "Forward" buttons smoothly
    window.addEventListener('popstate', () => {
        window.location.reload(); 
    });
});
</script> -->

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. SCROLL MEMORY: Target the actual scrolling element (.nav-links)
    const navContainer = document.querySelector('.nav-links');
    
    if (navContainer) {
        const savedScroll = localStorage.getItem('sidebarScroll');
        if (savedScroll) navContainer.scrollTop = savedScroll;
        
        navContainer.addEventListener('scroll', () => {
            localStorage.setItem('sidebarScroll', navContainer.scrollTop);
        });
    }

    // 2. THE SPA TURBO ENGINE
    document.body.addEventListener('click', async (e) => {
        const link = e.target.closest('a.nav-item');
        if (!link) return;

        const url = link.getAttribute('href');
        
        // Let the secure Logout bypass the SPA engine
        if (url.includes('logout') || link.target === '_blank') return;
        
        e.preventDefault();

        // Instantly update the blue "active" highlight
        document.querySelectorAll('a.nav-item').forEach(n => n.classList.remove('active'));
        link.classList.add('active');
        document.body.style.cursor = 'wait';

        try {
            const response = await fetch(url);
            const htmlText = await response.text();
            
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            
            const currentMain = document.querySelector('.main-content');
            const newMain = doc.querySelector('.main-content');
            
            // Grab the specific scrolling containers
            const currentNav = document.querySelector('.nav-links');
            const newNav = doc.querySelector('.nav-links');
            
            if (currentMain && newMain) {
                // Swap the main content seamlessly
                currentMain.innerHTML = newMain.innerHTML;
                
                // Swap the sidebar links (for "Back to Profiles") but keep scroll locked
                if (currentNav && newNav) {
                    const currentScroll = currentNav.scrollTop;
                    currentNav.innerHTML = newNav.innerHTML;
                    currentNav.scrollTop = currentScroll; // Snap it back instantly
                }
                
                // Update the browser URL and Tab Title quietly
                window.history.pushState({}, '', url);
                document.title = doc.title;
                
                // Re-execute Javascript on the new page
                const scripts = currentMain.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.innerHTML = oldScript.innerHTML;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            } else {
                window.location.href = url; 
            }
        } catch (error) {
            console.error('SPA Engine Failed:', error);
            window.location.href = url; 
        } finally {
            document.body.style.cursor = 'default';
        }
    });

    // Handle the Browser's physical "Back" and "Forward" buttons smoothly
    window.addEventListener('popstate', () => {
        window.location.reload(); 
    });
});
</script>