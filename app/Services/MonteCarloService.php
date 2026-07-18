<?php
declare(strict_types=1);
namespace App\Services;

class MonteCarloService
{

    public static function simulate(
        float $initialBalance,
        float $monthlyContribution,
        float $annualReturn,
        float $annualVolatility,
        int $months,
        int $iterations = 1000,
        float $targetGoal = 0.0
    ): array {
        $monthlyReturn = $annualReturn / 100 / 12;
        $monthlyVolatility = $annualVolatility / 100 / sqrt(12);

        $finalBalances = [];
        $successCount = 0;
        $medianSchedule = array_fill(0, $months, 0);

        for ($i = 0; $i < $iterations; $i++) {
            $balance = $initialBalance;
            $path = [];

            for ($m = 1; $m <= $months; $m++) {
                $u1 = max(0.0001, mt_rand() / mt_getrandmax());
                $u2 = mt_rand() / mt_getrandmax();
                $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

                $randomReturn = $monthlyReturn + ($z * $monthlyVolatility);

                $randomReturn = max(-0.5, min(0.5, $randomReturn));

                $balance = ($balance + $monthlyContribution) * (1 + $randomReturn);
                $path[$m] = $balance;
            }

            $finalBalances[] = $balance;
            if ($balance >= $targetGoal)
                $successCount++;

            foreach ($path as $m => $val) {
                $medianSchedule[$m - 1] += $val;
            }
        }

        sort($finalBalances);
        $p10 = $finalBalances[(int) ($iterations * 0.10)];
        $p50 = $finalBalances[(int) ($iterations * 0.50)];
        $p90 = $finalBalances[(int) ($iterations * 0.90)];

        foreach ($medianSchedule as &$val) {
            $val /= $iterations;
        }

        return [
            'probability_of_success' => round(($successCount / $iterations) * 100, 1),
            'percentiles' => [
                'worst_case_10th' => round($p10, 2),
                'median_50th' => round($p50, 2),
                'best_case_90th' => round($p90, 2)
            ],
            'median_schedule' => array_map(fn($v) => round($v, 2), $medianSchedule),
            'final_balances_sample' => array_slice($finalBalances, 0, 100) // Sample for distribution chart
        ];
    }
}