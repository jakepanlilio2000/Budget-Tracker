<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;

class Account
{
    public static function getAllByUser(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, c.code as currency_code, c.symbol as currency_symbol 
            FROM accounts a 
            JOIN currencies c ON a.currency_id = c.id 
            WHERE a.user_id = ? AND a.deleted_at IS NULL 
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, c.code as currency_code, c.symbol as currency_symbol 
            FROM accounts a 
            JOIN currencies c ON a.currency_id = c.id 
            WHERE a.id = ? AND a.user_id = ? AND a.deleted_at IS NULL
        ");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO accounts (user_id, currency_id, name, type, institution, account_number, opening_balance, current_balance, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $data['currency_id'],
            $data['name'],
            $data['type'],
            $data['institution'] ?? null,
            $data['account_number'] ?? null,
            $data['opening_balance'],
            $data['opening_balance'], // Initial current balance equals opening
            $data['notes'] ?? null,
            'active'
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE accounts SET name = ?, type = ?, institution = ?, account_number = ?, notes = ?, currency_id = ? 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['type'],
            $data['institution'] ?? null,
            $data['account_number'] ?? null,
            $data['notes'] ?? null,
            $data['currency_id'],
            $id,
            $userId
        ]);
    }

    public static function softDelete(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE accounts SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}