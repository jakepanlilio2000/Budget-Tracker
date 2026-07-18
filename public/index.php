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
if ($uri === '/expenses/' || $uri === '/expenses' || $uri === '/') {
    if (\App\Core\Auth::check()) {
        $prefs = \App\Models\Preference::get(\App\Core\Auth::id());
        $landingPage = $prefs['default_landing_page'] ?? '/dashboard';
        header('Location: /expenses' . $landingPage);
        exit;
    }
}

$router = new \App\Core\Router();

// --- PUBLIC ROUTES ---
$router->get('/', ['LandingController', 'index']);
$router->get('/home', ['LandingController', 'index']);

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
$router->get('/accounts/adjust/{id}', ['AccountController', 'adjust']);
$router->post('/accounts/process-adjustment/{id}', ['AccountController', 'processAdjustment']);

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
$router->post('/dashboard/save-layout', ['DashboardController', 'saveLayout']);

// Offline Sync Endpoint
$router->post('/transactions/sync', ['SyncController', 'syncTransactions']);

// Global Search
$router->get('/api/search', ['SearchController', 'globalSearch']);

// Settings & Backup
$router->get('/settings', ['SettingsController', 'index']);
$router->get('/settings/backup', ['SettingsController', 'backup']);
$router->post('/settings/preview-restore', ['SettingsController', 'previewRestore']);
$router->post('/settings/execute-restore', ['SettingsController', 'executeRestore']);
$router->post('/settings/delete-all', ['SettingsController', 'deleteAll']);

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
$router->get('/bills/edit/{id}', ['BillController', 'edit']);
$router->post('/bills/update/{id}', ['BillController', 'update']);
$router->post('/bills/cancel/{id}', ['BillController', 'cancel']);

// Salaries Module
$router->get('/salaries', ['SalaryController', 'index']);
$router->get('/salaries/create', ['SalaryController', 'create']);
$router->post('/salaries/store', ['SalaryController', 'store']);
$router->get('/salaries/show/{id}', ['SalaryController', 'show']);
$router->get('/salaries/export-csv', ['SalaryController', 'exportCsv']);
$router->get('/salaries/edit/{id}', ['SalaryController', 'edit']);
$router->post('/salaries/update/{id}', ['SalaryController', 'update']);
$router->post('/salaries/delete/{id}', ['SalaryController', 'delete']);

// Analytics & Insights Module
$router->get('/analytics', ['AnalyticsController', 'index']);
$router->post('/analytics/resolve-alert/{id}', ['AnalyticsController', 'resolveAlert']);

// Categories Module
$router->get('/categories', ['CategoryController', 'index']);
$router->post('/categories/store', ['CategoryController', 'store']);
$router->post('/categories/archive/{id}', ['CategoryController', 'archive']);
$router->post('/categories/delete/{id}', ['CategoryController', 'delete']);

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
$router->post('/sandbox/apply', ['SandboxController', 'applyPlan']);
$router->get('/sandbox/budget', ['SandboxController', 'budget']);
$router->get('/sandbox/studio', ['SandboxController', 'studio']);

// Scenario Manager Routes
$router->post('/sandbox/scenario/store', ['SandboxController', 'storeScenario']);
$router->post('/sandbox/scenario/duplicate/{id}', ['SandboxController', 'duplicateScenario']);
$router->post('/sandbox/scenario/archive/{id}', ['SandboxController', 'toggleArchiveScenario']);
$router->post('/sandbox/scenario/delete/{id}', ['SandboxController', 'deleteScenario']);
$router->get('/sandbox/scenario/load/{id}', ['SandboxController', 'loadScenario']);
$router->post('/sandbox/template/save', ['SandboxController', 'saveTemplate']);

// Loan & Debt Simulator Routes
$router->post('/sandbox/loan/add', ['SandboxController', 'addLoan']);
$router->post('/sandbox/loan/delete/{id}', ['SandboxController', 'deleteLoan']);
$router->get('/sandbox/loan/amortization', ['SandboxController', 'getLoanAmortization']);
$router->get('/sandbox/loan/list', ['SandboxController', 'listLoans']);

// Investment Simulator & Unified Cash Flow Routes
$router->post('/sandbox/investment/add', ['SandboxController', 'addInvestment']);
$router->post('/sandbox/investment/delete/{id}', ['SandboxController', 'deleteInvestment']);
$router->get('/sandbox/investment/list', ['SandboxController', 'listInvestments']);
$router->get('/sandbox/unified-cash-flow', ['SandboxController', 'getUnifiedCashFlow']);

// Financial Health & Recommendations
$router->get('/sandbox/health-analysis', ['SandboxController', 'getHealthAnalysis']);

// Investment Simulator Module
$router->get('/investments/simulator', ['InvestmentController', 'simulator']);

// Loan Sandbox Module
$router->get('/loans/simulator', ['LoanController', 'simulator']);

// Financial Timeline Module
$router->get('/timeline', ['TimelineController', 'index']);
$router->get('/timeline/load-more', ['TimelineController', 'loadMore']);

// Cash Flow Forecast Module
$router->get('/forecast', ['ForecastController', 'index']);
$router->get('/forecast/sandbox', ['ForecastController', 'sandbox']);
$router->post('/forecast/run-sandbox', ['ForecastController', 'runSandbox']);
$router->post('/forecast/save-scenario', ['ForecastController', 'saveScenario']);

// Financial Calendar Module
$router->get('/calendar', ['CalendarController', 'index']);
$router->get('/calendar/events', ['CalendarController', 'events']);
$router->get('/calendar/day-summary', ['CalendarController', 'daySummary']);

// Achievements Module
$router->get('/achievements', ['AchievementController', 'index']);
$router->post('/achievements/prestige', ['AchievementController', 'prestige']);

// Financial Summary Engine API
$router->get('/api/summary/current', ['SummaryController', 'getCurrent']);
$router->post('/api/summary/refresh', ['SummaryController', 'refresh']);

// Recurring Income Module
$router->get('/recurring-incomes', ['RecurringIncomeController', 'index']);
$router->post('/recurring-incomes/store', ['RecurringIncomeController', 'store']);
$router->post('/recurring-incomes/toggle-status/{id}', ['RecurringIncomeController', 'toggleStatus']);
$router->post('/recurring-incomes/skip/{id}', ['RecurringIncomeController', 'skip']);
$router->post('/recurring-incomes/post-now/{id}', ['RecurringIncomeController', 'postNow']);
$router->post('/recurring-incomes/delete/{id}', ['RecurringIncomeController', 'delete']);

// Dispatch request
$router->dispatch();