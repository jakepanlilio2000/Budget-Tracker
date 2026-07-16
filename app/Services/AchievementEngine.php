<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class AchievementEngine
{
    public static function syncUser(int $userId): array
    {
        $result = ['leveled_up' => false, 'new_level' => 1, 'total_xp' => 0, 'unlocks' => []];

        try {
            $stats = self::calculateStats($userId);
            self::saveStats($userId, $stats);

            $netWorth = (float) ($stats['netWorth'] ?? 0);
            $tier = self::getWealthTier($netWorth);

            $db = Database::getInstance()->getConnection();
            $db->prepare("UPDATE user_financial_stats SET wealth_tier = ? WHERE user_id = ?")->execute([$tier, $userId]);

            $ruleMap = [
                'net_worth_reach' => $netWorth,
                'total_savings_reach' => (float) ($stats['totalSavings'] ?? 0),
                'transaction_count_reach' => (int) ($stats['txnCount'] ?? 0),
                'account_count_reach' => (int) ($stats['accCount'] ?? 0),
                'vault_count_reach' => (int) ($stats['vaultCount'] ?? 0),
                'completed_vaults_reach' => (int) ($stats['completedVaults'] ?? 0),
                'budget_count_reach' => (int) ($stats['budgetCount'] ?? 0),
                'bills_paid_reach' => (int) ($stats['billsPaid'] ?? 0),
                'daily_log_count_reach' => (int) ($stats['dailyLogCount'] ?? 0),
                'pending_paid_count_reach' => (int) ($stats['pendingPaidCount'] ?? 0),
                'salary_count_reach' => (int) ($stats['salaryCount'] ?? 0),
                'savings_ratio_reach' => (float) ($stats['savingsRatio'] ?? 0), // <-- ADDED
            ];

            foreach ($ruleMap as $type => $value) {
                self::evaluateBatch($userId, $type, (float) $value);
            }

            $chainRuleMap = [
                'transaction_count_reach' => (int) ($stats['txnCount'] ?? 0),
                'account_count_reach' => (int) ($stats['accCount'] ?? 0),
                'vault_count_reach' => (int) ($stats['vaultCount'] ?? 0),
                'completed_vaults_reach' => (int) ($stats['completedVaults'] ?? 0),
                'budget_count_reach' => (int) ($stats['budgetCount'] ?? 0),
                'bills_paid_reach' => (int) ($stats['billsPaid'] ?? 0),
                'daily_log_count_reach' => (int) ($stats['dailyLogCount'] ?? 0),
                'pending_paid_count_reach' => (int) ($stats['pendingPaidCount'] ?? 0),
                'salary_count_reach' => (int) ($stats['salaryCount'] ?? 0),
            ];

            foreach ($chainRuleMap as $type => $value) {
                self::evaluateChains($userId, $type, (float) $value);
            }

            $stmt = $db->prepare("SELECT level, total_xp FROM user_financial_stats WHERE user_id = ?");
            $stmt->execute([$userId]);
            $oldStats = $stmt->fetch();
            $oldLevel = (int) ($oldStats['level'] ?? 1);
            $stmt = $db->prepare("SELECT COALESCE(SUM(ad.xp_value), 0) as total_xp FROM user_achievements ua JOIN achievement_definitions ad ON ua.achievement_id = ad.id WHERE ua.user_id = ? AND ua.unlocked_at IS NOT NULL");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            $totalXp = (int) ($row['total_xp'] ?? 0);
            $newLevel = floor(sqrt($totalXp / 60)) + 1;

            $db->prepare("UPDATE user_financial_stats SET total_xp = ?, level = ? WHERE user_id = ?")->execute([$totalXp, $newLevel, $userId]);


            $result['total_xp'] = $totalXp;
            $result['new_level'] = $newLevel;
            $result['leveled_up'] = ($newLevel > $oldLevel);


            $stmt = $db->prepare("
                SELECT ad.name, ad.icon, ad.color, ad.xp_value 
                FROM user_achievements ua 
                JOIN achievement_definitions ad ON ua.achievement_id = ad.id 
                WHERE ua.user_id = ? AND ua.unlocked_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
            ");
            $stmt->execute([$userId]);
            $result['unlocks'] = $stmt->fetchAll();

        } catch (\Exception $e) {
            Logger::error("Achievement sync failed", ['error' => $e->getMessage()]);
        }

        return $result;
    }

    private static function calculateStats(int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT COALESCE(SUM(current_balance), 0) as net_worth FROM accounts WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $netWorth = (float) ($row['net_worth'] ?? 0);

        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_savings FROM vault_transactions WHERE user_id = ? AND type = 'deposit'");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $totalSavings = (float) ($row['total_savings'] ?? 0);

        $stmt = $db->prepare("SELECT COUNT(*) as txn_count FROM transactions WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $txnCount = (int) ($row['txn_count'] ?? 0);

        $stmt = $db->prepare("SELECT COUNT(*) as acc_count FROM accounts WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $accCount = (int) ($row['acc_count'] ?? 0);

        $stmt = $db->prepare("SELECT COUNT(*) as vault_count FROM savings_vaults WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $vaultCount = (int) ($row['vault_count'] ?? 0);

        $stmt = $db->prepare("SELECT COUNT(*) as completed_vaults FROM savings_vaults WHERE user_id = ? AND status = 'completed'");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $completedVaults = (int) ($row['completed_vaults'] ?? 0);

        $stmt = $db->prepare("SELECT COUNT(*) as budget_count FROM budgets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $budgetCount = (int) ($row['budget_count'] ?? 0);

        $stmt = $db->prepare("SELECT COUNT(*) as bills_paid FROM bill_payments WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $billsPaid = (int) ($row['bills_paid'] ?? 0);


        $stmt = $db->prepare("SELECT COUNT(*) as daily_log_count FROM daily_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $dailyLogCount = (int) ($row['daily_log_count'] ?? 0);


        $stmt = $db->prepare("SELECT COUNT(*) as pending_paid_count FROM pending_ledger WHERE user_id = ? AND status = 'paid'");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $pendingPaidCount = (int) ($row['pending_paid_count'] ?? 0);


        $stmt = $db->prepare("SELECT COUNT(*) as salary_count FROM salaries WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $salaryCount = (int) ($row['salary_count'] ?? 0);

        $stmt = $db->prepare("SELECT COALESCE(SUM(net_pay), 0) as total_earned FROM salaries WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $totalEarned = (float) ($row['total_earned'] ?? 0);

        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_income FROM transactions WHERE user_id = ? AND type = 'income' AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $totalIncome = (float) ($row['total_income'] ?? 0);

        $savingsRatio = ($totalIncome > 0) ? (($totalSavings / $totalIncome) * 100) : 0;

        return [
            'netWorth' => $netWorth,
            'totalSavings' => $totalSavings,
            'txnCount' => $txnCount,
            'accCount' => $accCount,
            'vaultCount' => $vaultCount,
            'completedVaults' => $completedVaults,
            'budgetCount' => $budgetCount,
            'billsPaid' => $billsPaid,
            'dailyLogCount' => $dailyLogCount,
            'pendingPaidCount' => $pendingPaidCount,
            'salaryCount' => $salaryCount,
            'totalEarned' => $totalEarned,
            'savingsRatio' => $savingsRatio,
            'totalIncome' => $totalIncome
        ];
    }

    private static function saveStats(int $userId, array $s): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO user_financial_stats (user_id, net_worth, total_savings, transaction_count, account_count, vault_count, completed_vaults)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            net_worth = VALUES(net_worth), total_savings = VALUES(total_savings), 
            transaction_count = VALUES(transaction_count), account_count = VALUES(account_count),
            vault_count = VALUES(vault_count), completed_vaults = VALUES(completed_vaults)
        ");
        $stmt->execute([
            $userId,
            $s['netWorth'],
            $s['totalSavings'],
            $s['txnCount'],
            $s['accCount'],
            $s['vaultCount'],
            $s['completedVaults']
        ]);
    }

    private static function evaluateBatch(int $userId, string $ruleType, float $currentValue): void
    {
        $db = Database::getInstance()->getConnection();

        $sql = "
            INSERT INTO user_achievements (user_id, achievement_id, progress, target, unlocked_at)
            SELECT 
                ?, 
                id, 
                LEAST(?, CAST(JSON_EXTRACT(rule_config, '$.target') AS DECIMAL(15,2))), 
                CAST(JSON_EXTRACT(rule_config, '$.target') AS DECIMAL(15,2)), 
                CASE WHEN ? >= CAST(JSON_EXTRACT(rule_config, '$.target') AS DECIMAL(15,2)) THEN NOW() ELSE NULL END
            FROM achievement_definitions
            WHERE rule_type = ? AND is_active = 1
            ON DUPLICATE KEY UPDATE 
                progress = VALUES(progress),
                unlocked_at = COALESCE(unlocked_at, VALUES(unlocked_at))
        ";

        $db->prepare($sql)->execute([$userId, $currentValue, $currentValue, $ruleType]);
    }

    private static function updateLevel(int $userId): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COALESCE(SUM(ad.xp_value), 0) as total_xp FROM user_achievements ua JOIN achievement_definitions ad ON ua.achievement_id = ad.id WHERE ua.user_id = ? AND ua.unlocked_at IS NOT NULL");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $totalXp = (int) ($row['total_xp'] ?? 0);
        $level = floor(sqrt($totalXp / 60)) + 1;

        $db->prepare("UPDATE user_financial_stats SET total_xp = ?, level = ? WHERE user_id = ?")->execute([$totalXp, $level, $userId]);
    }

    public static function getWealthTier(float $netWorth): string
    {
        if ($netWorth >= 1000000000)
            return 'Investor Elite';
        if ($netWorth >= 100000000)
            return 'Financial Freedom';
        if ($netWorth >= 10000000)
            return 'Multi-Millionaire';
        if ($netWorth >= 5000000)
            return 'Millionaire';
        if ($netWorth >= 1000000)
            return 'Wealthy';
        if ($netWorth >= 500000)
            return 'Prosperous';
        if ($netWorth >= 100000)
            return 'Growing';
        if ($netWorth >= 50000)
            return 'Comfortable';
        if ($netWorth >= 10000)
            return 'Stable';
        if ($netWorth >= 1000)
            return 'Surviving';
        return 'Broke';
    }

    private static function evaluateChains(int $userId, string $ruleType, float $currentValue): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, base_target, chain_multiplier FROM achievement_definitions WHERE rule_type = ? AND is_chain = 1");
        $stmt->execute([$ruleType]);
        $chains = $stmt->fetchAll();

        foreach ($chains as $chain) {
            // Get user's current chain progress
            $stmt = $db->prepare("SELECT chain_level, unlocked_at FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
            $stmt->execute([$userId, $chain['id']]);
            $userChain = $stmt->fetch();

            $chainLevel = $userChain ? (int) $userChain['chain_level'] : 1;
            $isUnlocked = $userChain && $userChain['unlocked_at'] !== null;
            $evalLevel = $isUnlocked ? $chainLevel + 1 : $chainLevel;
            $dynamicTarget = (float) $chain['base_target'] * pow((float) $chain['chain_multiplier'], $evalLevel - 1);
            $dynamicTarget = round($dynamicTarget, strpos($ruleType, 'count') !== false ? 0 : 2);

            $progress = min($currentValue, $dynamicTarget);
            $newlyUnlocked = ($currentValue >= $dynamicTarget) && !$isUnlocked;
            $unlockDate = $newlyUnlocked ? date('Y-m-d H:i:s') : ($userChain['unlocked_at'] ?? null);
            $saveLevel = $newlyUnlocked ? $evalLevel : $chainLevel;
            $stmt = $db->prepare("
                INSERT INTO user_achievements (user_id, achievement_id, chain_level, progress, target, unlocked_at)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    chain_level = VALUES(chain_level),
                    progress = VALUES(progress),
                    target = VALUES(target),
                    unlocked_at = COALESCE(unlocked_at, VALUES(unlocked_at))
            ");
            $stmt->execute([$userId, $chain['id'], $saveLevel, $progress, $dynamicTarget, $unlockDate]);
        }
    }
}