<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class MonthlyReview
{
    public static function generate(int $userId, string $month): array
    {
        $db = Database::getInstance()->getConnection();
        $data = ['month' => $month];
        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'income' THEN total_amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as total_expense
            FROM transactions 
            WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL 
            AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $month]);
        $flow = $stmt->fetch();
        $data['total_income'] = (float) $flow['total_income'];
        $data['total_expense'] = (float) $flow['total_expense'];
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(net_pay), 0) as total_salaries
            FROM salaries 
            WHERE user_id = ? AND DATE_FORMAT(payment_date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $month]);
        $data['total_income'] += (float) $stmt->fetchColumn();
        $data['net_income'] = $data['total_income'] - $data['total_expense'];
        $stmt = $db->prepare("
            SELECT description, total_amount FROM transactions 
            WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL 
            AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
            ORDER BY total_amount DESC LIMIT 1
        ");
        $stmt->execute([$userId, $month]);
        $data['biggest_expense'] = $stmt->fetch() ?: ['description' => 'N/A', 'total_amount' => 0];
        $stmt = $db->prepare("
            SELECT c.name, c.color, SUM(ts.amount) as total 
            FROM transaction_splits ts
            JOIN categories c ON ts.category_id = c.id
            JOIN transactions t ON ts.transaction_id = t.id
            WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
            AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            GROUP BY c.id, c.name, c.color
            ORDER BY total DESC LIMIT 1
        ");
        $stmt->execute([$userId, $month]);
        $data['top_category'] = $stmt->fetch() ?: ['name' => 'N/A', 'color' => '#ccc', 'total' => 0];
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(bp.amount_paid + bp.penalty_applied), 0) as total_bills
            FROM bill_payments bp
            JOIN bills b ON bp.bill_id = b.id
            WHERE b.user_id = ? AND DATE_FORMAT(bp.payment_date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $month]);
        $data['total_expense'] += (float) $stmt->fetchColumn();
        $data['net_income'] = $data['total_income'] - $data['total_expense'];
        $stmt = $db->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = ? AND month = ?");
        $stmt->execute([$userId, $month]);
        $totalBudgets = (int) $stmt->fetchColumn();

        $overBudgetCount = 0;
        if ($totalBudgets > 0) {
            $stmt = $db->prepare("
                SELECT b.id, b.amount, COALESCE(SUM(ts.amount), 0) as spent
                FROM budgets b
                LEFT JOIN transaction_splits ts ON b.category_id = ts.category_id
                LEFT JOIN transactions t ON ts.transaction_id = t.id 
                    AND t.user_id = b.user_id AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
                    AND DATE_FORMAT(t.transaction_date, '%Y-%m') = b.month
                WHERE b.user_id = ? AND b.month = ?
                GROUP BY b.id, b.amount
                HAVING spent > b.amount
            ");
            $stmt->execute([$userId, $month]);
            $overBudgetCount = $stmt->rowCount();
        }
        $data['budget_success_rate'] = $totalBudgets > 0 ? round((($totalBudgets - $overBudgetCount) / $totalBudgets) * 100) : 100;
        $data['total_budgets'] = $totalBudgets;
        $data['over_budget_count'] = $overBudgetCount;
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_deposits 
            FROM vault_transactions 
            WHERE user_id = ? AND type = 'deposit' AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $month]);
        $data['monthly_savings'] = (float) $stmt->fetchColumn();
        $data['achievements'] = [];
        $data['warnings'] = [];

        if ($data['net_income'] > 0) {
            $data['achievements'][] = "Positive Cash Flow: You saved " . number_format($data['net_income'], 2) . " this month!";
        } else {
            $data['warnings'][] = "Negative Cash Flow: You spent more than you earned this month.";
        }

        if ($data['monthly_savings'] > 0) {
            $data['achievements'][] = "Savings Champion: You added " . number_format($data['monthly_savings'], 2) . " to your vaults.";
        }

        if ($data['budget_success_rate'] == 100 && $totalBudgets > 0) {
            $data['achievements'][] = "Budget Master: You stayed within all your budgets this month!";
        } elseif ($overBudgetCount > 0) {
            $data['warnings'][] = "Budget Alert: You exceeded your budget in {$overBudgetCount} categor" . ($overBudgetCount > 1 ? 'ies' : 'y') . ".";
        }

        if ($data['total_expense'] == 0) {
            $data['achievements'][] = "Zero Spend: No expenses recorded (or fully tracked via vaults).";
        }

        return $data;
    }
}