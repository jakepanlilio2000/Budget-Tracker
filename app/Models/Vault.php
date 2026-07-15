<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class Vault
{
    public static function getByStatus(int $userId, string $status = 'active'): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM savings_vaults WHERE user_id = ? AND status = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $status]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM savings_vaults WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO savings_vaults (user_id, name, description, target_amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $data['name'], $data['description'] ?? null, $data['target_amount']]);
        return (int)$db->lastInsertId();
    }

    public static function updateBalance(int $vaultId, float $change): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE savings_vaults SET current_amount = current_amount + ? WHERE id = ?");
        $stmt->execute([$change, $vaultId]);
    }

    public static function updateStatus(int $vaultId, string $status): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE savings_vaults SET status = ? WHERE id = ?");
        $stmt->execute([$status, $vaultId]);
    }

    public static function calculateMetrics(array $vault, int $userId): array
    {
        $target = (float)$vault['target_amount'];
        $current = (float)$vault['current_amount'];
        $percentage = $target > 0 ? min(100, ($current / $target) * 100) : 0;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as net_flow 
            FROM vault_transactions 
            WHERE vault_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$vault['id']]);
        $monthlyFlow = (float)$stmt->fetchColumn();
        
        $remaining = max(0, $target - $current);
        $estimatedMonths = ($monthlyFlow > 0 && $remaining > 0) ? ceil($remaining / $monthlyFlow) : null;

        return [
            'percentage' => round($percentage, 1),
            'remaining' => $remaining,
            'estimated_months' => $estimatedMonths,
            'is_completed' => $current >= $target && $target > 0
        ];
    }
}