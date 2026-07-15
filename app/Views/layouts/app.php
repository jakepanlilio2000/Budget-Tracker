<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;
use App\Models\Preference;

$user = Auth::user();
$pageTitle = $pageTitle ?? 'Dashboard';

$prefs = Preference::get($user['id']);
$bodyClasses = [];
if (!empty($prefs['privacy_blur'])) $bodyClasses[] = 'privacy-blur';
if (!empty($prefs['zen_mode'])) $bodyClasses[] = 'zen-mode';
if (!empty($prefs['compact_mode'])) $bodyClasses[] = 'compact-mode';
$bodyClassStr = implode(' ', $bodyClasses);

$htmlTheme = ($prefs['theme'] === 'auto') ? 'system' : e($prefs['theme']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $htmlTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Expense Tracker</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">

     <?php 
    $userAccent = $prefs['accent_color'] ?? '#3b82f6';
    $userAccentHover = adjust_color_brightness($userAccent, -30); 
    ?>
     <style>
        :root {
            --accent: <?= e($userAccent) ?>;
            --accent-hover: <?= e($userAccentHover) ?>;
        }
    </style></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="csrf-token" content="<?= CSRF::generate() ?>">
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= url('/sw.js') ?>')
                .then(reg => console.log('SW registered'))
                .catch(err => console.log('SW failed', err));
        }
    </script>
    <script src="<?= asset('js/offline.js') ?>"></script>
</head>
<body class="<?= $bodyClassStr ?>">
    
    <!-- Safety Net: Floating button to exit Zen Mode if trapped -->
    <?php if (!empty($prefs['zen_mode'])): ?>
    <div style="position: fixed; bottom: 2rem; right: 2rem; z-index: 99999;">
        <a href="<?= url('/preferences') ?>" class="btn btn-primary" style="border-radius: 50%; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,0.2);" title="Exit Zen Mode">
            <i class="fas fa-expand" style="font-size: 1.2rem;"></i>
        </a>
    </div>
    <?php endif; ?>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-wallet"></i> ExpensePro</h2>
                <!-- FIX: Corrected the broken closing tags here -->
                <button class="btn-close-sidebar" onclick="toggleSidebar()" aria-label="Close Sidebar Menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
                        <nav class="sidebar-nav">
                <!-- Overview -->
                <div class="nav-section">
                    <div class="nav-section-title">Overview</div>
                    <a href="<?= url('/dashboard') ?>" class="nav-item <?= request_is('/dashboard') ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
                    <a href="<?= url('/transactions') ?>" class="nav-item <?= request_is('/transactions*') ? 'active' : '' ?>"><i class="fas fa-arrow-right-arrow-left"></i> Transactions</a>
                    <a href="<?= url('/accounts') ?>" class="nav-item <?= request_is('/accounts*') ? 'active' : '' ?>"><i class="fas fa-building-columns"></i> Accounts</a>
                </div>

                <!-- Finance -->
                <div class="nav-section">
                    <div class="nav-section-title">Finance</div>
                    <a href="<?= url('/budgets') ?>" class="nav-item <?= request_is('/budgets*') ? 'active' : '' ?>"><i class="fas fa-piggy-bank"></i> Budgets</a>
                    <a href="<?= url('/vaults') ?>" class="nav-item <?= request_is('/vaults*') ? 'active' : '' ?>"><i class="fas fa-vault"></i> Savings Vaults</a>
                    <a href="<?= url('/bills') ?>" class="nav-item <?= request_is('/bills*') ? 'active' : '' ?>"><i class="fas fa-file-invoice"></i> Bills</a>
                    <a href="<?= url('/salaries') ?>" class="nav-item <?= request_is('/salaries*') ? 'active' : '' ?>"><i class="fas fa-briefcase"></i> Salaries</a>
                </div>

                <!-- Analysis -->
                <div class="nav-section">
                    <div class="nav-section-title">Analysis</div>
                    <a href="<?= url('/reports') ?>" class="nav-item <?= request_is('/reports*') ? 'active' : '' ?>"><i class="fas fa-file-invoice-dollar"></i> Reports</a>
                    <a href="<?= url('/analytics') ?>" class="nav-item <?= request_is('/analytics*') ? 'active' : '' ?>"><i class="fas fa-chart-simple"></i> Analytics</a>
                    <a href="<?= url('/monthly-review') ?>" class="nav-item <?= request_is('/monthly-review*') ? 'active' : '' ?>"><i class="fas fa-calendar-days"></i> Monthly Review</a>
                    <a href="<?= url('/yearly-review') ?>" class="nav-item <?= request_is('/yearly-review*') ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Yearly Review</a>
                </div>

                <!-- Tools -->
                <div class="nav-section">
                    <div class="nav-section-title">Tools</div>
                    <a href="<?= url('/sandbox/budget') ?>" class="nav-item <?= request_is('/sandbox*') ? 'active' : '' ?>"><i class="fas fa-flask"></i> Budget Sandbox</a>
                    <a href="<?= url('/investments/simulator') ?>" class="nav-item <?= request_is('/investments*') ? 'active' : '' ?>"><i class="fas fa-seedling"></i> Investment Sim</a>
                    <a href="<?= url('/loans/simulator') ?>" class="nav-item <?= request_is('/loans*') ? 'active' : '' ?>"><i class="fas fa-hand-holding-dollar"></i> Loan Simulator</a>
                    <a href="<?= url('/pending-ledger') ?>" class="nav-item <?= request_is('/pending-ledger*') ? 'active' : '' ?>"><i class="fas fa-hourglass-half"></i> Pending Ledger</a>
                    <a href="<?= url('/daily-logs') ?>" class="nav-item <?= request_is('/daily-logs*') ? 'active' : '' ?>"><i class="fas fa-book-open"></i> Daily Logs</a>
                </div>

                <div class="nav-divider"></div>

                <!-- System -->
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="<?= url('/categories') ?>" class="nav-item <?= request_is('/categories*') ? 'active' : '' ?>"><i class="fas fa-tags"></i> Categories</a>
                    <a href="<?= url('/preferences') ?>" class="nav-item <?= request_is('/preferences*') ? 'active' : '' ?>"><i class="fas fa-sliders"></i> Preferences</a>
                    <a href="<?= url('/settings') ?>" class="nav-item <?= request_is('/settings*') ? 'active' : '' ?>"><i class="fas fa-database"></i> Backup & Settings</a>
                    <a href="<?= url('/profile') ?>" class="nav-item <?= request_is('/profile*') ? 'active' : '' ?>"><i class="fas fa-user-gear"></i> Profile</a>
                    <a href="<?= url('/logout') ?>" class="nav-item text-danger"><i class="fas fa-right-from-bracket"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <button class="btn-menu" onclick="toggleSidebar()" aria-label="Open Sidebar Menu"><i class="fas fa-bars"></i></button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="topbarSearch" placeholder="Search (Ctrl+K)...">
                </div>
                <div class="topbar-actions">
                    <button class="btn-icon" id="zen-toggle" title="Toggle Focus Mode" aria-label="Toggle Focus Mode">
                        <i class="fas fa-bullseye"></i>
                    </button>
                    <button class="btn-icon" id="compact-toggle" title="Toggle Compact Mode" aria-label="Toggle Compact Mode">
                        <i class="fas fa-compress-alt"></i>
                    </button>
                    <button class="btn-icon" id="privacy-toggle" title="Toggle Privacy Blur" aria-label="Toggle Privacy Blur">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon" id="theme-toggle" title="Toggle Theme" aria-label="Toggle Theme">
                        <i class="fas fa-desktop"></i>
                    </button>
                    <div class="user-dropdown">
                        <button class="btn-user">
                            <div class="avatar"><?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?></div>
                            <span class="user-name"><?= e($user['full_name'] ?? 'User') ?></span>
                        </button>
                    </div>
                </div>
                <button class="zen-exit-btn" id="zen-exit-btn">
                <i class="fas fa-expand-alt"></i> Exit Focus
            </button>
            </header>

            <!-- Page Content -->
           <div class="content-wrapper">
                <?php if (Session::has('error') || isset($_SESSION['error'])): ?>
                    <div style="background: var(--danger, #dc3545); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 600;">
                        <i class="fas fa-exclamation-circle"></i> 
                        <?= e(Session::get('error') ?? $_SESSION['error']) ?>
                        <?php Session::remove('error'); ?>
                    </div>
                <?php endif; ?>

                <?php if (Session::has('success') || isset($_SESSION['success'])): ?>
                    <div style="background: var(--success, #198754); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> 
                        <?= e(Session::get('success') ?? $_SESSION['success']) ?>
                        <?php Session::remove('success'); ?>
                    </div>
                <?php endif; ?>
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <nav class="bottom-nav">
        <a href="<?= url('/dashboard') ?>" class="bottom-nav-item <?= request_is('/dashboard') ? 'active' : '' ?>"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="<?= url('/transactions') ?>" class="bottom-nav-item <?= request_is('/transactions*') ? 'active' : '' ?>"><i class="fas fa-receipt"></i><span>Trans</span></a>
        <a href="<?= url('/transactions/create') ?>" class="bottom-nav-item add-btn"><i class="fas fa-plus"></i></a>
        <a href="<?= url('/accounts') ?>" class="bottom-nav-item <?= request_is('/accounts*') ? 'active' : '' ?>"><i class="fas fa-wallet"></i><span>Accounts</span></a>
        <a href="<?= url('/profile') ?>" class="bottom-nav-item <?= request_is('/profile*') ? 'active' : '' ?>"><i class="fas fa-cog"></i><span>Settings</span></a>
    </nav>

    <!-- Global Search Modal -->
    <div id="searchModal" class="modal-overlay" style="display: none;">
        <div class="modal-content glass">
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="globalSearchInput" placeholder="Search transactions, accounts..." autocomplete="off">
                <kbd>ESC</kbd>
            </div>
            <div id="searchResults" class="search-results"></div>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>