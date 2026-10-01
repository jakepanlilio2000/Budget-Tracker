<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use DateTimeImmutable;
use App\Core\Database;
use App\Core\Logger;
use App\Models\CurrencyService;
use App\Services\MathService;
use App\Services\RequestValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\FinancialException;

/**
 * Hardened Multi-Currency & Budget Reconciliation Service.
 * Aggregates expenditures across multi-currency transaction splits using transaction-snapshotted
 * historical rates, prevents date-boundary rollover drift with DateTimeImmutable,
 * and maintains budget envelope invariants.
 */
class BudgetService
{
    /**
     * Computes deterministic start and end date timestamps for a budget period.
     * Prevents month-end rollover bugs (e.g. Leap years, 28/29/30/31 day months).
     */
    public static function getMonthDateRange(string $month): array
    {
        $validatedMonth = RequestValidator::validateMonthPeriod($month);
        $startDate = new DateTimeImmutable("{$validatedMonth}-01 00:00:00");
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_timestamp' => $startDate->format('Y-m-d H:i:s'),
            'end_timestamp' => $endDate->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Deterministically calculates the next YYYY-MM period without PHP modify() overflow.
     */
    public static function getNextMonthPeriod(string $month): string
    {
        $validated = RequestValidator::validateMonthPeriod($month);
        [$yearStr, $monthStr] = explode('-', $validated);
        $year = (int) $yearStr;
        $m = (int) $monthStr + 1;

        if ($m > 12) {
            $m = 1;
            $year++;
        }

        return sprintf('%04d-%02d', $year, $m);
    }

    /**
     * Deterministically calculates the prior YYYY-MM period without overflow.
     */
    public static function getPreviousMonthPeriod(string $month): string
    {
        $validated = RequestValidator::validateMonthPeriod($month);
        [$yearStr, $monthStr] = explode('-', $validated);
        $year = (int) $yearStr;
        $m = (int) $monthStr - 1;

        if ($m < 1) {
            $m = 12;
            $year--;
        }

        return sprintf('%04d-%02d', $year, $m);
    }

    /**
     * Retrieves monthly budget performance with multi-currency conversion
     * using TRANSACTION-RECORDED historical exchange rates.
     */
    public static function getMonthlyBudgetOverview(int $userId, string $month): array
    {
        $db = Database::getInstance()->getConnection();
        $dateRange = self::getMonthDateRange($month);

        // Fetch user base currency
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);
        $baseCurrencyId = (int) $baseCurrency['id'];

        // 1. Fetch all configured budgets for this user and month
        $stmt = $db->prepare("
            SELECT b.*, c.name as category_name, c.color, c.icon,
                   COALESCE(curr.code, 'USD') as currency_code,
                   COALESCE(curr.symbol, '$') as currency_symbol
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN currencies curr ON b.currency_id = curr.id
            WHERE b.user_id = ? AND b.month = ?
            ORDER BY c.name ASC
        ");
        $stmt->execute([$userId, $month]);
        $budgets = $stmt->fetchAll();

        // 2. Fetch all transaction splits in date range for this user's expense categories
        $stmtSplits = $db->prepare("
            SELECT 
                ts.id as split_id,
                ts.category_id,
                ts.amount as split_amount,
                t.id as transaction_id,
                t.currency_id as txn_currency_id,
                t.rate_applied,
                t.base_currency_id,
                t.settled_amount,
                t.total_amount,
                t.transaction_date,
                curr.exchange_rate as current_rate
            FROM transaction_splits ts
            JOIN transactions t ON ts.transaction_id = t.id
            LEFT JOIN currencies curr ON t.currency_id = curr.id
            WHERE t.user_id = ? 
              AND t.type = 'expense' 
              AND t.status = 'posted' 
              AND t.deleted_at IS NULL
              AND t.transaction_date BETWEEN ? AND ?
        ");
        $stmtSplits->execute([$userId, $dateRange['start_date'], $dateRange['end_date']]);
        $splits = $stmtSplits->fetchAll();

        // Index splits by category_id
        $splitsByCategory = [];
        foreach ($splits as $split) {
            $catId = (int) $split['category_id'];
            $splitsByCategory[$catId][] = $split;
        }

        // 3. Aggregate budget items using historical rates
        $result = [];
        $totalBudgetSum = '0.00';
        $totalSpentSum = '0.00';

        foreach ($budgets as $b) {
            $catId = (int) $b['category_id'];
            $budgetCurrencyId = (int) ($b['currency_id'] ?: $baseCurrencyId);
            $baseAmount = MathService::parseDecimal((string) $b['amount']);
            $rolledOver = MathService::parseDecimal((string) ($b['rolled_over_amount'] ?? '0.00'));
            $effectiveBudget = MathService::add($baseAmount, $rolledOver);

            $categorySplits = $splitsByCategory[$catId] ?? [];
            $categorySpent = '0.00';

            foreach ($categorySplits as $s) {
                $splitAmt = MathService::parseDecimal((string) $s['split_amount']);
                $txnCurrId = (int) $s['txn_currency_id'];

                if ($txnCurrId === $budgetCurrencyId) {
                    // Same currency, zero conversion error
                    $convertedSplit = $splitAmt;
                } else {
                    // Multi-currency conversion using the transaction's snapshotted rate
                    $rateApplied = (string) ($s['rate_applied'] ?: '1.000000');
                    $convertedSplit = MathService::mul($splitAmt, $rateApplied, MathService::SCALE_MONEY);
                }

                $categorySpent = MathService::add($categorySpent, $convertedSplit);
            }

            $remaining = MathService::sub($effectiveBudget, $categorySpent);
            $isOver = MathService::lt($remaining, '0.00');

            // Utilization percentage calculation
            $percent = 0.0;
            if (MathService::gt($effectiveBudget, '0.00')) {
                $ratio = MathService::div($categorySpent, $effectiveBudget, 4);
                $percent = (float) bcmul($ratio, '100', 1);
            } elseif (MathService::gt($categorySpent, '0.00')) {
                $percent = 100.0;
            }

            $totalBudgetSum = MathService::add($totalBudgetSum, $effectiveBudget);
            $totalSpentSum = MathService::add($totalSpentSum, $categorySpent);

            $result[] = [
                'id' => (int) $b['id'],
                'category_id' => $catId,
                'category_name' => $b['category_name'],
                'color' => $b['color'],
                'icon' => $b['icon'],
                'currency_code' => $b['currency_code'],
                'currency_symbol' => $b['currency_symbol'],
                'base_budget' => $baseAmount,
                'rolled_over_amount' => $rolledOver,
                'effective_budget' => $effectiveBudget,
                'spent_amount' => $categorySpent,
                'remaining_amount' => $remaining,
                'percent' => $percent,
                'is_over_budget' => $isOver,
                'carry_over' => (bool) ($b['carry_over'] ?? 0)
            ];
        }

        $totalRemaining = MathService::sub($totalBudgetSum, $totalSpentSum);
        $overallPercent = 0.0;
        if (MathService::gt($totalBudgetSum, '0.00')) {
            $ratio = MathService::div($totalSpentSum, $totalBudgetSum, 4);
            $overallPercent = (float) bcmul($ratio, '100', 1);
        }

        return [
            'month' => $month,
            'date_range' => $dateRange,
            'budgets' => $result,
            'total_budget' => $totalBudgetSum,
            'total_spent' => $totalSpentSum,
            'total_remaining' => $totalRemaining,
            'overall_percent' => $overallPercent,
            'base_currency' => $baseCurrency
        ];
    }

    /**
     * Upserts a budget record with ownership verification and strict decimal typing.
     */
    public static function upsertBudget(
        int $userId,
        int $categoryId,
        string $month,
        string|float $amount,
        ?int $currencyId = null,
        string $period = 'monthly',
        bool $carryOver = false
    ): bool {
        $db = Database::getInstance()->getConnection();

        // 1. Verify category ownership
        RequestValidator::verifyCategoryOwnership($db, $categoryId, $userId);

        // 2. Validate amount and month
        $validatedAmount = RequestValidator::validateAmount($amount, 'budget_amount');
        $validatedMonth = RequestValidator::validateMonthPeriod($month);

        if (!$currencyId) {
            $baseCurr = CurrencyService::getUserBaseCurrency($userId);
            $currencyId = (int) $baseCurr['id'];
        }

        $stmt = $db->prepare("
            INSERT INTO budgets (user_id, category_id, month, amount, currency_id, period, carry_over) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount),
                currency_id = VALUES(currency_id),
                period = VALUES(period),
                carry_over = VALUES(carry_over),
                updated_at = NOW()
        ");

        return $stmt->execute([
            $userId,
            $categoryId,
            $validatedMonth,
            $validatedAmount,
            $currencyId,
            $period,
            $carryOver ? 1 : 0
        ]);
    }

    /**
     * Calculates carry-over surplus from previous month and applies to next month.
     */
    public static function applyCarryOver(int $userId, string $fromMonth): array
    {
        $overview = self::getMonthlyBudgetOverview($userId, $fromMonth);
        $nextMonth = self::getNextMonthPeriod($fromMonth);
        $db = Database::getInstance()->getConnection();
        $applied = [];

        foreach ($overview['budgets'] as $b) {
            if (!$b['carry_over']) {
                continue;
            }

            // If surplus exists (remaining > 0), roll over
            $surplus = $b['remaining_amount'];
            if (MathService::gt($surplus, '0.00')) {
                // Add to next month's rolled_over_amount
                $catId = $b['category_id'];
                $currId = (int) ($b['currency_id'] ?? 1);

                $stmt = $db->prepare("
                    INSERT INTO budgets (user_id, category_id, month, amount, rolled_over_amount, currency_id, carry_over)
                    VALUES (?, ?, ?, '0.00', ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                        rolled_over_amount = ?,
                        updated_at = NOW()
                ");
                $stmt->execute([$userId, $catId, $nextMonth, $surplus, $currId, $surplus]);

                $applied[] = [
                    'category_id' => $catId,
                    'category_name' => $b['category_name'],
                    'surplus_rolled' => $surplus,
                    'to_month' => $nextMonth
                ];
            }
        }

        return $applied;
    }
}
