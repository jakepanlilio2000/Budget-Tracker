<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class VaultTransaction
{
    public static function record(int $vaultId, int $userId, string $type, float $amount, ?string $notes): bool
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO vault_transactions (vault_id, user_id, type, amount, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$vaultId, $userId, $type, $amount, $notes]);
            
            $change = ($type === 'deposit') ? $amount : -$amount;
            Vault::updateBalance($vaultId, $change);
            $vault = Vault::findById($vaultId, $userId);
            if ((float)$vault['current_amount'] >= (float)$vault['target_amount'] && $vault['status'] === 'active') {
                Vault::updateStatus($vaultId, 'completed');
            }
            
            $db->commit();
            return true;
        } catch (\PDOException $e) {
            $db->rollBack();
            \App\Core\Logger::error("Vault transaction failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function getTimeline(int $vaultId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM vault_transactions WHERE vault_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$vaultId]);
        return $stmt->fetchAll();
    }
}