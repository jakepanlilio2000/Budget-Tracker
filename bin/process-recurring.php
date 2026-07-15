<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';

use App\Core\Database;
use App\Models\Transaction;

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.' . PHP_EOL);
}

echo "Starting recurring transaction processor..." . PHP_EOL;

$db = Database::getInstance()->getConnection();

// Fetch all active recurring transactions
$stmt = $db->prepare("
    SELECT * FROM transactions 
    WHERE is_recurring = 1 AND status = 'posted' AND deleted_at IS NULL
    AND recurring_rule IS NOT NULL
");
$stmt->execute();
$recurringTxns = $stmt->fetchAll();

$createdCount = 0;

foreach ($recurringTxns as $txn) {
    if ($txn['recurring_rule'] === '1m') {
        $checkStmt = $db->prepare("
            SELECT COUNT(*) FROM transactions 
            WHERE user_id = ? AND description = ? AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
        ");
        $checkStmt->execute([$txn['user_id'], $txn['description']]);

        if ($checkStmt->fetchColumn() == 0) {
            $newData = [
                'account_id' => $txn['account_id'],
                'type' => $txn['type'],
                'total_amount' => $txn['total_amount'],
                'currency_id' => $txn['currency_id'],
                'transaction_date' => date('Y-m-d'),
                'status' => 'posted',
                'description' => $txn['description'],
                'notes' => $txn['notes'] . ' [Auto-generated Recurring]'
            ];

            // Fetch original splits
            $splitStmt = $db->prepare("SELECT category_id, amount, notes FROM transaction_splits WHERE transaction_id = ?");
            $splitStmt->execute([$txn['id']]);
            $splits = $splitStmt->fetchAll();

            if (Transaction::createWithSplits($txn['user_id'], $newData, $splits)) {
                echo "✓ Created recurring: {$txn['description']} for User ID {$txn['user_id']}" . PHP_EOL;
                $createdCount++;
            }
        }
    }
}

echo "Process complete. {$createdCount} recurring transactions created." . PHP_EOL;