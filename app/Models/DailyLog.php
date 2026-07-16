<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

class DailyLog
{
    public static function getRecent(int $userId, int $limit = 14): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM daily_logs 
            WHERE user_id = ? 
            ORDER BY log_date DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public static function upsert(int $userId, string $date, float $totalSpent, ?string $mood, ?string $notes): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO daily_logs (user_id, log_date, total_spent, mood_context, notes) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            total_spent = VALUES(total_spent), 
            mood_context = VALUES(mood_context), 
            notes = VALUES(notes)
        ");
        return $stmt->execute([$userId, $date, $totalSpent, $mood, $notes]);
    }
}