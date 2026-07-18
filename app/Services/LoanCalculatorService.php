<?php
declare(strict_types=1);
namespace App\Services;

class LoanCalculatorService
{
    public static function calculate(float $principal, float $annualRate, int $termMonths, float $extraPayment = 0.0): array
    {
        $monthlyRate = $annualRate / 100 / 12;

        if ($monthlyRate > 0) {
            $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) / (pow(1 + $monthlyRate, $termMonths) - 1);
        } else {
            $monthlyPayment = $principal / $termMonths;
        }

        $totalPayment = 0;
        $totalInterest = 0;
        $balance = $principal;
        $actualMonths = 0;
        $schedule = [];

        while ($balance > 0.01 && $actualMonths < ($termMonths * 2)) {
            $actualMonths++;
            $interestPayment = $balance * $monthlyRate;
            $principalPayment = $monthlyPayment - $interestPayment + $extraPayment;

            if ($principalPayment >= $balance) {
                $principalPayment = $balance;
                $balance = 0;
            } else {
                $balance -= $principalPayment;
            }

            $totalPayment += ($monthlyPayment + $extraPayment);
            $totalInterest += $interestPayment;
            if ($actualMonths % 12 === 0 || $balance == 0) {
                $schedule[] = [
                    'month' => $actualMonths,
                    'balance' => round($balance, 2),
                    'interest_paid_ytd' => round($totalInterest, 2)
                ];
            }
        }

        return [
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payment' => round($totalPayment, 2),
            'total_interest' => round($totalInterest, 2),
            'actual_months' => $actualMonths,
            'months_saved' => max(0, $termMonths - $actualMonths),
            'interest_saved' => 0,
            'schedule' => $schedule
        ];
    }
}