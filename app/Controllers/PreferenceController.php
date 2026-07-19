<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Preference;
use App\Core\Database;
use App\Core\Cache;

class PreferenceController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $preferences = Preference::get(Auth::id());
        $this->view('preferences.index', ['preferences' => $preferences]);
    }

    public function save(): void
    {
        $this->validateCsrf();
        $data = [
            'theme' => $_POST['theme'] ?? 'auto',
            'accent_color' => $_POST['accent_color'] ?? '#3b82f6',
            'privacy_blur' => isset($_POST['privacy_blur']) ? 1 : 0,
            'zen_mode' => isset($_POST['zen_mode']) ? 1 : 0,
            'compact_mode' => isset($_POST['compact_mode']) ? 1 : 0,
            'default_landing_page' => $_POST['default_landing_page'] ?? '/dashboard',
            'base_currency_id' => !empty($_POST['base_currency_id']) ? (int) $_POST['base_currency_id'] : null
        ];

        Preference::save(Auth::id(), $data);
        Cache::forget("dashboard_stats_" . Auth::id());

        Session::set('success', 'Preferences saved successfully.');
        $this->redirect('/preferences');
    }

    public function updateTheme(): void
    {
        $this->validateCsrf();
        $theme = $_POST['theme'] ?? 'system';

        if (!in_array($theme, ['light', 'dark', 'system', 'auto'])) {
            $this->json(['success' => false, 'error' => 'Invalid theme'], 400);
        }

        $dbTheme = ($theme === 'system') ? 'auto' : $theme;
        $userId = Auth::id();

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, theme) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE theme = VALUES(theme)
        ");
        $stmt->execute([$userId, $dbTheme]);

        $this->json(['success' => true, 'theme' => $theme]);
    }

    public function updatePrivacy(): void
    {
        $this->validateCsrf();
        $blur = isset($_POST['privacy_blur']) && $_POST['privacy_blur'] === '1' ? 1 : 0;
        $userId = Auth::id();

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, privacy_blur) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE privacy_blur = VALUES(privacy_blur)
        ");
        $stmt->execute([$userId, $blur]);

        $this->json(['success' => true, 'privacy_blur' => $blur]);
    }
    public function updateCompact(): void
    {
        $this->validateCsrf();
        $compact = isset($_POST['compact_mode']) && $_POST['compact_mode'] === '1' ? 1 : 0;
        $userId = Auth::id();

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, compact_mode) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE compact_mode = VALUES(compact_mode)
        ");
        $stmt->execute([$userId, $compact]);

        $this->json(['success' => true, 'compact_mode' => $compact]);
    }

    public function updateZen(): void
    {
        $this->validateCsrf();
        $zen = isset($_POST['zen_mode']) && $_POST['zen_mode'] === '1' ? 1 : 0;
        $userId = Auth::id();

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, zen_mode) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE zen_mode = VALUES(zen_mode)
        ");
        $stmt->execute([$userId, $zen]);

        $this->json(['success' => true, 'zen_mode' => $zen]);
    }
}