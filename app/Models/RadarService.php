<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class RadarService
{
    public static function detectSubscriptions(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT description, total_amount, COUNT(*) as frequency, MAX(transaction_date) as last_date
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL
            AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            GROUP BY description, total_amount
            HAVING frequency >= 2
            ORDER BY frequency DESC, total_amount DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function detectDuplicates(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT t1.id, t1.description, t1.total_amount, t1.transaction_date, COUNT(t2.id) as dup_count
            FROM transactions t1
            JOIN transactions t2 ON t1.total_amount = t2.total_amount 
                AND t1.transaction_date = t2.transaction_date 
                AND t1.id != t2.id
                AND t1.user_id = t2.user_id
            WHERE t1.user_id = ? AND t1.deleted_at IS NULL AND t2.deleted_at IS NULL
            GROUP BY t1.id
            HAVING dup_count >= 1
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getActiveAlerts(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM radar_alerts WHERE user_id = ? AND is_resolved = 0 ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function resolveAlert(int $alertId, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE radar_alerts SET is_resolved = 1, resolved_at = NOW() WHERE id = ? AND user_id = ?");
        return $stmt->execute([$alertId, $userId]);
    }

    public static function createAlert(int $userId, string $type, string $severity, string $title, string $description, ?string $entityType = null, ?int $entityId = null): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM radar_alerts WHERE user_id = ? AND type = ? AND title = ? AND is_resolved = 0");
        $stmt->execute([$userId, $type, $title]);
        if ($stmt->fetch())
            return;

        $insert = $db->prepare("INSERT INTO radar_alerts (user_id, type, severity, title, description, entity_type, entity_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$userId, $type, $severity, $title, $description, $entityType, $entityId]);
        Logger::info("Radar alert created", ['user_id' => $userId, 'type' => $type, 'title' => $title]);
    }
}