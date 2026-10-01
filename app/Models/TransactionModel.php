<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use DateTimeImmutable;
use App\Core\Database;
use App\Core\Logger;
use App\Services\MathService;
use App\Services\RequestValidator;
use App\Exceptions\FinancialException;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthorizationException;

/**
 * ACID-Compliant Transaction & Multi-Currency Ledger Engine.
 * Enforces atomic state transitions, row-level locking, invariant split checks,
 * rate snapshotting, and event-driven gamification tracking with reversal rollbacks.
 */
class TransactionModel
{
    /**
     * Creates a transaction with splits inside an explicit PDO transaction.
     * Enforces row locking, rate snapshotting, overdraft checks, and idempotent XP awards.
     */
    public static function createWithSplits(int $userId, array $txnData, array $splits, ?string $clientMutationId = null): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            // 1. Idempotency Check
            if (!empty($clientMutationId)) {
                RequestValidator::checkIdempotency($db, $userId, $clientMutationId, '/transactions/store', [
                    'txn' => $txnData,
                    'splits' => $splits
                ]);
            }

            // 2. Validate & Lock Account (FOR UPDATE)
            $accountId = (int) ($txnData['account_id'] ?? 0);
            $account = AccountModel::findByIdForUpdate($db, $accountId, $userId);

            // 3. Validate Inputs
            $type = $txnData['type'] ?? 'expense';
            if (!in_array($type, ['income', 'expense', 'transfer'], true)) {
                throw new ValidationException("Invalid transaction type: {$type}", ['type' => 'Must be income, expense, or transfer']);
            }

            $status = $txnData['status'] ?? 'posted';
            if (!in_array($status, ['draft', 'posted', 'archived'], true)) {
                throw new ValidationException("Invalid status: {$status}", ['status' => 'Invalid status value']);
            }

            $txnDate = RequestValidator::validateDate($txnData['transaction_date'] ?? date('Y-m-d'), 'transaction_date', $status === 'draft');
            $totalAmount = RequestValidator::validateAmount($txnData['total_amount'] ?? '0.00', 'total_amount');
            $currencyId = (int) ($txnData['currency_id'] ?? $account['currency_id']);

            if (empty($txnData['description'])) {
                throw new ValidationException("Transaction description is required", ['description' => 'Description is required']);
            }

            // 4. Validate Splits & Invariant Verification
            if (empty($splits)) {
                throw new ValidationException("At least one split category is required", ['splits' => 'Splits cannot be empty']);
            }

            $splitAmounts = [];
            $validatedSplits = [];
            foreach ($splits as $index => $split) {
                $catId = (int) ($split['category_id'] ?? 0);
                RequestValidator::verifyCategoryOwnership($db, $catId, $userId);
                $splitAmt = RequestValidator::validateAmount($split['amount'] ?? '0.00', "split_amount_{$index}");
                $splitAmounts[] = $splitAmt;
                $validatedSplits[] = [
                    'category_id' => $catId,
                    'amount' => $splitAmt,
                    'notes' => trim($split['notes'] ?? '')
                ];
            }

            // Invariant: sum(splits) == total_amount down to the exact cent
            if (!MathService::verifyInvariant($splitAmounts, [$totalAmount])) {
                $sumSplits = MathService::sum($splitAmounts);
                throw new ValidationException(
                    "Split balance invariant violated: Sum of splits ({$sumSplits}) does not match total amount ({$totalAmount})",
                    ['splits' => 'Sum of splits must equal total amount']
                );
            }

            // 5. Multi-Currency Rate Snapshotting
            $accountCurrencyId = (int) $account['currency_id'];
            $rateApplied = '1.000000';
            $settledAmount = $totalAmount;

            // Fetch user base currency
            $stmtBase = $db->prepare("
                SELECT c.id, c.exchange_rate 
                FROM currencies c 
                JOIN user_preferences up ON c.id = up.base_currency_id 
                WHERE up.user_id = ?
            ");
            $stmtBase->execute([$userId]);
            $baseCurr = $stmtBase->fetch() ?: ['id' => $accountCurrencyId, 'exchange_rate' => '1.000000'];
            $baseCurrencyId = (int) $baseCurr['id'];

            if ($currencyId !== $accountCurrencyId) {
                // Fetch rates for both
                $stmtRate = $db->prepare("SELECT id, exchange_rate FROM currencies WHERE id IN (?, ?)");
                $stmtRate->execute([$currencyId, $accountCurrencyId]);
                $rates = $stmtRate->fetchAll(PDO::FETCH_KEY_PAIR);

                $fromRate = (string) ($rates[$currencyId] ?? '1.000000');
                $toRate = (string) ($rates[$accountCurrencyId] ?? '1.000000');

                $rateApplied = MathService::div($toRate, $fromRate, MathService::SCALE_RATE);
                $settledAmount = MathService::convertCross($totalAmount, $fromRate, $toRate, MathService::SCALE_MONEY);
            }

            // 6. Mutate Account Balance if posted
            $newBalance = (string) $account['current_balance'];
            if ($status === 'posted') {
                $isCredit = ($type === 'income');
                $newBalance = AccountModel::mutateBalance($db, $accountId, $userId, $settledAmount, $isCredit);
            }

            // 7. Insert Transaction Record with Complete Rate Snapshot
            $primaryCategoryId = $validatedSplits[0]['category_id'];
            $stmtTxn = $db->prepare("
                INSERT INTO transactions (
                    user_id, account_id, category_id, type, total_amount, currency_id, 
                    rate_applied, original_currency_id, base_currency_id, settled_amount,
                    client_mutation_id, transaction_date, status, description, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtTxn->execute([
                $userId,
                $accountId,
                $primaryCategoryId,
                $type,
                $totalAmount,
                $currencyId,
                $rateApplied,
                $currencyId,
                $baseCurrencyId,
                $settledAmount,
                $clientMutationId,
                $txnDate,
                $status,
                trim($txnData['description']),
                trim($txnData['notes'] ?? '')
            ]);
            $txnId = (int) $db->lastInsertId();

            // 8. Insert Splits
            $stmtSplit = $db->prepare("
                INSERT INTO transaction_splits (transaction_id, category_id, amount, notes)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($validatedSplits as $s) {
                $stmtSplit->execute([$txnId, $s['category_id'], $s['amount'], $s['notes'] ?: null]);
            }

            // 9. Event-Driven, Idempotent Gamification (FXP)
            $xpAwarded = 0;
            $actionType = ($type === 'income') ? 'record_income' : 'record_expense';
            $refId = "txn_{$txnId}";

            // Look up XP value
            $stmtFxp = $db->prepare("SELECT xp_value FROM fxp_actions WHERE action_type = ? AND is_active = 1");
            $stmtFxp->execute([$actionType]);
            $baseXp = (int) ($stmtFxp->fetchColumn() ?: 5);

            $stmtEvent = $db->prepare("
                INSERT IGNORE INTO gamification_events (user_id, event_type, reference_id, xp_awarded)
                VALUES (?, ?, ?, ?)
            ");
            $stmtEvent->execute([$userId, $actionType, $refId, $baseXp]);

            if ($stmtEvent->rowCount() > 0) {
                $xpAwarded = $baseXp;
                self::incrementUserFxp($db, $userId, $xpAwarded);
            }

            // 10. Store Idempotency Response
            $response = [
                'success' => true,
                'transaction_id' => $txnId,
                'account_id' => $accountId,
                'settled_amount' => $settledAmount,
                'new_balance' => $newBalance,
                'xp_awarded' => $xpAwarded
            ];

            if (!empty($clientMutationId)) {
                RequestValidator::storeIdempotencyResponse($db, $userId, $clientMutationId, 201, $response);
            }

            $db->commit();

            // Dispatch Decoupled Domain Event
            \App\Services\AchievementEngine::dispatch(
                \App\Events\LedgerEvent::CREATED,
                $userId,
                ['transaction_id' => $txnId, 'amount' => $totalAmount, 'type' => $type]
            );

            return $response;

        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("Transaction creation failed", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Executes an ACID-compliant fund transfer between two accounts.
     * Prevents deadlocks by sorting account IDs before acquiring row locks (FOR UPDATE).
     */
    public static function transferFunds(
        int $userId,
        int $sourceAccountId,
        int $destAccountId,
        string $amount,
        string $fee = '0.00',
        ?string $date = null,
        ?string $description = null,
        ?string $clientMutationId = null
    ): array {
        if ($sourceAccountId === $destAccountId) {
            throw new ValidationException("Source and destination accounts must be distinct", ['destination_account' => 'Accounts must differ']);
        }

        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            if (!empty($clientMutationId)) {
                RequestValidator::checkIdempotency($db, $userId, $clientMutationId, '/accounts/transfer', [
                    'source' => $sourceAccountId,
                    'destination' => $destAccountId,
                    'amount' => $amount,
                    'fee' => $fee
                ]);
            }

            $transferAmount = RequestValidator::validateAmount($amount, 'transfer_amount');
            $transferFee = MathService::parseDecimal($fee);
            $transferDate = RequestValidator::validateDate($date ?? date('Y-m-d'), 'transfer_date');
            $transferDesc = trim($description ?? "Transfer between accounts");

            // Sort IDs to prevent database deadlock
            $firstId = min($sourceAccountId, $destAccountId);
            $secondId = max($sourceAccountId, $destAccountId);

            $stmtLock = $db->prepare("
                SELECT a.*, c.code as currency_code, c.exchange_rate 
                FROM accounts a 
                JOIN currencies c ON a.currency_id = c.id 
                WHERE a.id IN (?, ?) AND a.user_id = ? AND a.deleted_at IS NULL
                ORDER BY a.id ASC
                FOR UPDATE
            ");
            $stmtLock->execute([$firstId, $secondId, $userId]);
            $lockedAccounts = $stmtLock->fetchAll();

            $accountMap = [];
            foreach ($lockedAccounts as $acc) {
                $accountMap[(int) $acc['id']] = $acc;
            }

            if (!isset($accountMap[$sourceAccountId]) || !isset($accountMap[$destAccountId])) {
                throw new AuthorizationException("One or both accounts do not exist or access is forbidden.");
            }

            $sourceAccount = $accountMap[$sourceAccountId];
            $destAccount = $accountMap[$destAccountId];

            // Calculate currency conversion for transfer
            $srcCurrId = (int) $sourceAccount['currency_id'];
            $dstCurrId = (int) $destAccount['currency_id'];

            $creditAmount = $transferAmount;
            $rateApplied = '1.000000';

            if ($srcCurrId !== $dstCurrId) {
                $srcRate = (string) $sourceAccount['exchange_rate'];
                $dstRate = (string) $destAccount['exchange_rate'];
                $creditAmount = MathService::convertCross($transferAmount, $srcRate, $dstRate, MathService::SCALE_MONEY);
                $rateApplied = MathService::div($dstRate, $srcRate, MathService::SCALE_RATE);
            }

            // Invariant verification: Debit = Credit (in source currency) + Fee
            $totalDebit = MathService::add($transferAmount, $transferFee);

            // Mutate Source Account (Debit: transfer amount + fee)
            $newSourceBalance = AccountModel::mutateBalance($db, $sourceAccountId, $userId, $totalDebit, false);

            // Mutate Destination Account (Credit: converted transfer amount)
            $newDestBalance = AccountModel::mutateBalance($db, $destAccountId, $userId, $creditAmount, true);

            // Record Transfer Out
            $stmtOut = $db->prepare("
                INSERT INTO transactions (
                    user_id, account_id, type, total_amount, currency_id, rate_applied, 
                    original_currency_id, base_currency_id, settled_amount, transaction_date, 
                    status, description, notes
                ) VALUES (?, ?, 'expense', ?, ?, '1.000000', ?, ?, ?, ?, 'posted', ?, ?)
            ");
            $stmtOut->execute([
                $userId,
                $sourceAccountId,
                $totalDebit,
                $srcCurrId,
                $srcCurrId,
                $srcCurrId,
                $totalDebit,
                $transferDate,
                "Transfer to {$destAccount['name']}: {$transferDesc}",
                "Fee: {$transferFee}"
            ]);

            // Record Transfer In
            $stmtIn = $db->prepare("
                INSERT INTO transactions (
                    user_id, account_id, type, total_amount, currency_id, rate_applied, 
                    original_currency_id, base_currency_id, settled_amount, transaction_date, 
                    status, description, notes
                ) VALUES (?, ?, 'income', ?, ?, ?, ?, ?, ?, ?, 'posted', ?, ?)
            ");
            $stmtIn->execute([
                $userId,
                $destAccountId,
                $creditAmount,
                $dstCurrId,
                $rateApplied,
                $srcCurrId,
                $dstCurrId,
                $creditAmount,
                $transferDate,
                "Transfer from {$sourceAccount['name']}: {$transferDesc}",
                "Original: {$transferAmount} {$sourceAccount['currency_code']}"
            ]);

            $response = [
                'success' => true,
                'source_account_id' => $sourceAccountId,
                'source_new_balance' => $newSourceBalance,
                'dest_account_id' => $destAccountId,
                'dest_new_balance' => $newDestBalance,
                'debit_amount' => $totalDebit,
                'credit_amount' => $creditAmount
            ];

            if (!empty($clientMutationId)) {
                RequestValidator::storeIdempotencyResponse($db, $userId, $clientMutationId, 200, $response);
            }

            $db->commit();
            return $response;

        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("Transfer failed", ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Reverses or soft-deletes a transaction with full ledger integrity.
     * Reverses account balance changes and rolls back earned XP / badges.
     */
    public static function reverseTransaction(int $userId, int $txnId, string $reason = ''): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $stmtTxn = $db->prepare("
                SELECT * FROM transactions 
                WHERE id = ? AND user_id = ? AND deleted_at IS NULL 
                FOR UPDATE
            ");
            $stmtTxn->execute([$txnId, $userId]);
            $txn = $stmtTxn->fetch();

            if (!$txn) {
                throw new AuthorizationException("Transaction #{$txnId} not found or already deleted.");
            }

            $accountId = (int) $txn['account_id'];
            $settledAmount = (string) $txn['settled_amount'];
            $type = $txn['type'];
            $status = $txn['status'];

            // Reverse balance update if transaction was posted
            $newBalance = null;
            if ($status === 'posted') {
                // If it was income, reverse by debiting; if expense, reverse by crediting
                $isCreditOnReversal = ($type === 'expense');
                $newBalance = AccountModel::mutateBalance($db, $accountId, $userId, $settledAmount, $isCreditOnReversal);
            }

            // Soft delete transaction
            $stmtDel = $db->prepare("UPDATE transactions SET deleted_at = NOW(), notes = CONCAT(COALESCE(notes, ''), ' [Reversed: ', ?, ']') WHERE id = ?");
            $stmtDel->execute([trim($reason ?: 'Manual Reversal'), $txnId]);

            // Gamification Clawback: Reverses XP earned on this transaction
            $refId = "txn_{$txnId}";
            $stmtEvent = $db->prepare("
                SELECT id, xp_awarded 
                FROM gamification_events 
                WHERE user_id = ? AND reference_id = ? AND is_reversed = 0
                FOR UPDATE
            ");
            $stmtEvent->execute([$userId, $refId]);
            $event = $stmtEvent->fetch();

            $xpClawedBack = 0;
            if ($event) {
                $xpClawedBack = (int) $event['xp_awarded'];
                $db->prepare("UPDATE gamification_events SET is_reversed = 1, reversed_at = NOW() WHERE id = ?")->execute([$event['id']]);
                self::decrementUserFxp($db, $userId, $xpClawedBack);
            }

            $db->commit();

            // Dispatch Decoupled Domain Event for Gamification Clawback
            \App\Services\AchievementEngine::dispatch(
                \App\Events\LedgerEvent::REVERSED,
                $userId,
                ['transaction_id' => $txnId]
            );

            Logger::info("Transaction reversed successfully", [
                'txn_id' => $txnId,
                'user_id' => $userId,
                'settled_amount' => $settledAmount,
                'xp_clawed_back' => $xpClawedBack
            ]);

            return [
                'success' => true,
                'transaction_id' => $txnId,
                'reversed_amount' => $settledAmount,
                'new_account_balance' => $newBalance,
                'xp_clawed_back' => $xpClawedBack
            ];

        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("Transaction reversal failed", ['txn_id' => $txnId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public static function getRecent(int $userId, int $limit = 50): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT t.*, a.name as account_name, c.code as currency_code, c.symbol as currency_symbol 
            FROM transactions t
            JOIN accounts a ON t.account_id = a.id
            JOIN currencies c ON t.currency_id = c.id
            WHERE t.user_id = ? AND t.deleted_at IS NULL
            ORDER BY t.transaction_date DESC, t.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function incrementUserFxp(PDO $db, int $userId, int $xp): void
    {
        if ($xp <= 0) return;

        $stmt = $db->prepare("SELECT lifetime_fxp, current_level FROM user_fxp_stats WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();

        $currentFxp = (int) ($stats['lifetime_fxp'] ?? 0);
        $newFxp = $currentFxp + $xp;
        $newLevel = (int) floor(pow($newFxp / 100, 2 / 3)) + 1;

        $db->prepare("
            INSERT INTO user_fxp_stats (user_id, lifetime_fxp, current_level)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE lifetime_fxp = VALUES(lifetime_fxp), current_level = VALUES(current_level)
        ")->execute([$userId, $newFxp, $newLevel]);
    }

    private static function decrementUserFxp(PDO $db, int $userId, int $xp): void
    {
        if ($xp <= 0) return;

        $stmt = $db->prepare("SELECT lifetime_fxp, current_level FROM user_fxp_stats WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();

        if (!$stats) return;

        $currentFxp = (int) $stats['lifetime_fxp'];
        $newFxp = max(0, $currentFxp - $xp);
        $newLevel = (int) floor(pow($newFxp / 100, 2 / 3)) + 1;

        $db->prepare("
            UPDATE user_fxp_stats 
            SET lifetime_fxp = ?, current_level = ? 
            WHERE user_id = ?
        ")->execute([$newFxp, $newLevel, $userId]);
    }
}
