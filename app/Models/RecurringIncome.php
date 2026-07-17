<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class RecurringIncome
{
    public static function getAllByUser(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT ri.*, c.name as category_name, a.name as account_name, cur.symbol as currency_symbol 
            FROM recurring_incomes ri
            LEFT JOIN categories c ON ri.category_id = c.id
            LEFT JOIN accounts a ON ri.account_id = a.id
            LEFT JOIN currencies cur ON ri.currency_id = cur.id
            WHERE ri.user_id = ? ORDER BY ri.next_post_date ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM recurring_incomes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO recurring_incomes 
            (user_id, name, amount, currency_id, account_id, category_id, frequency, custom_interval_days, start_date, end_date, next_post_date, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $data['name'],
            $data['amount'],
            $data['currency_id'],
            $data['account_id'],
            $data['category_id'] ?? null,
            $data['frequency'],
            $data['custom_interval_days'] ?? null,
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['next_post_date'],
            $data['notes'] ?? null
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE recurring_incomes SET 
            name = ?, amount = ?, currency_id = ?, account_id = ?, category_id = ?, 
            frequency = ?, custom_interval_days = ?, start_date = ?, end_date = ?, 
            next_post_date = ?, notes = ? 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['amount'],
            $data['currency_id'],
            $data['account_id'],
            $data['category_id'] ?? null,
            $data['frequency'],
            $data['custom_interval_days'] ?? null,
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['next_post_date'],
            $data['notes'] ?? null,
            $id,
            $userId
        ]);
    }

    public static function toggleStatus(int $id, int $userId, string $status): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE recurring_incomes SET status = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$status, $id, $userId]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM recurring_incomes WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}