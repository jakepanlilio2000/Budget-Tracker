<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Cache;
use App\Models\CurrencyService;

class FinancialSummaryEngine
{

    public static function getSummary(int $userId, ?string $periodStart = null, ?string $periodEnd = null): array
    {
        $cacheKey = "fin_summary_{$userId}_" . ($periodStart ?? 'all') . "_" . ($periodEnd ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($userId, $periodStart, $periodEnd) {
            $db = Database::getInstance()->getConnection();
            $baseCurrency = CurrencyService::getUserBaseCurrency($userId);
            $currId = $baseCurrency['id'];
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(current_balance), 0) as total_assets 
                FROM accounts WHERE user_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$userId]);
            $accountAssets = (float) $stmt->fetchColumn();

            $stmt = $db->prepare("
                SELECT COALESCE(SUM(current_amount), 0) as vault_assets 
                FROM savings_vaults WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$userId]);
            $vaultAssets = (float) $stmt->fetchColumn();

            $totalAssets = $accountAssets + $vaultAssets;
            $dateCondition = "";
            $params = [$userId];
            if ($periodStart && $periodEnd) {
                $dateCondition = "AND transaction_date BETWEEN ? AND ?";
                $params[] = $periodStart;
                $params[] = $periodEnd;
            }

            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN type = 'income' THEN total_amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as total_expense
                FROM transactions 
                WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL AND currency_id = ?
                " . $dateCondition . "
            ");
            $stmt->execute($params);
            $flow = $stmt->fetch();
            $totalIncome = (float) $flow['total_income'];
            $totalExpense = (float) $flow['total_expense'];
            $netIncome = $totalIncome - $totalExpense;
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM vault_transactions WHERE user_id = ? AND type = 'deposit'");
            $stmt->execute([$userId]);
            $totalSavingsDeposited = (float) $stmt->fetchColumn();
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as recurring_total 
                FROM recurring_incomes 
                WHERE user_id = ? AND status = 'active' AND currency_id = ?
                AND (end_date IS NULL OR end_date >= ?) AND start_date <= ?
            ");
            $stmt->execute([$userId, $currId, $periodEnd ?? date('Y-m-d'), $periodEnd ?? date('Y-m-d')]);

            $stmt = $db->prepare("SELECT COUNT(*) FROM savings_vaults WHERE user_id = ? AND status = 'completed'");
            $stmt->execute([$userId]);
            $completedGoals = (int) $stmt->fetchColumn();
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_bills,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_bills,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_bills,
                    SUM(CASE WHEN status = 'active' AND next_due_date >= CURRENT_DATE THEN total_amount ELSE 0 END) as upcoming_balance
                FROM bills WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $bills = $stmt->fetch();


            $netWorth = $totalAssets;
            $recurringIncome = (float) $stmt->fetchColumn();

            return [
                'currency' => $baseCurrency['symbol'],
                'assets' => [
                    'total' => $totalAssets,
                    'accounts' => $accountAssets,
                    'vaults' => $vaultAssets
                ],
                'income' => [
                    'total' => $totalIncome,
                    'recurring' => $recurringIncome,
                    'manual' => $totalIncome - $recurringIncome
                ],
                'expenses' => [
                    'total' => $totalExpense,
                    'recurring' => 0.00,
                    'manual' => $totalExpense
                ],
                'savings' => [
                    'total_deposited' => $totalSavingsDeposited,
                    'completed_goals' => $completedGoals,
                    'current_active_balance' => $vaultAssets
                ],
                'bills' => [
                    'total' => (int) $bills['total_bills'],
                    'paid' => (int) $bills['paid_bills'],
                    'overdue' => (int) $bills['overdue_bills'],
                    'upcoming_balance' => (float) $bills['upcoming_balance']
                ],
                'cash_flow' => [
                    'inflow' => $totalIncome,
                    'outflow' => $totalExpense,
                    'net' => $netIncome
                ],
                'net_worth' => $netWorth,
                'ratios' => [
                    'savings_rate' => $totalIncome > 0 ? round(($totalSavingsDeposited / $totalIncome) * 100, 1) : 0,
                    'expense_ratio' => $totalIncome > 0 ? round(($totalExpense / $totalIncome) * 100, 1) : 0
                ]
            ];
        });
    }

    public static function invalidateCache(int $userId): void
    {
        Cache::forget("dashboard_stats_{$userId}");
        Cache::forget("lifetime_stats_{$userId}");
    }
}