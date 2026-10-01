<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AccountModel;
use App\Exceptions\FinancialException;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthorizationException;

/**
 * Service Layer for Account Management & Ledger Balance Reconciliation.
 * Enforces ACID row locking, overdraft policy checks, and automated
 * balance recalculations against historical double-entry ledger transactions.
 */
class AccountService
{
    /**
     * Retrieve all active accounts for a given user.
     *
     * @param int $userId Authenticated user ID
     * @return array List of user accounts with currency metadata
     */
    public function getUserAccounts(int $userId): array
    {
        return AccountModel::getAllByUser($userId);
    }

    /**
     * Retrieve a specific account by ID with ownership verification.
     *
     * @param int $id Account ID
     * @param int $userId Authenticated user ID
     * @return array|null Account record or null
     */
    public function getAccount(int $id, int $userId): ?array
    {
        return AccountModel::findById($id, $userId);
    }

    /**
     * Create a new financial account for the user.
     *
     * @param int $userId Authenticated user ID
     * @param array $data Account configuration (name, type, currency_id, initial_balance, allow_overdraft)
     * @return array Created account details
     */
    public function createAccount(int $userId, array $data): array
    {
        return AccountModel::createAccount($userId, $data);
    }

    /**
     * Run an automated reconciliation on an account, recalculating its current balance
     * directly from historical posted transactions to heal any drift.
     *
     * @param int $userId Authenticated user ID
     * @param int $accountId Account ID to reconcile
     * @return array Reconciliation report (status, stored_balance, calculated_balance, drift, healed)
     */
    public function reconcileAccount(int $userId, int $accountId): array
    {
        return AccountModel::reconcileBalance($userId, $accountId);
    }

    /**
     * Reconcile all accounts for a given user.
     *
     * @param int $userId Authenticated user ID
     * @return array Array of reconciliation reports per account
     */
    public function reconcileAllAccounts(int $userId): array
    {
        return AccountModel::reconcileAll($userId);
    }
}
