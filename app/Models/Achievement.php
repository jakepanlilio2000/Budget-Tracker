<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class Achievement
{
    public static function getAll(): array
    {
        $db = Database::getInstance()->getConnection();
        return $db->query("SELECT * FROM achievements ORDER BY category, sort_order")->fetchAll();
    }
}