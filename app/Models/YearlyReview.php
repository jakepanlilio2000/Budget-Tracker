<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class YearlyReview
{
    public static function generate(int $userId, string $year): array
    {
        $db = Database::getInstance()->getConnection();
        $data = ['year' => $year];

        // 1. Total Income (Transactions + Salaries)
        $stmt = $db->prepare("
            SELECT SUM(total) as total_income FROM (
                SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'income' AND status = 'posted' AND deleted_at IS NULL AND YEAR(transaction_date) = ?
                UNION ALL
                SELECT COALESCE(SUM(net_pay), 0) as total FROM salaries WHERE user_id = ? AND status = 'paid' AND YEAR(payment_date) = ?
            ) as inc
        ");
        $stmt->execute([$userId, $year, $userId, $year]);
        $data['total_income'] = (float) $stmt->fetchColumn();

        // 2. Total Expense (Transactions + Daily Logs + Bill Payments + Paid Pending Ledger)
        $stmt = $db->prepare("
            SELECT SUM(total) as total_expense FROM (
                SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL AND YEAR(transaction_date) = ?
                UNION ALL
                SELECT COALESCE(SUM(total_spent), 0) as total FROM daily_logs WHERE user_id = ? AND YEAR(log_date) = ?
                UNION ALL
                SELECT COALESCE(SUM(amount_paid), 0) as total FROM bill_payments WHERE user_id = ? AND YEAR(payment_date) = ?
                UNION ALL
                SELECT COALESCE(SUM(amount), 0) as total FROM pending_ledger WHERE user_id = ? AND type = 'expense' AND status = 'paid' AND YEAR(due_date) = ?
            ) as exp
        ");
        $stmt->execute([$userId, $year, $userId, $year, $userId, $year, $userId, $year]);
        $data['total_expense'] = (float) $stmt->fetchColumn();

        $data['net_income'] = $data['total_income'] - $data['total_expense'];

        // 3. Total Transactions (For milestone logging)
        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL AND YEAR(transaction_date) = ?");
        $stmt->execute([$userId, $year]);
        $data['total_transactions'] = (int) $stmt->fetchColumn();

        // 4. Monthly Trend (Income vs Expense across all modules)
        $stmt = $db->prepare("
            SELECT month_num, month_name, SUM(income) as income, SUM(expense) as expense FROM (
                SELECT MONTH(transaction_date) as month_num, DATE_FORMAT(transaction_date, '%b') as month_name, 
                    CASE WHEN type = 'income' THEN total_amount ELSE 0 END as income,
                    CASE WHEN type = 'expense' THEN total_amount ELSE 0 END as expense
                FROM transactions WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL AND YEAR(transaction_date) = ?
                
                UNION ALL
                
                SELECT MONTH(payment_date) as month_num, DATE_FORMAT(payment_date, '%b') as month_name, net_pay as income, 0 as expense
                FROM salaries WHERE user_id = ? AND status = 'paid' AND YEAR(payment_date) = ?
                
                UNION ALL
                
                SELECT MONTH(log_date) as month_num, DATE_FORMAT(log_date, '%b') as month_name, 0 as income, total_spent as expense
                FROM daily_logs WHERE user_id = ? AND YEAR(log_date) = ?
                
                UNION ALL
                
                SELECT MONTH(payment_date) as month_num, DATE_FORMAT(payment_date, '%b') as month_name, 0 as income, amount_paid as expense
                FROM bill_payments WHERE user_id = ? AND YEAR(payment_date) = ?
            ) as combined
            GROUP BY month_num, month_name
            ORDER BY month_num ASC
        ");
        $stmt->execute([$userId, $year, $userId, $year, $userId, $year, $userId, $year]);
        $monthlyRaw = $stmt->fetchAll();

        $data['monthly_trend'] = [];
        for ($m = 1; $m <= 12; $m++) {
            $found = array_filter($monthlyRaw, fn($row) => (int) $row['month_num'] === $m);
            if ($found) {
                $row = reset($found);
                $data['monthly_trend'][] = [
                    'month' => $row['month_name'],
                    'income' => (float) $row['income'],
                    'expense' => (float) $row['expense'],
                    'net' => (float) $row['income'] - (float) $row['expense']
                ];
            } else {
                $data['monthly_trend'][] = [
                    'month' => date('M', mktime(0, 0, 0, $m, 10)),
                    'income' => 0.0,
                    'expense' => 0.0,
                    'net' => 0.0
                ];
            }
        }

        $activeMonths = array_filter($data['monthly_trend'], fn($m) => $m['income'] > 0 || $m['expense'] > 0);
        if (!empty($activeMonths)) {
            usort($activeMonths, fn($a, $b) => $b['net'] <=> $a['net']);
            $data['best_month'] = reset($activeMonths);
            $data['worst_month'] = end($activeMonths);
        } else {
            $data['best_month'] = ['month' => 'N/A', 'net' => 0];
            $data['worst_month'] = ['month' => 'N/A', 'net' => 0];
        }

        // 5. Top Categories (Transactions + Bills)
        $stmt = $db->prepare("
            SELECT c.name, c.color, SUM(sub.total) as total 
            FROM (
                SELECT category_id, SUM(total_amount) as total 
                FROM transactions 
                WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL AND YEAR(transaction_date) = ? AND category_id IS NOT NULL
                GROUP BY category_id
                
                UNION ALL
                
                SELECT category_id, SUM(total_amount) as total 
                FROM bills 
                WHERE user_id = ? AND category_id IS NOT NULL
                GROUP BY category_id
            ) as sub
            JOIN categories c ON sub.category_id = c.id
            WHERE c.user_id = ?
            GROUP BY c.id, c.name, c.color
            ORDER BY total DESC LIMIT 6
        ");
        $stmt->execute([$userId, $year, $userId, $userId]);
        $data['top_categories'] = $stmt->fetchAll();

        // 6. Yearly Savings
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_deposits 
            FROM vault_transactions 
            WHERE user_id = ? AND type = 'deposit' AND YEAR(created_at) = ?
        ");
        $stmt->execute([$userId, $year]);
        $data['yearly_savings'] = (float) $stmt->fetchColumn();

        // 7. Milestones
        $data['milestones'] = [];
        $positiveMonths = count(array_filter($data['monthly_trend'], fn($m) => $m['net'] > 0));

        $data['milestones'][] = "Logged <strong>{$data['total_transactions']}</strong> transactions this year.";
        $data['milestones'][] = "Achieved positive cash flow in <strong>{$positiveMonths} out of 12</strong> months.";
        if ($data['yearly_savings'] > 0) {
            $data['milestones'][] = "Saved a total of <strong>" . number_format($data['yearly_savings'], 2) . "</strong> in your Savings Vaults.";
        }
        if ($data['net_income'] > 0) {
            $data['milestones'][] = "Finished the year with a net surplus of <strong>" . number_format($data['net_income'], 2) . "</strong>.";
        }

        return $data;
    }
}