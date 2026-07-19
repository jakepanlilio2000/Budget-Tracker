<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

class AnalyticsService
{
    public static function getFinancialPerformance(int $userId, string $from, string $to): array
    {
        $db = Database::getInstance()->getConnection();

        // Include both Transactions and Salaries for a complete income picture
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(date_val, '%Y-%m') as month,
                DATE_FORMAT(date_val, '%b %Y') as label,
                SUM(income) as income,
                SUM(expense) as expense
            FROM (
                SELECT transaction_date as date_val, 
                    CASE WHEN type = 'income' THEN total_amount ELSE 0 END as income,
                    CASE WHEN type = 'expense' THEN total_amount ELSE 0 END as expense
                FROM transactions 
                WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL 
                AND transaction_date BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT payment_date as date_val, net_pay as income, 0 as expense
                FROM salaries
                WHERE user_id = ? AND status = 'paid' AND payment_date BETWEEN ? AND ?
            ) as combined
            GROUP BY month, label
            ORDER BY month ASC
        ");
        $stmt->execute([$userId, $from, $to, $userId, $from, $to]);
        $rows = $stmt->fetchAll();

        $labels = [];
        $income = [];
        $expense = [];
        $net = [];

        foreach ($rows as $row) {
            $labels[] = $row['label'];
            $inc = (float) $row['income'];
            $exp = (float) $row['expense'];
            $income[] = $inc;
            $expense[] = $exp;
            $net[] = $inc - $exp;
        }

        // Fallback if no data in range
        if (empty($labels)) {
            $labels = [date('M Y', strtotime($from))];
            $income = [0];
            $expense = [0];
            $net = [0];
        }

        return compact('labels', 'income', 'expense', 'net');
    }

    public static function getBehavioralAnalysis(int $userId, string $from, string $to): array
    {
        $db = Database::getInstance()->getConnection();

        // Day of Week Analysis
        $stmt = $db->prepare("
            SELECT DAYOFWEEK(transaction_date) as dow, SUM(total_amount) as total
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL 
            AND transaction_date BETWEEN ? AND ?
            GROUP BY dow ORDER BY dow
        ");
        $stmt->execute([$userId, $from, $to]);
        $dowRaw = array_column($stmt->fetchAll(), 'total', 'dow');

        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $dowData = [];
        foreach ($days as $i => $day) {
            $dowData[$day] = (float) ($dowRaw[$i + 1] ?? 0);
        }

        // Hour of Day Analysis (Using created_at to see when user actually logs expenses)
        $stmt = $db->prepare("
            SELECT HOUR(created_at) as hour, SUM(total_amount) as total
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL 
            AND transaction_date BETWEEN ? AND ?
            GROUP BY hour ORDER BY hour
        ");
        $stmt->execute([$userId, $from, $to]);
        $hourRaw = array_column($stmt->fetchAll(), 'total', 'hour');

        $hourLabels = [];
        $hourData = [];
        for ($h = 0; $h < 24; $h++) {
            $hourLabels[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $hourData[] = (float) ($hourRaw[$h] ?? 0);
        }

        return [
            'day_of_week' => ['labels' => array_keys($dowData), 'data' => array_values($dowData)],
            'hour_of_day' => ['labels' => $hourLabels, 'data' => $hourData]
        ];
    }

    public static function getCategoryIntelligence(int $userId, string $from, string $to): array
    {
        $db = Database::getInstance()->getConnection();

        // FIX: Query BOTH transactions and bills using category_id, not just transaction_splits.
        // This ensures all expenses are captured, even if they weren't explicitly split.
        $stmt = $db->prepare("
            SELECT c.name, c.color, SUM(sub.total) as total 
            FROM (
                SELECT category_id, SUM(total_amount) as total 
                FROM transactions 
                WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL
                AND transaction_date BETWEEN ? AND ? AND category_id IS NOT NULL
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
        $stmt->execute([$userId, $from, $to, $userId, $userId]);
        $topCats = $stmt->fetchAll();

        // Category Trends (Monthly)
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(t.transaction_date, '%b') as month,
                c.name as category,
                c.color as color,
                SUM(t.total_amount) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
            AND t.transaction_date BETWEEN ? AND ? AND t.category_id IS NOT NULL
            GROUP BY month, c.id, c.name, c.color
            ORDER BY MIN(t.transaction_date) ASC
        ");
        $stmt->execute([$userId, $from, $to]);
        $trendRaw = $stmt->fetchAll();

        $months = array_unique(array_column($trendRaw, 'month'));
        $categories = array_unique(array_column($trendRaw, 'category'));

        $datasets = [];
        foreach ($categories as $cat) {
            $data = [];
            $color = '#ccc';
            foreach ($months as $m) {
                $found = array_filter($trendRaw, fn($r) => $r['category'] === $cat && $r['month'] === $m);
                if ($found) {
                    $row = reset($found);
                    $data[] = (float) $row['total'];
                    $color = $row['color'];
                } else {
                    $data[] = 0;
                }
            }
            $datasets[] = [
                'label' => $cat,
                'data' => $data,
                'backgroundColor' => $color,
                'borderRadius' => 4
            ];
        }

        return [
            'top_categories' => $topCats,
            'trends' => [
                'labels' => array_values($months),
                'datasets' => $datasets
            ]
        ];
    }

    public static function getAccountAnalysis(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT name, type, current_balance 
            FROM accounts 
            WHERE user_id = ? AND deleted_at IS NULL
            ORDER BY current_balance DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}