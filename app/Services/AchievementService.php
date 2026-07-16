<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Models\UserAchievement;

class AchievementService
{
    public static function syncAll(int $userId): void
    {
        $db = Database::getInstance()->getConnection();
        $txnCounts = $db->prepare("SELECT type, COUNT(*) as c FROM transactions WHERE user_id = ? AND deleted_at IS NULL GROUP BY type");
        $txnCounts->execute([$userId]);
        $types = array_column($txnCounts->fetchAll(), 'c', 'type');

        self::check('first_expense', $userId, ($types['expense'] ?? 0) > 0 ? 1 : 0, 1);
        self::check('first_income', $userId, ($types['income'] ?? 0) > 0 ? 1 : 0, 1);

        $salaryCount = $db->prepare("SELECT COUNT(*) FROM salaries WHERE user_id = ?");
        $salaryCount->execute([$userId]);
        self::check('first_salary', $userId, (int) $salaryCount->fetchColumn() > 0 ? 1 : 0, 1);

        $budgetCount = $db->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = ?");
        $budgetCount->execute([$userId]);
        self::check('first_budget', $userId, (int) $budgetCount->fetchColumn() > 0 ? 1 : 0, 1);

        $vaultCount = $db->prepare("SELECT COUNT(*) FROM savings_vaults WHERE user_id = ?");
        $vaultCount->execute([$userId]);
        $vCount = (int) $vaultCount->fetchColumn();
        self::check('first_vault', $userId, $vCount > 0 ? 1 : 0, 1);
        self::check('multi_vaults', $userId, min($vCount, 3), 3);


        $totalSaved = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM vault_transactions WHERE user_id = ? AND type = 'deposit'");
        $totalSaved->execute([$userId]);
        $saved = (float) $totalSaved->fetchColumn();
        self::check('saved_1k', $userId, min($saved, 1000), 1000);
        self::check('saved_10k', $userId, min($saved, 10000), 10000);
        self::check('saved_50k', $userId, min($saved, 50000), 50000);
        self::check('saved_100k', $userId, min($saved, 100000), 100000);

        $completedGoals = $db->prepare("SELECT COUNT(*) FROM savings_vaults WHERE user_id = ? AND status = 'completed'");
        $completedGoals->execute([$userId]);
        $cGoals = (int) $completedGoals->fetchColumn();
        self::check('first_goal_completed', $userId, $cGoals > 0 ? 1 : 0, 1);
        self::check('multiple_goals_completed', $userId, min($cGoals, 5), 5);

        $totalTxns = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND deleted_at IS NULL");
        $totalTxns->execute([$userId]);
        $tCount = (int) $totalTxns->fetchColumn();
        self::check('txn_100', $userId, min($tCount, 100), 100);
        self::check('txn_500', $userId, min($tCount, 500), 500);
        self::check('txn_1000', $userId, min($tCount, 1000), 1000);

        $accCount = $db->prepare("SELECT COUNT(*) FROM accounts WHERE user_id = ? AND deleted_at IS NULL");
        $accCount->execute([$userId]);
        self::check('multi_accounts', $userId, min((int) $accCount->fetchColumn(), 3), 3);
    }

    private static function check(string $slug, int $userId, float $progress, float $target): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM achievements WHERE slug = ?");
        $stmt->execute([$slug]);
        $ach = $stmt->fetch();
        if ($ach) {
            UserAchievement::updateProgress($userId, (int) $ach['id'], $progress, $target);
        }
    }
}