<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use App\Core\Database;
use App\Core\Logger;
use App\Services\MathService;
use App\Services\RequestValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\AuthorizationException;

class VaultTransaction
{
    public static function record(int $vaultId, int $userId, string $type, string|float $amount, ?string $notes): bool
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            // Lock vault row FOR UPDATE
            $stmt = $db->prepare("SELECT * FROM savings_vaults WHERE id = ? AND user_id = ? FOR UPDATE");
            $stmt->execute([$vaultId, $userId]);
            $vault = $stmt->fetch();

            if (!$vault) {
                throw new AuthorizationException("Vault not found or access denied.");
            }

            if ($vault['status'] !== 'active') {
                throw new ValidationException("Cannot transact on an inactive vault.");
            }

            $validatedAmount = RequestValidator::validateAmount($amount, 'vault_transaction_amount');
            $currentAmount = MathService::parseDecimal((string) $vault['current_amount']);
            $targetAmount = MathService::parseDecimal((string) $vault['target_amount']);

            if ($type === 'withdrawal') {
                if (MathService::gt($validatedAmount, $currentAmount)) {
                    throw new InsufficientFundsException("Insufficient funds in vault for withdrawal. Current: {$currentAmount}, Requested: {$validatedAmount}");
                }
                $newAmount = MathService::sub($currentAmount, $validatedAmount);
            } elseif ($type === 'deposit') {
                $newAmount = MathService::add($currentAmount, $validatedAmount);
            } else {
                throw new ValidationException("Invalid transaction type: {$type}");
            }

            $stmtTxn = $db->prepare("INSERT INTO vault_transactions (vault_id, user_id, type, amount, notes) VALUES (?, ?, ?, ?, ?)");
            $stmtTxn->execute([$vaultId, $userId, $type, $validatedAmount, trim($notes ?? '') ?: null]);

            $newStatus = $vault['status'];
            if (MathService::gte($newAmount, $targetAmount) && MathService::gt($targetAmount, '0.00')) {
                $newStatus = 'completed';
            }

            $stmtUpd = $db->prepare("UPDATE savings_vaults SET current_amount = ?, status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmtUpd->execute([$newAmount, $newStatus, $vaultId, $userId]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("Vault transaction failed", ['vault_id' => $vaultId, 'user_id' => $userId, 'error' => $e->getMessage()]);
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