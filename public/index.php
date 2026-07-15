<?php
declare(strict_types=1);

// --- DEBUG MODE ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php-error.log');

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = BASE_PATH . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once BASE_PATH . '/app/Helpers/functions.php';
if (!file_exists(BASE_PATH . '/config/config.php') && !defined('IS_INSTALLER')) {
    header('Location: /expenses/install.php');
    exit;
}

\App\Core\Session::start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/expenses/' || $uri === '/expenses') {
    if (\App\Core\Auth::check()) {
        $prefs = \App\Models\Preference::get(\App\Core\Auth::id());
        $landingPage = $prefs['default_landing_page'] ?? '/dashboard';
        header('Location: /expenses' . $landingPage);
    } else {
        header('Location: /expenses/login');
    }
    exit;
}

$router = new \App\Core\Router();


// Auth Routes
$router->get('/login', ['AuthController', 'showLogin']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/register', ['AuthController', 'showRegister']);
$router->post('/register', ['AuthController', 'register']);
$router->get('/logout', ['AuthController', 'logout']);
$router->get('/forgot-password', ['AuthController', 'showForgot']);
$router->post('/forgot-password', ['AuthController', 'forgot']);
$router->get('/reset-password', ['AuthController', 'showReset']);
$router->post('/reset-password', ['AuthController', 'reset']);

// Profile Routes (Protected via Controller constructor)
$router->get('/profile', ['ProfileController', 'index']);
$router->post('/profile/update', ['ProfileController', 'update']);
$router->post('/profile/change-password', ['ProfileController', 'changePassword']);

// Accounts Module
$router->get('/accounts', ['AccountController', 'index']);
$router->get('/accounts/create', ['AccountController', 'create']);
$router->post('/accounts/store', ['AccountController', 'store']);
$router->get('/accounts/edit/{id}', ['AccountController', 'edit']);
$router->post('/accounts/update/{id}', ['AccountController', 'update']);
$router->post('/accounts/delete/{id}', ['AccountController', 'delete']);

// Transactions Module
$router->get('/transactions', ['TransactionController', 'index']);
$router->get('/transactions/create', ['TransactionController', 'create']);
$router->post('/transactions/store', ['TransactionController', 'store']);

// Budgets Module
$router->get('/budgets', ['BudgetController', 'index']);
$router->post('/budgets/store', ['BudgetController', 'store']);

// Reports Module
$router->get('/reports', ['ReportController', 'index']);
$router->get('/reports/export-csv', ['ReportController', 'exportCsv']);

// Dashboard (Protected)
$router->get('/api/dashboard/stats', ['DashboardController', 'getStats']);
$router->get('/dashboard', ['DashboardController', 'index']);
// Offline Sync Endpoint
$router->post('/transactions/sync', ['SyncController', 'syncTransactions']);

// Global Search
$router->get('/api/search', ['SearchController', 'globalSearch']);

// Settings & Backup
$router->get('/settings', ['SettingsController', 'index']);
$router->get('/settings/backup', ['SettingsController', 'backup']);
$router->post('/settings/restore', ['SettingsController', 'restore']);

// Financial Intelligence Modules
$router->get('/pending-ledger', ['PendingLedgerController', 'index']);
$router->post('/pending-ledger/store', ['PendingLedgerController', 'store']);
$router->post('/pending-ledger/mark-paid/{id}', ['PendingLedgerController', 'markPaid']);
// Daily Logs Module
$router->get('/daily-logs', ['DailyLogController', 'index']);
$router->post('/daily-logs/store', ['DailyLogController', 'store']);

// Bills Module
$router->get('/bills', ['BillController', 'index']);
$router->post('/bills/store', ['BillController', 'store']);
$router->post('/bills/pay/{id}', ['BillController', 'pay']);

// Salaries Module
$router->get('/salaries', ['SalaryController', 'index']);
$router->get('/salaries/create', ['SalaryController', 'create']);
$router->post('/salaries/store', ['SalaryController', 'store']);
$router->get('/salaries/show/{id}', ['SalaryController', 'show']);
$router->get('/salaries/export-csv', ['SalaryController', 'exportCsv']);

// Analytics & Insights Module
$router->get('/analytics', ['AnalyticsController', 'index']);
$router->post('/analytics/resolve-alert/{id}', ['AnalyticsController', 'resolveAlert']);

// Categories Module
$router->get('/categories', ['CategoryController', 'index']);
$router->post('/categories/store', ['CategoryController', 'store']);
$router->post('/categories/archive/{id}', ['CategoryController', 'archive']);

// Preferences Module
$router->get('/preferences', ['PreferenceController', 'index']);
$router->post('/preferences/save', ['PreferenceController', 'save']);
$router->post('/preferences/update-theme', ['PreferenceController', 'updateTheme']);
$router->post('/preferences/update-privacy', ['PreferenceController', 'updatePrivacy']);
$router->post('/preferences/update-compact', ['PreferenceController', 'updateCompact']);
$router->post('/preferences/update-zen', ['PreferenceController', 'updateZen']);

// Savings Vaults Module
$router->get('/vaults', ['VaultController', 'index']);
$router->get('/vaults/create', ['VaultController', 'create']);
$router->post('/vaults/store', ['VaultController', 'store']);
$router->get('/vaults/show/{id}', ['VaultController', 'show']);
$router->post('/vaults/transact/{id}', ['VaultController', 'transact']);
$router->post('/vaults/status/{id}', ['VaultController', 'updateStatus']);

// Monthly Review Module
$router->get('/monthly-review', ['MonthlyReviewController', 'index']);
$router->get('/yearly-review', ['YearlyReviewController', 'index']);
$router->get('/yearly-review/export-csv', ['YearlyReviewController', 'exportCsv']);

// Budget Sandbox Module
$router->get('/sandbox/budget', ['SandboxController', 'budget']);
$router->post('/sandbox/apply', ['SandboxController', 'applyPlan']);

// Investment Simulator Module
$router->get('/investments/simulator', ['InvestmentController', 'simulator']);

// Loan Sandbox Module
$router->get('/loans/simulator', ['LoanController', 'simulator']);

// Dispatch request
$router->dispatch();