<?php
declare(strict_types=1);

session_start();

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

$url = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($url, $method);