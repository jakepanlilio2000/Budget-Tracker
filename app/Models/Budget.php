<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

class Budget
{
    public static function getMonthlyByUser(int $userId, string $month): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT b.*, c.name as category_name, c.color, c.icon,
                   COALESCE(SUM(ts.amount), 0) as spent_amount
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN transaction_splits ts ON ts.category_id = c.id
            LEFT JOIN transactions t ON t.id = ts.transaction_id 
                AND t.user_id = b.user_id 
                AND t.type = 'expense' 
                AND t.status = 'posted' 
                AND t.deleted_at IS NULL
                AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            WHERE b.user_id = ? AND b.month = ?
            GROUP BY b.id, c.name, c.color, c.icon
            ORDER BY c.name ASC
        ");
        $stmt->execute([$month, $userId, $month]);
        return $stmt->fetchAll();
    }

    public static function upsert(int $userId, int $categoryId, string $month, float $amount): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO budgets (user_id, category_id, month, amount) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE amount = VALUES(amount)
        ");
        return $stmt->execute([$userId, $categoryId, $month, $amount]);
    }
}