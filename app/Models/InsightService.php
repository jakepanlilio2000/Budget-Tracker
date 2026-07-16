<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

class InsightService
{
    public static function getFinancialHealthScore(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE WHEN type = 'income' AND status = 'posted' THEN total_amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' AND status = 'posted' THEN total_amount ELSE 0 END) as total_expense
            FROM transactions 
            WHERE user_id = ? AND deleted_at IS NULL 
            AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)
        ");
        $stmt->execute([$userId]);
        $flow = $stmt->fetch();

        $income = (float) ($flow['total_income'] ?? 0);
        $expense = (float) ($flow['total_expense'] ?? 0);
        $savingsRate = $income > 0 ? (($income - $expense) / $income) : 0;
        $savingsScore = min(40, max(0, $savingsRate * 100 * 1.5));
        $stmt = $db->prepare("SELECT SUM(current_balance) as total_balance FROM accounts WHERE user_id = ? AND deleted_at IS NULL AND type != 'credit_card'");
        $stmt->execute([$userId]);
        $balance = (float) ($stmt->fetchColumn() ?? 0);
        $monthlyExpense = $expense / 3;
        $emergencyMonths = $monthlyExpense > 0 ? ($balance / $monthlyExpense) : 0;
        $emergencyScore = min(40, $emergencyMonths * 10);
        $stmt = $db->prepare("SELECT COUNT(DISTINCT MONTH(transaction_date)) as active_months FROM transactions WHERE user_id = ? AND type = 'income' AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)");
        $stmt->execute([$userId]);
        $activeMonths = (int) ($stmt->fetchColumn() ?? 0);
        $consistencyScore = min(20, $activeMonths * 10);

        $totalScore = (int) round($savingsScore + $emergencyScore + $consistencyScore);

        $grade = $totalScore >= 80 ? 'A' : ($totalScore >= 60 ? 'B' : ($totalScore >= 40 ? 'C' : 'D'));

        return [
            'score' => $totalScore,
            'grade' => $grade,
            'savings_rate' => round($savingsRate * 100, 1),
            'emergency_months' => round($emergencyMonths, 1)
        ];
    }

    public static function getRecommendations(int $userId): array
    {
        $health = self::getFinancialHealthScore($userId);
        $recommendations = [];

        if ($health['savings_rate'] < 10) {
            $recommendations[] = [
                'icon' => 'fas fa-piggy-bank',
                'color' => 'var(--danger)',
                'title' => 'Boost Your Savings',
                'text' => 'Your savings rate is below 10%. Try applying the 50/30/20 rule to your next paycheck.'
            ];
        }
        if ($health['emergency_months'] < 3) {
            $recommendations[] = [
                'icon' => 'fas fa-shield-alt',
                'color' => 'var(--accent)',
                'title' => 'Build Emergency Fund',
                'text' => 'You have less than 3 months of expenses saved. Prioritize building a safety net.'
            ];
        }
        if (empty($recommendations)) {
            $recommendations[] = [
                'icon' => 'fas fa-check-circle',
                'color' => 'var(--success)',
                'title' => 'Excellent Financial Health',
                'text' => 'Your financial habits are strong. Consider investing your surplus to beat inflation.'
            ];
        }

        return $recommendations;
    }
}