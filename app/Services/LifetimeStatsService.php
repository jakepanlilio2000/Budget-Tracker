<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Cache;
use App\Core\Auth;

class LifetimeStatsService
{
    public static function getStats(int $userId): array
    {
        return Cache::remember("lifetime_stats_{$userId}", 3600, function () use ($userId) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN type = 'income' THEN total_amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0) as total_expense,
                    COUNT(*) as total_transactions
                FROM transactions WHERE user_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$userId]);
            $core = $stmt->fetch();
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_savings FROM vault_transactions WHERE user_id = ? AND type = 'deposit'");
            $stmt->execute([$userId]);
            $totalSavings = (float) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_withdrawals FROM vault_transactions WHERE user_id = ? AND type = 'withdrawal'");
            $stmt->execute([$userId]);
            $totalWithdrawals = (float) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM savings_vaults WHERE user_id = ? AND status = 'completed'");
            $stmt->execute([$userId]);
            $goalsCompleted = (int) $stmt->fetchColumn();
            $stmt = $db->prepare("SELECT COUNT(*) FROM bill_payments WHERE user_id = ?");
            $stmt->execute([$userId]);
            $billsPaid = (int) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalBudgets = (int) $stmt->fetchColumn();

            // 4. Streaks & FXP
            $stmt = $db->prepare("SELECT MAX(best_streak) as longest_streak FROM user_streaks WHERE user_id = ?");
            $stmt->execute([$userId]);
            $longestStreak = (int) ($stmt->fetchColumn() ?? 0);

            $fxpStats = FxpEngine::getUserStats($userId);
            $stmt = $db->prepare("
                SELECT MAX(monthly_total) as highest_monthly_income FROM (
                    SELECT SUM(total_amount) as monthly_total 
                    FROM transactions 
                    WHERE user_id = ? AND type = 'income' AND deleted_at IS NULL 
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ) as sub
            ");
            $stmt->execute([$userId]);
            $highestMonthlyIncome = (float) ($stmt->fetchColumn() ?? 0);

            return [
                'total_income' => (float) $core['total_income'],
                'total_expense' => (float) $core['total_expense'],
                'total_transactions' => (int) $core['total_transactions'],
                'total_savings' => $totalSavings,
                'total_withdrawals' => $totalWithdrawals,
                'goals_completed' => $goalsCompleted,
                'bills_paid' => $billsPaid,
                'total_budgets' => $totalBudgets,
                'longest_streak' => $longestStreak,
                'highest_monthly_income' => $highestMonthlyIncome,
                'highest_level' => $fxpStats['global']['current_level'],
                'prestige_count' => $fxpStats['global']['prestige_stars'],
                'lifetime_fxp' => $fxpStats['global']['lifetime_fxp'],
                'active_days' => $longestStreak
            ];
        });
    }

    public static function clearCache(int $userId): void
    {
        Cache::forget("lifetime_stats_{$userId}");
    }
}