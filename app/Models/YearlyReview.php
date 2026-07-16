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

        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'income' THEN total_amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as total_expense,
                COUNT(*) as total_transactions
            FROM transactions 
            WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL 
            AND YEAR(transaction_date) = ?
        ");
        $stmt->execute([$userId, $year]);
        $flow = $stmt->fetch();
        $data['total_income'] = (float)$flow['total_income'];
        $data['total_expense'] = (float)$flow['total_expense'];
        $data['net_income'] = $data['total_income'] - $data['total_expense'];
        $data['total_transactions'] = (int)$flow['total_transactions'];
        $stmt = $db->prepare("
            SELECT 
                MONTH(transaction_date) as month_num,
                DATE_FORMAT(transaction_date, '%b') as month_name,
                SUM(CASE WHEN type = 'income' THEN total_amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END) as expense
            FROM transactions 
            WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL 
            AND YEAR(transaction_date) = ?
            GROUP BY MONTH(transaction_date), month_name
            ORDER BY month_num ASC
        ");
        $stmt->execute([$userId, $year]);
        $monthlyRaw = $stmt->fetchAll();
        $data['monthly_trend'] = [];
        for ($m = 1; $m <= 12; $m++) {
            $found = array_filter($monthlyRaw, fn($row) => (int)$row['month_num'] === $m);
            if ($found) {
                $row = reset($found);
                $data['monthly_trend'][] = [
                    'month' => $row['month_name'],
                    'income' => (float)$row['income'],
                    'expense' => (float)$row['expense'],
                    'net' => (float)$row['income'] - (float)$row['expense']
                ];
            } else {
                $data['monthly_trend'][] = [
                    'month' => date('M', mktime(0, 0, 0, $m, 10)),
                    'income' => 0.0, 'expense' => 0.0, 'net' => 0.0
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
        $stmt = $db->prepare("
            SELECT c.name, c.color, SUM(ts.amount) as total 
            FROM transaction_splits ts
            JOIN categories c ON ts.category_id = c.id
            JOIN transactions t ON ts.transaction_id = t.id
            WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
            AND YEAR(t.transaction_date) = ?
            GROUP BY c.id, c.name, c.color
            ORDER BY total DESC LIMIT 6
        ");
        $stmt->execute([$userId, $year]);
        $data['top_categories'] = $stmt->fetchAll();
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_deposits 
            FROM vault_transactions 
            WHERE user_id = ? AND type = 'deposit' AND YEAR(created_at) = ?
        ");
        $stmt->execute([$userId, $year]);
        $data['yearly_savings'] = (float)$stmt->fetchColumn();
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