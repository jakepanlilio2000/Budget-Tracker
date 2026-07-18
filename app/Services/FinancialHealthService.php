<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

class FinancialHealthService
{

    public static function calculate(int $userId, array $sandboxAdjustments = []): array
    {
        $summary = FinancialSummaryEngine::getSummary($userId, date('Y-m-01'), date('Y-m-t'));

        $monthlyIncome = (float) $summary['income']['total'] + (float) ($sandboxAdjustments['income_change'] ?? 0);
        $monthlyExpenses = (float) $summary['expenses']['total'] + (float) ($sandboxAdjustments['expense_change'] ?? 0);


        $monthlySavings = max(0, $monthlyIncome - $monthlyExpenses);

        $savingsRate = $monthlyIncome > 0 ? ($monthlySavings / $monthlyIncome) * 100 : 0;

        $fixedObligations = (float) $summary['bills']['upcoming_balance'];
        $dti = $monthlyIncome > 0 ? ($fixedObligations / $monthlyIncome) * 100 : 0;

        $liquidAssets = (float) $summary['assets']['accounts'];
        $emergencyMonths = $monthlyExpenses > 0 ? $liquidAssets / $monthlyExpenses : 0;

        $savingsScore = min(100, max(0, $savingsRate * 5));
        $dtiScore = max(0, 100 - ($dti * 2.5));
        $emergencyScore = min(100, max(0, $emergencyMonths * 16.66));

        $overallScore = round(($savingsScore * 0.4) + ($dtiScore * 0.3) + ($emergencyScore * 0.3));

        return [
            'overall_score' => $overallScore,
            'metrics' => [
                'savings_rate' => round($savingsRate, 1),
                'debt_to_income' => round($dti, 1),
                'emergency_fund_months' => round($emergencyMonths, 1),
                'monthly_income' => $monthlyIncome,
                'monthly_expenses' => $monthlyExpenses,
                'liquid_assets' => $liquidAssets
            ],
            'scores' => [
                'savings' => round($savingsScore),
                'debt' => round($dtiScore),
                'emergency' => round($emergencyScore)
            ]
        ];
    }
}