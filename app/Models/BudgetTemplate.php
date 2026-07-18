<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class BudgetTemplate
{
    public static function getAll(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM budget_templates 
            WHERE is_system = 1 OR user_id = ? 
            ORDER BY is_system DESC, name ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, string $name, string $description, array $allocations): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO budget_templates (user_id, name, description, allocations, is_system) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$userId, $name, $description, json_encode($allocations)]);
        return (int) $db->lastInsertId();
    }

    public static function delete(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM budget_templates WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}