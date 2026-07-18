<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class PlanningScenario
{
    public static function getAllByUser(int $userId, bool $includeArchived = false): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id, name, description, is_favorite, is_archived, created_at, updated_at 
                FROM planning_scenarios WHERE user_id = ?";
        if (!$includeArchived) {
            $sql .= " AND is_archived = 0";
        }
        $sql .= " ORDER BY is_favorite DESC, updated_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM planning_scenarios WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, string $name, ?string $description, array $workspaceData): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO planning_scenarios (user_id, name, description, workspace_data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $description, json_encode($workspaceData)]);
        return (int) $db->lastInsertId();
    }

    public static function updateWorkspace(int $id, int $userId, array $workspaceData): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE planning_scenarios SET workspace_data = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([json_encode($workspaceData), $id, $userId]);
    }

    public static function duplicate(int $id, int $userId): ?int
    {
        $scenario = self::findById($id, $userId);
        if (!$scenario)
            return null;

        $db = Database::getInstance()->getConnection();
        $newName = $scenario['name'] . ' (Copy)';
        $stmt = $db->prepare("INSERT INTO planning_scenarios (user_id, name, description, is_favorite, workspace_data) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $newName, $scenario['description'], 0, $scenario['workspace_data']]);
        return (int) $db->lastInsertId();
    }

    public static function toggleArchive(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE planning_scenarios SET is_archived = NOT is_archived WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM planning_scenarios WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}