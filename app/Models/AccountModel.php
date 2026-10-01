<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use App\Core\Database;
use App\Core\Logger;
use App\Services\MathService;
use App\Services\RequestValidator;
use App\Exceptions\FinancialException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\InsufficientFundsException;

/**
 * ACID-Compliant Account & Ledger State Model.
 * Implements row-level locking (FOR UPDATE), overdraft guards,
 * and automated balance reconciliation against historical ledger entries.
 */
class AccountModel
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
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Locks and fetches an account row within an active transaction.
     * Prevents race conditions and lost updates during balance mutations.
     */
    public static function findByIdForUpdate(PDO $db, int $id, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT a.*, c.code as currency_code, c.symbol as currency_symbol, c.exchange_rate
            FROM accounts a 
            JOIN currencies c ON a.currency_id = c.id 
            WHERE a.id = ? AND a.user_id = ? AND a.deleted_at IS NULL
            FOR UPDATE
        ");
        $stmt->execute([$id, $userId]);
        $account = $stmt->fetch();

        if (!$account) {
            throw new AuthorizationException("Account #{$id} not found or access denied.");
        }

        if (($account['status'] ?? 'active') !== 'active') {
            throw new FinancialException("Account #{$id} is inactive or archived.");
        }

        return $account;
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $openingBalance = RequestValidator::validateAmount($data['opening_balance'] ?? '0.00', 'opening_balance');
        $currencyId = (int) ($data['currency_id'] ?? 1);
        $allowOverdraft = !empty($data['allow_overdraft']) ? 1 : 0;

        $stmt = $db->prepare("
            INSERT INTO accounts (user_id, currency_id, name, type, institution, account_number, opening_balance, current_balance, allow_overdraft, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $userId,
            $currencyId,
            trim($data['name'] ?? 'Account'),
            $data['type'] ?? 'bank',
            $data['institution'] ?? null,
            $data['account_number'] ?? null,
            $openingBalance,
            $openingBalance,
            $allowOverdraft,
            $data['notes'] ?? null
        ]);

        return (int) $db->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE accounts 
            SET name = ?, type = ?, institution = ?, account_number = ?, notes = ?, currency_id = ?, allow_overdraft = ?
            WHERE id = ? AND user_id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            trim($data['name']),
            $data['type'],
            $data['institution'] ?? null,
            $data['account_number'] ?? null,
            $data['notes'] ?? null,
            (int) $data['currency_id'],
            !empty($data['allow_overdraft']) ? 1 : 0,
            $id,
            $userId
        ]);
    }

    public static function softDelete(int $id, int $userId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE accounts SET deleted_at = NOW(), status = 'archived' WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Atomically mutates account balance within an active PDO transaction.
     * Enforces overdraft policy: Rejects mutations driving balance negative unless allow_overdraft = 1.
     */
    public static function mutateBalance(PDO $db, int $accountId, int $userId, string $deltaAmount, bool $isCredit): string
    {
        $account = self::findByIdForUpdate($db, $accountId, $userId);
        $currentBalance = MathService::parseDecimal((string) $account['current_balance']);
        $validatedDelta = RequestValidator::validateAmount($deltaAmount, 'delta_amount');

        if ($isCredit) {
            $newBalance = MathService::add($currentBalance, $validatedDelta);
        } else {
            $newBalance = MathService::sub($currentBalance, $validatedDelta);
            // Overdraft Check
            if (MathService::lt($newBalance, '0.00')) {
                $allowOverdraft = (bool) ($account['allow_overdraft'] ?? 0);
                if (!$allowOverdraft) {
                    throw new InsufficientFundsException(
                        "Transaction rejected: Insufficient funds in account '{$account['name']}'. " .
                        "Current Balance: {$currentBalance}, Required: {$validatedDelta} (Overdraft disallowed)."
                    );
                }
            }
        }

        $stmt = $db->prepare("UPDATE accounts SET current_balance = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$newBalance, $accountId, $userId]);

        return $newBalance;
    }

    /**
     * Dedicated Reconciliation Engine: Recalculates stored balance directly
     * against verified transaction ledger history.
     */
    public static function reconcileBalance(int $userId, int $accountId, bool $applyCorrection = false): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $account = self::findByIdForUpdate($db, $accountId, $userId);
            $storedBalance = MathService::parseDecimal((string) $account['current_balance']);
            $openingBalance = MathService::parseDecimal((string) $account['opening_balance']);

            // Calculate total income transactions settled on this account
            $stmtInc = $db->prepare("
                SELECT COALESCE(SUM(settled_amount), 0) as total_inc
                FROM transactions
                WHERE account_id = ? AND user_id = ? AND type = 'income' AND status = 'posted' AND deleted_at IS NULL
            ");
            $stmtInc->execute([$accountId, $userId]);
            $totalInc = MathService::parseDecimal((string) $stmtInc->fetchColumn());

            // Calculate total expense transactions settled on this account
            $stmtExp = $db->prepare("
                SELECT COALESCE(SUM(settled_amount), 0) as total_exp
                FROM transactions
                WHERE account_id = ? AND user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL
            ");
            $stmtExp->execute([$accountId, $userId]);
            $totalExp = MathService::parseDecimal((string) $stmtExp->fetchColumn());

            // Ledger True Balance = Opening + Total Income - Total Expense
            $ledgerTrueBalance = MathService::add($openingBalance, $totalInc);
            $ledgerTrueBalance = MathService::sub($ledgerTrueBalance, $totalExp);

            $discrepancy = MathService::sub($storedBalance, $ledgerTrueBalance);
            $isSynchronized = MathService::isZero($discrepancy);

            if (!$isSynchronized && $applyCorrection) {
                $upd = $db->prepare("UPDATE accounts SET current_balance = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $upd->execute([$ledgerTrueBalance, $accountId, $userId]);

                $log = $db->prepare("
                    INSERT INTO reconciliation_audit_logs (user_id, account_id, stored_balance, ledger_balance, discrepancy, reconciled, notes)
                    VALUES (?, ?, ?, ?, ?, 1, ?)
                ");
                $log->execute([
                    $userId,
                    $accountId,
                    $storedBalance,
                    $ledgerTrueBalance,
                    $discrepancy,
                    "Automated ledger reconciliation: corrected drift of {$discrepancy}"
                ]);

                Logger::warning("Account balance reconciled", [
                    'account_id' => $accountId,
                    'user_id' => $userId,
                    'old_stored' => $storedBalance,
                    'reconciled_to' => $ledgerTrueBalance,
                    'discrepancy' => $discrepancy
                ]);
            }

            $db->commit();

            return [
                'account_id' => $accountId,
                'account_name' => $account['name'],
                'stored_balance' => $storedBalance,
                'ledger_true_balance' => $ledgerTrueBalance,
                'discrepancy' => $discrepancy,
                'is_synchronized' => $isSynchronized,
                'corrected' => (!$isSynchronized && $applyCorrection)
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
