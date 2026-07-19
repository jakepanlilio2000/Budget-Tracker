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

            $totalIncome = (float) $core['total_income'];
            $totalExpense = (float) $core['total_expense'];

            $stmt = $db->prepare("SELECT COALESCE(SUM(net_pay), 0) FROM salaries WHERE user_id = ? AND status = 'paid'");
            $stmt->execute([$userId]);
            $totalIncome += (float) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COALESCE(SUM(total_spent), 0) FROM daily_logs WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalExpense += (float) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM bill_payments WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalExpense += (float) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM pending_ledger WHERE user_id = ? AND status = 'paid' AND type = 'expense'");
            $stmt->execute([$userId]);
            $totalExpense += (float) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM vault_transactions WHERE user_id = ? AND type = 'deposit'");
            $stmt->execute([$userId]);
            $totalSavings = (float) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM savings_vaults WHERE user_id = ? AND status = 'completed'");
            $stmt->execute([$userId]);
            $goalsCompleted = (int) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM bill_payments WHERE user_id = ?");
            $stmt->execute([$userId]);
            $billsPaid = (int) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalBudgets = (int) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT MAX(best_streak) as longest_streak FROM user_streaks WHERE user_id = ?");
            $stmt->execute([$userId]);
            $longestStreak = (int) ($stmt->fetchColumn() ?? 0);

            $fxpStats = \App\Services\FxpEngine::getUserStats($userId);

            return [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'total_transactions' => (int) $core['total_transactions'],
                'total_savings' => $totalSavings,
                'goals_completed' => $goalsCompleted,
                'bills_paid' => $billsPaid,
                'total_budgets' => $totalBudgets,
                'longest_streak' => $longestStreak,
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