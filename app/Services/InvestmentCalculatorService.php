<?php
declare(strict_types=1);
namespace App\Services;

class InvestmentCalculatorService
{

    public static function calculate(
        float $initial,
        float $monthlyContribution,
        float $annualReturn,
        float $annualFee,
        int $months,
        float $inflationRate = 2.0
    ): array {
        $monthlyReturn = $annualReturn / 100 / 12;
        $monthlyFee = $annualFee / 100 / 12;
        $monthlyInflation = $inflationRate / 100 / 12;

        $balance = $initial;
        $totalContributions = $initial;
        $totalReturns = 0;
        $schedule = [];

        for ($m = 1; $m <= $months; $m++) {
            $growth = $balance * ($monthlyReturn - $monthlyFee);
            $balance += $growth + $monthlyContribution;
            $totalContributions += $monthlyContribution;
            $totalReturns += $growth;
            if ($m % 12 === 0 || $m === $months) {
                $realValue = $balance / pow(1 + $monthlyInflation, $m);

                $schedule[] = [
                    'month' => $m,
                    'balance' => round($balance, 2),
                    'real_value' => round($realValue, 2),
                    'contributions' => round($totalContributions, 2),
                    'returns' => round($totalReturns, 2)
                ];
            }
        }

        return [
            'final_balance' => round($balance, 2),
            'final_real_value' => round($balance / pow(1 + $monthlyInflation, $months), 2),
            'total_contributions' => round($totalContributions, 2),
            'total_returns' => round($totalReturns, 2),
            'schedule' => $schedule
        ];
    }
}