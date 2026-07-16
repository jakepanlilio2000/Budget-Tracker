<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class UserAchievement
{
    public static function getByUser(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, ua.progress, ua.target, ua.unlocked_at 
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
            ORDER BY a.category, a.sort_order
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getRecentlyUnlocked(int $userId, int $limit = 5): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, ua.unlocked_at 
            FROM user_achievements ua
            JOIN achievements a ON ua.achievement_id = a.id
            WHERE ua.user_id = ? AND ua.unlocked_at IS NOT NULL
            ORDER BY ua.unlocked_at DESC LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public static function updateProgress(int $userId, int $achievementId, float $progress, float $target): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO user_achievements (user_id, achievement_id, progress, target, unlocked_at) 
            VALUES (?, ?, ?, ?, CASE WHEN ? >= ? THEN NOW() ELSE NULL END)
            ON DUPLICATE KEY UPDATE 
            progress = VALUES(progress), 
            target = VALUES(target),
            unlocked_at = CASE WHEN VALUES(progress) >= VALUES(target) AND unlocked_at IS NULL THEN NOW() ELSE unlocked_at END
        ");
        $stmt->execute([$userId, $achievementId, $progress, $target, $progress, $target]);
    }
}