<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

class AnalyticsService
{
    public static function getFinancialPerformance(int $userId, string $from, string $to): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(transaction_date, '%Y-%m') as month,
                DATE_FORMAT(transaction_date, '%b %Y') as label,
                SUM(CASE WHEN type = 'income' THEN total_amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END) as expense
            FROM transactions 
            WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL 
            AND transaction_date BETWEEN ? AND ?
            GROUP BY month, label
            ORDER BY month ASC
        ");
        $stmt->execute([$userId, $from, $to]);
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

        return compact('labels', 'income', 'expense', 'net');
    }

    public static function getBehavioralAnalysis(int $userId, string $from, string $to): array
    {
        $db = Database::getInstance()->getConnection();

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
        $stmt = $db->prepare("
            SELECT c.name, c.color, SUM(ts.amount) as total 
            FROM transaction_splits ts
            JOIN categories c ON ts.category_id = c.id
            JOIN transactions t ON ts.transaction_id = t.id
            WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
            AND t.transaction_date BETWEEN ? AND ?
            GROUP BY c.id, c.name, c.color
            ORDER BY total DESC LIMIT 6
        ");
        $stmt->execute([$userId, $from, $to]);
        $topCats = $stmt->fetchAll();
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(t.transaction_date, '%b') as month,
                c.name as category,
                c.color as color,
                SUM(ts.amount) as total
            FROM transaction_splits ts
            JOIN categories c ON ts.category_id = c.id
            JOIN transactions t ON ts.transaction_id = t.id
            WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
            AND t.transaction_date BETWEEN ? AND ?
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