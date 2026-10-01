<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\MathService;

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
        $targetAmount = MathService::parseDecimal((string) ($data['target_amount'] ?? '0.00'));
        $stmt = $db->prepare("INSERT INTO savings_vaults (user_id, name, description, target_amount, current_amount, status) VALUES (?, ?, ?, ?, '0.00', 'active')");
        $stmt->execute([$userId, trim($data['name']), trim($data['description'] ?? '') ?: null, $targetAmount]);
        return (int) $db->lastInsertId();
    }

    public static function updateBalance(int $vaultId, int $userId, string $change): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE savings_vaults SET current_amount = current_amount + ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$change, $vaultId, $userId]);
    }

    public static function updateStatus(int $vaultId, int $userId, string $status): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE savings_vaults SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $vaultId, $userId]);
    }

    public static function calculateMetrics(array $vault, int $userId): array
    {
        $target = MathService::parseDecimal((string) $vault['target_amount']);
        $current = MathService::parseDecimal((string) $vault['current_amount']);
        $targetFloat = (float) $target;
        $currentFloat = (float) $current;
        $percentage = $targetFloat > 0 ? min(100.0, ($currentFloat / $targetFloat) * 100.0) : 0.0;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as net_flow 
            FROM vault_transactions 
            WHERE vault_id = ? AND user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$vault['id'], $userId]);
        $monthlyFlow = (float) $stmt->fetchColumn();

        $remaining = max(0.0, $targetFloat - $currentFloat);
        $estimatedMonths = ($monthlyFlow > 0 && $remaining > 0) ? (int) ceil($remaining / $monthlyFlow) : null;

        return [
            'percentage' => round($percentage, 1),
            'remaining' => $remaining,
            'estimated_months' => $estimatedMonths,
            'is_completed' => MathService::gte($current, $target) && MathService::gt($target, '0.00')
        ];
    }
}