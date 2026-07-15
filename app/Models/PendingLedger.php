<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

class PendingLedger
{
    public static function getUpcoming(int $userId, int $limit = 10): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT pl.*, c.symbol, c.code 
            FROM pending_ledger pl
            JOIN currencies c ON pl.currency_id = c.id
            WHERE pl.user_id = ? AND pl.status = 'pending' AND pl.due_date >= CURRENT_DATE()
            ORDER BY pl.due_date ASC, FIELD(pl.priority, 'critical', 'high', 'medium', 'low') ASC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO pending_ledger (user_id, type, description, amount, currency_id, due_date, priority, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $data['type'], $data['description'], $data['amount'], 
            $data['currency_id'], $data['due_date'], $data['priority'], $data['notes'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function markAsPaid(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE pending_ledger SET status = 'paid' WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}