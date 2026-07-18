<?php
declare(strict_types=1);

use App\Core\Session;

if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if (str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -7);
    }

    define('BASE_URL', rtrim($scriptDir, '/') . '/');
}

function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    if (strpos($url, 'http') !== 0 && strpos($url, '/') === 0) {
        $url = BASE_URL . ltrim($url, '/');
    }
    header("Location: {$url}");
    exit;
}

function asset(string $path): string
{
    return BASE_URL . 'assets/' . ltrim($path, '/');
}

function is_logged_in(): bool
{
    return !empty(Session::get('user_id'));
}

function old(string $key, mixed $default = ''): mixed
{
    $flash = Session::get('old_input', []);
    return $flash[$key] ?? $default;
}

if (!function_exists('request_is')) {
    function request_is(string $pattern): bool
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        $path = str_replace($basePath, '', $requestUri);
        $path = '/' . ltrim($path, '/');

        if (str_ends_with($pattern, '*')) {
            $pattern = rtrim($pattern, '*');
            return str_starts_with($path, $pattern);
        }

        return $path === $pattern || $path === $pattern . '/';
    }
}

function url(string $path): string
{
    return BASE_URL . ltrim($path, '/');
}
function hasRole(string $role): bool
{
    $userRole = Session::get('user_role');
    if ($userRole === 'admin')
        return true;
    return $userRole === $role;
}

/**
 * Adjusts the brightness of a hex color.
 * @param string $hex Hex color code (e.g., '#3b82f6')
 * @param int $steps Negative for darker, positive for lighter (-255 to 255)
 * @return string Adjusted hex color code
 */
function adjust_color_brightness(string $hex, int $steps): string
{
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }

    $steps = max(-255, min(255, $steps));
    $color_parts = str_split($hex, 2);
    $return = '#';

    foreach ($color_parts as $color) {
        $color = hexdec($color);
        $color = max(0, min(255, $color + $steps));
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
    }
    return $return;
}

/**
 * Returns an array of all available modules that can be set as a landing page.
 * @return array [route => display_name]
 */
function getAvailableLandingPages(): array
{
    return [
        '/dashboard' => 'Dashboard',
        '/transactions' => 'Transactions',
        '/accounts' => 'Accounts',
        '/budgets' => 'Budgets',
        '/reports' => 'Reports',
        '/bills' => 'Bills & Recurring',
        '/salaries' => 'Salaries & Payslips',
        '/analytics' => 'Analytics & Insights',
        '/categories' => 'Categories',
        '/pending-ledger' => 'Pending Ledger',
        '/daily-logs' => 'Daily Logs',
        '/preferences' => 'Preferences',
        '/profile' => 'Profile Settings',
    ];
}

if (!function_exists('base_currency_symbol')) {
    function base_currency_symbol(?int $userId = null): string
    {
        if (!$userId) {
            $userId = \App\Core\Auth::id();
        }
        if (!$userId) {
            return '$';
        }

        try {
            return \App\Models\CurrencyService::getUserBaseCurrency($userId)['symbol'] ?? '$';
        } catch (\Exception $e) {
            return '$';
        }
    }
}