<?php
declare(strict_types=1);
namespace App\Services;

class SmartRecommendationService
{

    public static function generate(array $healthData): array
    {
        $recommendations = [];
        $metrics = $healthData['metrics'];
        if ($metrics['emergency_fund_months'] < 3) {
            $recommendations[] = [
                'title' => 'Build Your Emergency Fund',
                'description' => 'You currently have ' . $metrics['emergency_fund_months'] . ' months of expenses saved. Aim for at least 3-6 months to protect against unexpected events.',
                'icon' => 'fa-shield-alt',
                'color' => '#ef4444',
                'priority' => 'high'
            ];
        } elseif ($metrics['emergency_fund_months'] < 6) {
            $recommendations[] = [
                'title' => 'Boost Emergency Savings',
                'description' => 'Great start! You have ' . $metrics['emergency_fund_months'] . ' months saved. Try to reach 6 months for optimal financial security.',
                'icon' => 'fa-shield-alt',
                'color' => '#f59e0b',
                'priority' => 'medium'
            ];
        }

        if ($metrics['savings_rate'] < 10) {
            $recommendations[] = [
                'title' => 'Increase Savings Rate',
                'description' => 'Your current savings rate is ' . $metrics['savings_rate'] . '%. Try to allocate at least 20% of your income to savings and investments using the 50/30/20 rule.',
                'icon' => 'fa-piggy-bank',
                'color' => '#ef4444',
                'priority' => 'high'
            ];
        }

        if ($metrics['debt_to_income'] > 30) {
            $recommendations[] = [
                'title' => 'Reduce Fixed Obligations',
                'description' => 'Your fixed obligations consume ' . $metrics['debt_to_income'] . '% of your income. Look for ways to refinance debt or reduce recurring bills.',
                'icon' => 'fa-file-invoice-dollar',
                'color' => '#f59e0b',
                'priority' => 'medium'
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'title' => 'Excellent Financial Health!',
                'description' => 'Your financial foundation is strong. Consider exploring investment opportunities in the Studio to grow your wealth further.',
                'icon' => 'fa-trophy',
                'color' => '#10b981',
                'priority' => 'low'
            ];
        }

        return $recommendations;
    }
}