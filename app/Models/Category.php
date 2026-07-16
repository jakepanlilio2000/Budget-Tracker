<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

class Category
{
    public static function getAllByUser(int $userId, ?string $type = null): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM categories WHERE user_id = ? AND deleted_at IS NULL";
        $params = [$userId];
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY parent_id ASC, name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO categories (user_id, parent_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $data['parent_id'] ?: null, $data['name'], $data['type'], $data['color'], $data['icon']]);
        return (int) $db->lastInsertId();
    }

    public static function getAllByUserWithArchived(int $userId, ?string $type = null): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM categories WHERE user_id = ? AND deleted_at IS NULL";
        $params = [$userId];
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY is_archived ASC, parent_id ASC, name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function toggleArchive(int $id, int $userId, bool $archive): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE categories SET is_archived = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([(int) $archive, $id, $userId]);
    }

    public static function updateDetails(int $id, int $userId, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE categories SET name = ?, type = ?, color = ?, icon = ?, parent_id = ? 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['type'],
            $data['color'],
            $data['icon'],
            $data['parent_id'] ?: null,
            $id,
            $userId
        ]);
    }
    public static function permanentlyDelete(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ? AND user_id = ? AND is_archived = 1");
        return $stmt->execute([$id, $userId]);
    }

    public static function getAllActiveByUser(int $userId, ?string $type = null): array
    {
        $db = Database::getInstance()->getConnection();
        if ($type) {
            $stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? AND type = ? AND is_archived = 0 ORDER BY name ASC");
            $stmt->execute([$userId, $type]);
        } else {
            $stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? AND is_archived = 0 ORDER BY name ASC");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }
    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }
}