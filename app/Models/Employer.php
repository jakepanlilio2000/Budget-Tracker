<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class Employer
{
    public static function getAllByUser(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM employers WHERE user_id = ? ORDER BY company_name ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO employers (user_id, company_name, contact_email, contact_phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $data['company_name'], $data['contact_email'] ?? null, $data['contact_phone'] ?? null, $data['address'] ?? null]);
        return (int)$db->lastInsertId();
    }
}