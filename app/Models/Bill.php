<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class Bill
{
    public static function getActiveByUser(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM bills WHERE user_id = ? AND status != 'completed' ORDER BY next_due_date ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getUpcoming(int $userId, int $limit = 5): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM bills 
            WHERE user_id = ? AND status = 'active' AND next_due_date >= CURRENT_DATE() 
            ORDER BY next_due_date ASC LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO bills (user_id, category_id, name, total_amount, frequency, recurring_count, next_due_date, penalty_rate, penalty_type, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $data['category_id'], $data['name'], $data['total_amount'], $data['frequency'], 
            $data['recurring_count'], $data['next_due_date'], $data['penalty_rate'] ?? 0, 
            $data['penalty_type'] ?? 'fixed', $data['notes'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }
    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM bills WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function getTotalPaid(int $billId): float
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount_paid), 0) as total FROM bill_payments WHERE bill_id = ?");
        $stmt->execute([$billId]);
        return (float)$stmt->fetchColumn();
    }

    public static function calculatePenalty(array $bill, float $totalPaid): float
    {
        $remaining = $bill['total_amount'] - $totalPaid;
        if ($remaining <= 0) return 0.0;

        $daysOverdue = max(0, (strtotime(date('Y-m-d')) - strtotime($bill['next_due_date'])) / 86400);
        if ($daysOverdue <= 0) return 0.0;

        if ($bill['penalty_type'] === 'percentage') {
            return round(($remaining * ($bill['penalty_rate'] / 100)) * ($daysOverdue / 30), 2);
        }
        return round($bill['penalty_rate'] * ($daysOverdue / 30), 2); // Fixed penalty per month
    }

    public static function advanceDueDate(int $billId, string $frequency): void
    {
        $db = Database::getInstance()->getConnection();
        $interval = match($frequency) {
            'weekly' => 'INTERVAL 1 WEEK',
            'monthly' => 'INTERVAL 1 MONTH',
            'quarterly' => 'INTERVAL 3 MONTH',
            'yearly' => 'INTERVAL 1 YEAR',
            default => 'INTERVAL 1 MONTH'
        };
        
        $stmt = $db->prepare("UPDATE bills SET next_due_date = DATE_ADD(next_due_date, {$interval}) WHERE id = ?");
        $stmt->execute([$billId]);
    }
}