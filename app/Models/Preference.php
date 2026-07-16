<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class Preference
{
    public static function get(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch();

        $defaults = [
            'theme' => 'auto',
            'accent_color' => '#3b82f6',
            'privacy_blur' => 0,
            'zen_mode' => 0,
            'compact_mode' => 0,
            'default_landing_page' => '/dashboard',
            'base_currency_id' => null,
            'dashboard_config' => null
        ];

        if (!$prefs)
            return $defaults;

        if (isset($prefs['dashboard_config']) && is_string($prefs['dashboard_config'])) {
            $prefs['dashboard_config'] = json_decode($prefs['dashboard_config'], true);
        }

        return array_merge($defaults, $prefs);
    }

    public static function save(int $userId, array $data): void
    {
        $db = Database::getInstance()->getConnection();

        $configJson = null;
        if (isset($data['dashboard_config'])) {
            $configJson = is_array($data['dashboard_config']) ? json_encode($data['dashboard_config']) : $data['dashboard_config'];
        }

        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, theme, accent_color, privacy_blur, zen_mode, compact_mode, default_landing_page, base_currency_id, dashboard_config) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            theme = VALUES(theme), accent_color = VALUES(accent_color), privacy_blur = VALUES(privacy_blur), 
            zen_mode = VALUES(zen_mode), compact_mode = VALUES(compact_mode), default_landing_page = VALUES(default_landing_page),
            base_currency_id = VALUES(base_currency_id), dashboard_config = VALUES(dashboard_config)
        ");
        $stmt->execute([
            $userId,
            $data['theme'] ?? 'auto',
            $data['accent_color'] ?? '#3b82f6',
            (int) ($data['privacy_blur'] ?? 0),
            (int) ($data['zen_mode'] ?? 0),
            (int) ($data['compact_mode'] ?? 0),
            $data['default_landing_page'] ?? '/dashboard',
            !empty($data['base_currency_id']) ? (int) $data['base_currency_id'] : null,
            $configJson
        ]);
    }
}