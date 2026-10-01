<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use App\Services\TimelineService;

class Account
{
    public static function getAllByUser(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, c.code as currency_code, c.symbol as currency_symbol 
            FROM accounts a 
            JOIN currencies c ON a.currency_id = c.id 
            WHERE a.user_id = ? AND a.deleted_at IS NULL 
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, c.code as currency_code, c.symbol as currency_symbol 
            FROM accounts a 
            JOIN currencies c ON a.currency_id = c.id 
            WHERE a.id = ? AND a.user_id = ? AND a.deleted_at IS NULL
        ");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO accounts (user_id, currency_id, name, type, institution, account_number, opening_balance, current_balance, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $data['currency_id'],
            $data['name'],
            $data['type'],
            $data['institution'] ?? null,
            $data['account_number'] ?? null,
            $data['opening_balance'],
            $data['opening_balance'],
            $data['notes'] ?? null,
            'active'
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE accounts SET name = ?, type = ?, institution = ?, account_number = ?, notes = ?, currency_id = ? 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['type'],
            $data['institution'] ?? null,
            $data['account_number'] ?? null,
            $data['notes'] ?? null,
            $data['currency_id'],
            $id,
            $userId
        ]);
    }

    public static function softDelete(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE accounts SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function adjustBalance(int $id, int $userId, float|string $newBalance, string $reason, int $currencyId): bool
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                SELECT current_balance, name, allow_overdraft 
                FROM accounts 
                WHERE id = ? AND user_id = ? AND deleted_at IS NULL 
                FOR UPDATE
            ");
            $stmt->execute([$id, $userId]);
            $account = $stmt->fetch();

            if (!$account) {
                throw new \Exception("Account not found");
            }

            $currentBalance = \App\Services\MathService::parseDecimal((string) $account['current_balance']);
            $targetBalance = \App\Services\MathService::parseDecimal((string) $newBalance);
            $difference = \App\Services\MathService::sub($targetBalance, $currentBalance);

            if (\App\Services\MathService::isZero($difference)) {
                $db->rollBack();
                return true;
            }

            $isIncome = \App\Services\MathService::gt($difference, '0.00');
            $type = $isIncome ? 'income' : 'expense';
            $amount = \App\Services\MathService::abs($difference);

            if (!$isIncome && \App\Services\MathService::lt($targetBalance, '0.00') && empty($account['allow_overdraft'])) {
                throw new \App\Exceptions\InsufficientFundsException("Balance adjustment rejected: Overdraft is disallowed on this account.");
            }

            $description = "Balance Adjustment: " . substr($reason, 0, 200);

            $txnStmt = $db->prepare("
                INSERT INTO transactions (
                    user_id, account_id, type, total_amount, currency_id, 
                    rate_applied, original_currency_id, base_currency_id, settled_amount,
                    transaction_date, status, description, notes
                ) VALUES (?, ?, ?, ?, ?, '1.000000', ?, ?, ?, CURRENT_DATE, 'posted', ?, ?)
            ");
            $txnStmt->execute([
                $userId, $id, $type, $amount, $currencyId, 
                $currencyId, $currencyId, $amount, 
                $description, $reason
            ]);
            $txnId = (int) $db->lastInsertId();

            $updateStmt = $db->prepare("UPDATE accounts SET current_balance = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $updateStmt->execute([$targetBalance, $id, $userId]);

            $db->commit();
            TimelineService::logEvent(
                'accounts',
                'balance_adjusted',
                $description,
                (float) $amount,
                $currencyId,
                $id,
                null,
                $txnId,
                'fa-sliders-h',
                '#f59e0b'
            );

            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("Account balance adjustment failed", ['error' => $e->getMessage()]);
            return false;
        }
    }
}