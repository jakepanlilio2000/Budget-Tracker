<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Backwards compatibility adapter delegating to TransactionModel.
 */
class Transaction
{
    public static function createWithSplits(int $userId, array $txnData, array $splits, ?string $clientMutationId = null): bool
    {
        try {
            $result = TransactionModel::createWithSplits($userId, $txnData, $splits, $clientMutationId);
            return !empty($result['success']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getRecent(int $userId, int $limit = 10): array
    {
        return TransactionModel::getRecent($userId, $limit);
    }
}