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
if (!empty($prefs['privacy_blur']))
    $bodyClasses[] = 'privacy-blur';
if (!empty($prefs['zen_mode']))
    $bodyClasses[] = 'zen-mode';
if (!empty($prefs['compact_mode']))
    $bodyClasses[] = 'compact-mode';
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

    <link rel="icon" href="<?= asset('favicon.ico') ?>" sizes="any">


    <?php
    $userAccent = $prefs['accent_color'] ?? '#3b82f6';
    $userAccentHover = adjust_color_brightness($userAccent, -30);
    ?>
    <style>
        :root {
            --accent:
                <?= e($userAccent) ?>
            ;
            --accent-hover:
                <?= e($userAccentHover) ?>
            ;
        }
    </style>
    </script>
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

    <?php if (!empty($prefs['zen_mode'])): ?>
        <div style="position: fixed; bottom: 2rem; right: 2rem; z-index: 99999;">
            <a href="<?= url('/preferences') ?>" class="btn btn-primary"
                style="border-radius: 50%; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,0.2);"
                title="Exit Zen Mode">
                <i class="fas fa-expand" style="font-size: 1.2rem;"></i>
            </a>
        </div>
    <?php endif; ?>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-wallet"></i> ExpensePro</h2>
                <button class="btn-close-sidebar" onclick="toggleSidebar()" aria-label="Close Sidebar Menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <!-- Overview -->
                <div class="nav-section">
                    <div class="nav-section-title">Overview</div>
                    <a href="<?= url('/dashboard') ?>"
                        class="nav-item <?= request_is('/dashboard') ? 'active' : '' ?>"><i
                            class="fas fa-chart-line"></i> Dashboard</a>
                    <a href="<?= url('/transactions') ?>"
                        class="nav-item <?= request_is('/transactions*') ? 'active' : '' ?>"><i
                            class="fas fa-arrow-right-arrow-left"></i> Transactions</a>
                    <a href="<?= url('/accounts') ?>"
                        class="nav-item <?= request_is('/accounts*') ? 'active' : '' ?>"><i
                            class="fas fa-building-columns"></i> Accounts</a>
                    <a href="<?= url('/timeline') ?>"
                        class="nav-item <?= request_is('/timeline*') ? 'active' : '' ?>"><i class="fas fa-history"></i>
                        Timeline</a>
                </div>

                <!-- Finance -->
                <div class="nav-section">
                    <div class="nav-section-title">Finance</div>
                    <a href="<?= url('/budgets') ?>" class="nav-item <?= request_is('/budgets*') ? 'active' : '' ?>"><i
                            class="fas fa-piggy-bank"></i> Budgets</a>
                    <a href="<?= url('/vaults') ?>" class="nav-item <?= request_is('/vaults*') ? 'active' : '' ?>"><i
                            class="fas fa-vault"></i> Savings Vaults</a>
                    <a href="<?= url('/bills') ?>" class="nav-item <?= request_is('/bills*') ? 'active' : '' ?>"><i
                            class="fas fa-file-invoice"></i> Bills</a>
                    <a href="<?= url('/salaries') ?>"
                        class="nav-item <?= request_is('/salaries*') ? 'active' : '' ?>"><i
                            class="fas fa-briefcase"></i> Salaries</a>
                    <a href="<?= url('/recurring-incomes') ?>"
                        class="nav-item <?= request_is('/recurring-incomes*') ? 'active' : '' ?>">
                        <i class="fas fa-sync-alt"></i> Recurring Income
                    </a>
                </div>

                <!-- Analytics -->
                <div class="nav-section">
                    <div class="nav-section-title">Analytics</div>
                    <a href="<?= url('/reports') ?>" class="nav-item <?= request_is('/reports*') ? 'active' : '' ?>"><i
                            class="fas fa-file-invoice-dollar"></i> Reports</a>
                    <a href="<?= url('/analytics') ?>"
                        class="nav-item <?= request_is('/analytics*') ? 'active' : '' ?>"><i
                            class="fas fa-chart-simple"></i> Analytics</a>
                    <a href="<?= url('/forecast') ?>"
                        class="nav-item <?= request_is('/forecast*') ? 'active' : '' ?>"><i
                            class="fas fa-chart-area"></i> Cash Flow</a>
                    <a href="<?= url('/calendar') ?>"
                        class="nav-item <?= request_is('/calendar*') ? 'active' : '' ?>"><i
                            class="fas fa-calendar-alt"></i> Calendar</a>
                </div>

                <!-- Insights -->
                <div class="nav-section">
                    <div class="nav-section-title">Insights</div>
                    <a href="<?= url('/monthly-review') ?>"
                        class="nav-item <?= request_is('/monthly-review*') ? 'active' : '' ?>"><i
                            class="fas fa-calendar-days"></i> Monthly Review</a>
                    <a href="<?= url('/yearly-review') ?>"
                        class="nav-item <?= request_is('/yearly-review*') ? 'active' : '' ?>"><i
                            class="fas fa-calendar-check"></i> Yearly Review</a>
                    <a href="<?= url('/achievements') ?>"
                        class="nav-item <?= request_is('/achievements*') ? 'active' : '' ?>"><i
                            class="fas fa-trophy"></i> Achievements</a>
                    </a>
                </div>

                <!-- Simulators -->
                <div class="nav-section">
                    <div class="nav-section-title">Simulators</div>
                    <a href="<?= url('/sandbox/budget') ?>"
                        class="nav-item <?= request_is('/sandbox*') ? 'active' : '' ?>"><i class="fas fa-flask"></i>
                        Budget Sandbox</a>
                    <a href="<?= url('/investments/simulator') ?>"
                        class="nav-item <?= request_is('/investments*') ? 'active' : '' ?>"><i
                            class="fas fa-seedling"></i> Investment Sim</a>
                    <a href="<?= url('/loans/simulator') ?>"
                        class="nav-item <?= request_is('/loans*') ? 'active' : '' ?>"><i
                            class="fas fa-hand-holding-dollar"></i> Loan Simulator</a>
                </div>

                <!-- Tracking -->
                <div class="nav-section">
                    <div class="nav-section-title">Tracking</div>
                    <a href="<?= url('/pending-ledger') ?>"
                        class="nav-item <?= request_is('/pending-ledger*') ? 'active' : '' ?>"><i
                            class="fas fa-hourglass-half"></i> Pending Ledger</a>
                    <a href="<?= url('/daily-logs') ?>"
                        class="nav-item <?= request_is('/daily-logs*') ? 'active' : '' ?>"><i
                            class="fas fa-book-open"></i> Daily Logs</a>
                </div>

                <div class="nav-divider"></div>

                <!-- System -->
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="<?= url('/categories') ?>"
                        class="nav-item <?= request_is('/categories*') ? 'active' : '' ?>"><i class="fas fa-tags"></i>
                        Categories</a>
                    <a href="<?= url('/preferences') ?>"
                        class="nav-item <?= request_is('/preferences*') ? 'active' : '' ?>"><i
                            class="fas fa-sliders"></i> Preferences</a>
                    <a href="<?= url('/settings') ?>"
                        class="nav-item <?= request_is('/settings*') ? 'active' : '' ?>"><i class="fas fa-database"></i>
                        Backup & Settings</a>
                    <a href="<?= url('/profile') ?>" class="nav-item <?= request_is('/profile*') ? 'active' : '' ?>"><i
                            class="fas fa-user-gear"></i> Profile</a>
                    <a href="<?= url('/logout') ?>" class="nav-item text-danger"><i
                            class="fas fa-right-from-bracket"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <button class="btn-menu" onclick="toggleSidebar()" aria-label="Open Sidebar Menu"><i
                        class="fas fa-bars"></i></button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="topbarSearch" placeholder="Search (Ctrl+K)...">
                </div>
                <div class="topbar-actions">
                    <button class="btn-icon" id="zen-toggle" title="Toggle Focus Mode" aria-label="Toggle Focus Mode">
                        <i class="fas fa-bullseye"></i>
                    </button>
                    <button class="btn-icon" id="compact-toggle" title="Toggle Compact Mode"
                        aria-label="Toggle Compact Mode">
                        <i class="fas fa-compress-alt"></i>
                    </button>
                    <button class="btn-icon" id="privacy-toggle" title="Toggle Privacy Blur"
                        aria-label="Toggle Privacy Blur">
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
                    <div
                        style="background: var(--danger, #dc3545); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 600;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= e(Session::get('error') ?? $_SESSION['error']) ?>
                        <?php Session::remove('error'); ?>
                    </div>
                <?php endif; ?>

                <?php if (Session::has('success') || isset($_SESSION['success'])): ?>
                    <div
                        style="background: var(--success, #198754); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 600;">
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
        <a href="<?= url('/dashboard') ?>" class="bottom-nav-item <?= request_is('/dashboard') ? 'active' : '' ?>"><i
                class="fas fa-home"></i><span>Home</span></a>
        <a href="<?= url('/transactions') ?>"
            class="bottom-nav-item <?= request_is('/transactions*') ? 'active' : '' ?>"><i
                class="fas fa-receipt"></i><span>Trans</span></a>
        <a href="<?= url('/transactions/create') ?>" class="bottom-nav-item add-btn"><i class="fas fa-plus"></i></a>
        <a href="<?= url('/accounts') ?>" class="bottom-nav-item <?= request_is('/accounts*') ? 'active' : '' ?>"><i
                class="fas fa-wallet"></i><span>Accounts</span></a>
        <a href="<?= url('/profile') ?>" class="bottom-nav-item <?= request_is('/profile*') ? 'active' : '' ?>"><i
                class="fas fa-cog"></i><span>Settings</span></a>
    </nav>

    <!-- Global Search Modal -->
    <div id="searchModal" class="modal-overlay" style="display: none;">
        <div class="modal-content glass">
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="globalSearchInput" placeholder="Search transactions, accounts..."
                    autocomplete="off">
                <kbd>ESC</kbd>
            </div>
            <div id="searchResults" class="search-results"></div>
        </div>
    </div>

    <!-- Achievement Notification System -->
    <?php
    $achNotif = Session::get('achievement_notification');
    if ($achNotif && (isset($achNotif['leveled_up']) || !empty($achNotif['unlocks']))):
        Session::remove('achievement_notification');
        ?>
        <div id="achievementToastContainer"
            style="position: fixed; top: 100px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 10px;">
            <?php if (isset($achNotif['leveled_up']) && $achNotif['leveled_up']): ?>
                <div class="ach-toast"
                    style="display: flex; align-items: center; gap: 1rem; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px); border: 1px solid rgba(245, 158, 11, 0.5); border-left: 4px solid #f59e0b; border-radius: 12px; padding: 1rem; min-width: 320px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideInRight 0.5s ease-out;">
                    <div
                        style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #ef4444); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div style="flex: 1;">
                        <div
                            style="color: #f8fafc; font-weight: 700; font-size: 1rem; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            LEVEL UP!</div>
                        <div style="color: #94a3b8; font-size: 0.85rem;">You reached <strong style="color: white;">Level
                                <?= (int) $achNotif['new_level'] ?></strong> with <?= number_format($achNotif['total_xp']) ?>
                            XP!
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($achNotif['rewards'])): ?>
                <?php foreach ($achNotif['rewards'] as $reward): ?>
                    <div class="ach-toast"
                        style="display: flex; align-items: center; gap: 1rem; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px); border: 2px solid <?= e($reward['color']) ?>; border-radius: 12px; padding: 1rem; min-width: 320px; box-shadow: 0 0 30px <?= e($reward['color']) ?>66; animation: slideInRight 0.5s ease-out, pulse 2s infinite;">
                        <div
                            style="width: 56px; height: 56px; border-radius: 50%; background: <?= e($reward['color']) ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                            <i class="fas <?= e($reward['icon']) ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <div
                                style="color: <?= e($reward['color']) ?>; font-weight: 800; font-size: 1rem; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                Reward Unlocked!</div>
                            <div style="color: #f8fafc; font-size: 0.9rem;">
                                <?= e($reward['name']) ?> <span style="color: #94a3b8; font-size: 0.8rem;">-
                                    <?= e($reward['description']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($achNotif['unlocks'])): ?>
                <?php foreach ($achNotif['unlocks'] as $unlock): ?>
                    <div class="ach-toast"
                        style="display: flex; align-items: center; gap: 1rem; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px); border: 1px solid rgba(59, 130, 246, 0.3); border-left: 4px solid <?= e($unlock['color']) ?>; border-radius: 12px; padding: 1rem; min-width: 320px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideInRight 0.5s ease-out;">
                        <div
                            style="width: 48px; height: 48px; border-radius: 50%; background: <?= e($unlock['color']) ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                            <i class="fas <?= e($unlock['icon']) ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="color: #f8fafc; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.25rem;">Achievement
                                Unlocked!</div>
                            <div style="color: #94a3b8; font-size: 0.85rem;"><?= e($unlock['name']) ?> <span
                                    style="color: #f59e0b; font-size: 0.8rem;">(+<?= (int) $unlock['xp_value'] ?> XP)</span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toasts = document.querySelectorAll('.ach-toast');
                toasts.forEach(function (toast, index) {
                    setTimeout(function () {
                        toast.style.animation = 'fadeOutRight 0.5s ease forwards';
                        setTimeout(function () {
                            toast.remove();
                        }, 500);
                    }, 5000 + (index * 1000));
                });
            });
        </script>
    <?php endif; ?>
    <script src="<?= asset('js/app.js') ?>"></script>
</body>

</html>