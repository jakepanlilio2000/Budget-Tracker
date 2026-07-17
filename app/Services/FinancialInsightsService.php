<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

class FinancialInsightsService
{
    public static function generateInsights(int $userId, string $currentStart, string $currentEnd, string $prevStart, string $prevEnd): array
    {
        $db = Database::getInstance()->getConnection();
        $insights = [];
        $metrics = [];

        $getTotals = function ($start, $end) use ($db, $userId) {
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN type = 'income' THEN total_amount ELSE 0 END), 0) as income,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as expense
                FROM transactions WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL 
                AND transaction_date BETWEEN ? AND ?
            ");
            $stmt->execute([$userId, $start, $end]);
            return $stmt->fetch();
        };

        $curr = $getTotals($currentStart, $currentEnd);
        $prev = $getTotals($prevStart, $prevEnd);

        $currIncome = (float) $curr['income'];
        $currExpense = (float) $curr['expense'];
        $prevIncome = (float) $prev['income'];
        $prevExpense = (float) $prev['expense'];

        $currNet = $currIncome - $currExpense;
        $prevNet = $prevIncome - $prevExpense;

        $metrics['income_change'] = $prevIncome > 0 ? round((($currIncome - $prevIncome) / $prevIncome) * 100, 1) : 0;
        $metrics['expense_change'] = $prevExpense > 0 ? round((($currExpense - $prevExpense) / $prevExpense) * 100, 1) : 0;
        $metrics['net_change'] = $prevNet != 0 ? round((($currNet - $prevNet) / abs($prevNet)) * 100, 1) : 0;

        if ($metrics['expense_change'] > 10) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'fa-arrow-trend-up',
                'color' => '#ef4444',
                'message' => "Spending increased by " . abs($metrics['expense_change']) . "% compared to last month."
            ];
        } elseif ($metrics['expense_change'] < -5) {
            $insights[] = [
                'type' => 'success',
                'icon' => 'fa-arrow-trend-down',
                'color' => '#10b981',
                'message' => "Excellent discipline! Spending decreased by " . abs($metrics['expense_change']) . "%."
            ];
        }

        if ($currIncome > 0 && $currExpense > 0) {
            $savingsRate = round((($currIncome - $currExpense) / $currIncome) * 100, 1);
            if ($savingsRate >= 20) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'fa-piggy-bank',
                    'color' => '#10b981',
                    'message' => "Outstanding! You are saving {$savingsRate}% of your income this month."
                ];
            } elseif ($savingsRate < 0) {
                $insights[] = [
                    'type' => 'danger',
                    'icon' => 'fa-triangle-exclamation',
                    'color' => '#ef4444',
                    'message' => "Cash flow is negative. Expenses exceed income by " . abs($savingsRate) . "%."
                ];
            }
        }

        if ($metrics['income_change'] > 0) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'fa-chart-line',
                'color' => '#3b82f6',
                'message' => "Income grew by {$metrics['income_change']}% this month. Keep it up!"
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'fa-check-circle',
                'color' => '#3b82f6',
                'message' => "Your financial situation is stable compared to last month."
            ];
        }

        return [
            'metrics' => $metrics,
            'insights' => $insights
        ];
    }
}