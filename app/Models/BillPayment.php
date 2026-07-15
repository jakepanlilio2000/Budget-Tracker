<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class BillPayment
{
    public static function record(int $userId, int $billId, float $amount, float $penalty, ?int $accountId, ?string $notes): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO bill_payments (bill_id, user_id, amount_paid, penalty_applied, payment_date, account_id, notes) 
            VALUES (?, ?, ?, ?, CURRENT_DATE(), ?, ?)
        ");
        return $stmt->execute([$billId, $userId, $amount, $penalty, $accountId, $notes]);
    }
}