<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class Salary
{
    public static function getRecent(int $userId, int $limit = 10): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT s.*, e.company_name 
            FROM salaries s 
            JOIN employers e ON s.employer_id = e.id 
            WHERE s.user_id = ? 
            ORDER BY s.payment_date DESC LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT s.*, e.company_name, e.contact_email 
            FROM salaries s 
            JOIN employers e ON s.employer_id = e.id 
            WHERE s.id = ? AND s.user_id = ?
        ");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO salaries (user_id, employer_id, pay_period_start, pay_period_end, basic_salary, bonus, overtime_pay, allowances, deductions, thirteenth_month, net_pay, payment_date, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $data['employer_id'], $data['pay_period_start'], $data['pay_period_end'],
            $data['basic_salary'], $data['bonus'] ?? 0, $data['overtime_pay'] ?? 0,
            json_encode($data['allowances'] ?? []), json_encode($data['deductions'] ?? []),
            $data['thirteenth_month'] ?? 0, $data['net_pay'], $data['payment_date'], 
            $data['status'] ?? 'paid', $data['notes'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function getAnalytics(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                SUM(net_pay) as total_earned,
                SUM(basic_salary) as total_basic,
                SUM(bonus + overtime_pay + thirteenth_month) as total_extras,
                COUNT(*) as total_payslips
            FROM salaries WHERE user_id = ? AND YEAR(payment_date) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }
}