<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\TransactionModel;
use App\Exceptions\FinancialException;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthorizationException;

/**
 * Service Layer for Transaction and Transfer Execution.
 * Encapsulates multi-currency validation, arbitrary-precision split verification,
 * ACID ledger mutations, and event-driven reversal workflows.
 */
class TransactionService
{
    /**
     * Create a standard or split transaction with strict balance synchronization.
     *
     * @param int $userId Authenticated user ID
     * @param array $txnData Transaction header data (account_id, type, total_amount, currency_id, transaction_date, etc.)
     * @param array $splits Split items (category_id, amount, notes, etc.)
     * @param string|null $clientMutationId Optional idempotency UUID
     * @return array Created transaction details and updated account balance
     */
    public function createTransaction(int $userId, array $txnData, array $splits = [], ?string $clientMutationId = null): array
    {
        return TransactionModel::createWithSplits($userId, $txnData, $splits, $clientMutationId);
    }

    /**
     * Execute an ACID-compliant fund transfer between two accounts with fee tracking.
     *
     * @param int $userId Authenticated user ID
     * @param int $fromAccountId Source account ID
     * @param int $toAccountId Destination account ID
     * @param string $amount Transfer amount in source currency (decimal string)
     * @param string $fee Transfer fee in source currency (decimal string, default '0.00')
     * @param string|null $date ISO date string (Y-m-d)
     * @param string|null $notes Optional transfer memo
     * @param string|null $clientMutationId Optional idempotency key
     * @return array Transfer receipt including debit/credit transactions
     */
    public function executeTransfer(
        int $userId,
        int $fromAccountId,
        int $toAccountId,
        string $amount,
        string $fee = '0.00',
        ?string $date = null,
        ?string $notes = null,
        ?string $clientMutationId = null
    ): array {
        return TransactionModel::transfer(
            $userId,
            $fromAccountId,
            $toAccountId,
            $amount,
            $fee,
            $date,
            $notes,
            $clientMutationId
        );
    }

    /**
     * Atomically reverse a transaction, restoring account balance and clawing back gamification rewards.
     *
     * @param int $userId Authenticated user ID
     * @param int $transactionId ID of the transaction to reverse
     * @param string $reason Audit log reason for reversal
     * @return array Reversal confirmation and adjusted balance
     */
    public function reverseTransaction(int $userId, int $transactionId, string $reason = 'User requested reversal'): array
    {
        return TransactionModel::reverseTransaction($userId, $transactionId, $reason);
    }

    /**
     * Retrieve filtered transaction history for a user.
     *
     * @param int $userId Authenticated user ID
     * @param array $filters Query filters (start_date, end_date, account_id, category_id, type, limit, offset)
     * @return array List of transactions
     */
    public function getTransactionHistory(int $userId, array $filters = []): array
    {
        return TransactionModel::getTransactions($userId, $filters);
    }

    /**
     * Retrieve a single transaction with ownership verification.
     *
     * @param int $id Transaction ID
     * @param int $userId Authenticated user ID
     * @return array|null Transaction record or null
     */
    public function getTransactionDetails(int $id, int $userId): ?array
    {
        return TransactionModel::findById($id, $userId);
    }
}
