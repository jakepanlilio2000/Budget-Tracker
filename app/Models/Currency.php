<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Currency
{
    public static function getAll(): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM currencies ORDER BY is_base DESC, code ASC");
        return $stmt->fetchAll();
    }

    public static function getBaseCurrency(): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM currencies WHERE is_base = 1 LIMIT 1");
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM currencies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}