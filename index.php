<?php
declare(strict_types=1);

session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('Asia/Manila');
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once 'core/Router.php';

$router = new core\Router();

// --- ROUTING TABLE ---

// --- Authentication ---
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@authenticate');
$router->get('/register', 'AuthController@register');
$router->post('/register', 'AuthController@store');
$router->get('/logout', 'AuthController@logout');

// --- Account Management ---
$router->get('/account', 'AccountController@index');
$router->post('/account/profile', 'AccountController@updateProfile');
$router->post('/account/password', 'AccountController@updatePassword');

// --- Global Command Center & Tools ---
$router->get('/tools/compound', 'GlobalController@compound');
$router->get('/tools/loan', 'GlobalController@loan');
$router->get('/system/security', 'GlobalController@security'); 
$router->get('/system/master-backup', 'GlobalController@masterBackup');
$router->post('/system/master-restore', 'GlobalController@masterRestore');

// Profiles
$router->get('/', 'ProfileController@index');
$router->get('/profile/create', 'ProfileController@create');
$router->post('/profile/create', 'ProfileController@store');
$router->get('/profile/{id}/edit', 'ProfileController@edit');
$router->post('/profile/{id}/edit', 'ProfileController@update');
$router->get('/profile/{id}/delete', 'ProfileController@delete'); 

// Dashboard
$router->get('/dashboard/{profile_id}', 'DashboardController@index');
$router->post('/dashboard/tx/{id}/toggle', 'DashboardController@toggleTx');
$router->post('/dashboard/tx/{id}/amount', 'DashboardController@updateTxAmount');
$router->post('/dashboard/{profile_id}/quick', 'DashboardController@quickAdd');

// Entries
$router->get('/entries/{profile_id}', 'EntryController@index');
$router->get('/entries/{profile_id}/create', 'EntryController@create');
$router->post('/entries/{profile_id}/store', 'EntryController@store');
$router->get('/entries/edit/{id}', 'EntryController@edit');
$router->post('/entries/{id}/update', 'EntryController@update');
$router->post('/entries/{id}/toggle', 'EntryController@toggleActive');
$router->post('/entries/{id}/delete', 'EntryController@delete');

// Categories
$router->get('/categories/{profile_id}', 'CategoryController@index');
$router->post('/categories/{profile_id}/store', 'CategoryController@store');
$router->post('/categories/{profile_id}/reorder', 'CategoryController@reorder');
$router->get('/categories/edit/{id}', 'CategoryController@edit');
$router->post('/categories/{id}/update', 'CategoryController@update');
$router->post('/categories/{id}/delete', 'CategoryController@delete');

// Calculator
$router->get('/calculator/{profile_id}', 'CalculatorController@index');
$router->get('/calculator/{profile_id}/import', 'CalculatorController@importPeriod');

// --- The Vault ---
$router->get('/vault/{profile_id}', 'VaultController@index');
$router->post('/vault/{profile_id}/store', 'VaultController@store');
$router->post('/vault/fund/{id}', 'VaultController@addFunds');
$router->post('/vault/delete/{id}', 'VaultController@delete');

// --- Insights & Radar ---
$router->get('/insights/{profile_id}', 'InsightsController@index');
$router->get('/radar/{profile_id}', 'RadarController@index');

// --- Forecast Sandbox ---
$router->get('/forecast/{profile_id}', 'ForecastController@index');
$router->post('/forecast/{profile_id}/add', 'ForecastController@add');
$router->post('/forecast/{profile_id}/remove', 'ForecastController@remove');
$router->post('/forecast/{profile_id}/clear', 'ForecastController@clear');

// --- Purchases & Shopping Log ---
$router->get('/shopping/{profile_id}', 'ShoppingController@index');
$router->post('/shopping/{profile_id}/store', 'ShoppingController@store');
$router->post('/shopping/delete/{id}', 'ShoppingController@delete');

// --- Income & Revenue Tracker ---
$router->get('/income/{profile_id}', 'IncomeController@index');
$router->post('/income/{profile_id}/store', 'IncomeController@store');
$router->post('/income/delete/{id}', 'IncomeController@delete');

// --- Backups & Export ---
$router->get('/backups/{profile_id}', 'BackupController@index');
$router->get('/backups/{profile_id}/excel', 'BackupController@exportExcel'); 
$router->get('/backups/{profile_id}/json', 'BackupController@exportJson');
$router->post('/backups/{profile_id}/wipe', 'BackupController@wipeData');

// --- Preferences ---
$router->get('/preferences/{profile_id}', 'PreferenceController@index');
$router->post('/preferences/{profile_id}/toggle', 'PreferenceController@toggle');

$url = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$parsedUrl = parse_url($url, PHP_URL_PATH);
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$routePath = str_replace($basePath, '', $parsedUrl);
if ($routePath === '') $routePath = '/';

// Define public routes that don't require login
$publicRoutes = ['/', '/login', '/register'];

if (!isset($_SESSION['user_id']) && !in_array($routePath, $publicRoutes)) {
    header("Location: {$basePath}/login");
    exit;
}

$router->dispatch($url, $method);