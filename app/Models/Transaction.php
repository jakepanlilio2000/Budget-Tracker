<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
use PDOException;

class Transaction
{
        public static function createWithSplits(int $userId, array $txnData, array $splits): bool
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            // Use the first split's category_id for the parent transaction to satisfy any legacy constraints
            $primaryCategoryId = $splits[0]['category_id'] ?? null;

            // 1. Insert Parent Transaction
            $stmt = $db->prepare("
                INSERT INTO transactions (user_id, account_id, category_id, type, total_amount, currency_id, transaction_date, status, description, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $txnData['account_id'],
                $primaryCategoryId,
                $txnData['type'],
                $txnData['total_amount'],
                $txnData['currency_id'],
                $txnData['transaction_date'],
                $txnData['status'],
                $txnData['description'],
                $txnData['notes']
            ]);
            $txnId = (int) $db->lastInsertId();

            // 2. Insert Splits
            $splitStmt = $db->prepare("INSERT INTO transaction_splits (transaction_id, category_id, amount, notes) VALUES (?, ?, ?, ?)");
            foreach ($splits as $split) {
                $splitStmt->execute([$txnId, $split['category_id'], $split['amount'], $split['notes'] ?? null]);
            }

            // 3. Update Account Balance
            if ($txnData['status'] === 'posted') {
                $multiplier = ($txnData['type'] === 'income') ? 1 : -1;
                $change = $txnData['total_amount'] * $multiplier;
                $updateAcc = $db->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
                $updateAcc->execute([$change, $txnData['account_id'], $userId]);
            }

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            \App\Core\Logger::error("Transaction creation failed", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public static function getRecent(int $userId, int $limit = 10): array
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
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}