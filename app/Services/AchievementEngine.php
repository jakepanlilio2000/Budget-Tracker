<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Exception;
use DateTimeImmutable;
use App\Core\Database;
use App\Core\Logger;
use App\Events\LedgerEvent;
use App\Events\BudgetEvent;

/**
 * Enterprise Gamification & Achievement Progression Engine.
 * Decoupled, state-driven, idempotent, and exploit-resistant.
 * Enforces mathematical leveling curves, immutable audit trails, and reversal clawbacks.
 */
class AchievementEngine
{
    /**
     * Leveling formula: Required FXP for level L:
     * Required FXP(L) = floor(100 * L^1.5)
     */
    public static function getRequiredFxpForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }
        return (int) floor(100 * pow($level - 1, 1.5));
    }

    /**
     * Next Level Target: The total cumulative FXP needed to transition from level L to L+1
     */
    public static function getNextLevelThreshold(int $level): int
    {
        return (int) floor(100 * pow($level, 1.5));
    }

    /**
     * Calculate user level from total lifetime FXP using the inverse power curve.
     */
    public static function calculateLevelFromFxp(int $totalFxp): int
    {
        if ($totalFxp <= 0) {
            return 1;
        }
        $level = 1;
        while ($totalFxp >= self::getNextLevelThreshold($level)) {
            $level++;
            if ($level >= 500) { // Safety ceiling
                break;
            }
        }
        return $level;
    }

    /**
     * Returns rich progress metrics for real-time UI display.
     */
    public static function getLevelProgress(int $totalFxp): array
    {
        $currentLevel = self::calculateLevelFromFxp($totalFxp);
        $currentBaseFxp = self::getRequiredFxpForLevel($currentLevel);
        $nextThresholdFxp = self::getNextLevelThreshold($currentLevel);
        
        $fxpInLevel = max(0, $totalFxp - $currentBaseFxp);
        $span = max(1, $nextThresholdFxp - $currentBaseFxp);
        $percent = min(100.0, round(($fxpInLevel / $span) * 100, 1));

        return [
            'level' => $currentLevel,
            'total_fxp' => $totalFxp,
            'current_base_fxp' => $currentBaseFxp,
            'next_threshold_fxp' => $nextThresholdFxp,
            'fxp_in_level' => $fxpInLevel,
            'fxp_needed' => max(0, $nextThresholdFxp - $totalFxp),
            'progress_percent' => $percent,
            'title' => self::getWealthTitle($currentLevel)
        ];
    }

    public static function getWealthTitle(int $level): string
    {
        return match (true) {
            $level >= 50 => 'Sovereign Titan',
            $level >= 40 => 'Grand Capitalist',
            $level >= 30 => 'Financial Vanguard',
            $level >= 20 => 'Master Disciplinarian',
            $level >= 15 => 'Wealth Strategist',
            $level >= 10 => 'Budget Architect',
            $level >= 5  => 'Prudent Saver',
            default      => 'Financial Novice'
        };
    }

    /**
     * Decoupled Event Dispatcher entry point.
     * Evaluates relevant track milestones without blocking core financial mutations.
     */
    public static function dispatch(string $eventType, int $userId, array $payload = []): array
    {
        $unlocks = [];
        try {
            switch ($eventType) {
                case LedgerEvent::CREATED:
                    $unlocks = array_merge(
                        $unlocks,
                        self::evaluateReconciliationStreaks($userId),
                        self::evaluateSavingsVelocity($userId),
                        self::evaluateLiquidityResilience($userId)
                    );
                    break;

                case LedgerEvent::REVERSED:
                case LedgerEvent::DELETED:
                    $txnId = (int) ($payload['transaction_id'] ?? 0);
                    if ($txnId > 0) {
                        self::handleTransactionReversal($userId, $txnId);
                    }
                    break;

                case BudgetEvent::CYCLE_COMPLETED:
                case BudgetEvent::ALLOCATED:
                    $unlocks = array_merge($unlocks, self::evaluateBudgetStreaks($userId));
                    break;

                case LedgerEvent::RECONCILED:
                    $unlocks = array_merge($unlocks, self::evaluateReconciliationStreaks($userId));
                    break;
            }

            self::syncUser($userId);
        } catch (\Throwable $e) {
            Logger::error("Gamification dispatch error for event {$eventType}: " . $e->getMessage(), [
                'user_id' => $userId,
                'payload' => $payload
            ]);
        }

        return $unlocks;
    }

    /**
     * Synchronizes and recalculates the user's gamification state idempotently.
     */
    public static function syncUser(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $result = [
            'leveled_up' => false,
            'new_level' => 1,
            'total_xp' => 0,
            'unlocks' => [],
            'progress' => []
        ];

        try {
            // 1. Calculate active unrevoked FXP
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(fxp_awarded), 0) as total_awarded 
                FROM user_achievements 
                WHERE user_id = ? AND is_revoked = 0 AND unlocked_at IS NOT NULL
            ");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            $achievementFxp = (int) ($row['total_awarded'] ?? 0);

            // Fetch current stats
            $stmtStats = $db->prepare("SELECT total_xp, level, current_streak FROM user_financial_stats WHERE user_id = ?");
            $stmtStats->execute([$userId]);
            $stats = $stmtStats->fetch();

            $oldLevel = (int) ($stats['level'] ?? 1);
            $totalFxp = max($achievementFxp, (int) ($stats['total_xp'] ?? 0));
            $newLevel = self::calculateLevelFromFxp($totalFxp);

            // Compute actual streak of distinct calendar days
            $streakDays = self::calculateCalendarLoggingStreak($userId);
            $wealthTier = self::getWealthTitle($newLevel);

            // Upsert user_financial_stats
            $stmtUp = $db->prepare("
                INSERT INTO user_financial_stats (user_id, total_xp, level, current_streak, wealth_tier, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    total_xp = VALUES(total_xp),
                    level = VALUES(level),
                    current_streak = VALUES(current_streak),
                    wealth_tier = VALUES(wealth_tier),
                    updated_at = NOW()
            ");
            $stmtUp->execute([$userId, $totalFxp, $newLevel, $streakDays, $wealthTier]);

            // Unlocks in last 30 seconds for toast notifications
            $stmtRecent = $db->prepare("
                SELECT ua.achievement_key as slug, ad.name, ad.icon, ad.color, ua.fxp_awarded as xp_value
                FROM user_achievements ua
                LEFT JOIN achievement_definitions ad ON ua.achievement_key = ad.slug
                WHERE ua.user_id = ? AND ua.is_revoked = 0 AND ua.unlocked_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
            ");
            $stmtRecent->execute([$userId]);
            $recentUnlocks = $stmtRecent->fetchAll();

            $result['total_xp'] = $totalFxp;
            $result['new_level'] = $newLevel;
            $result['leveled_up'] = ($newLevel > $oldLevel);
            $result['unlocks'] = $recentUnlocks;
            $result['progress'] = self::getLevelProgress($totalFxp);

        } catch (\Throwable $e) {
            Logger::error("Achievement sync failed for user #{$userId}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Unlocks an achievement idempotently.
     * Prevents CRUD farming: returns null if already unlocked and active.
     */
    public static function unlockAchievement(
        int $userId,
        string $achievementKey,
        int $fxpReward,
        ?string $sourceReferenceId = null
    ): ?array {
        $db = Database::getInstance()->getConnection();

        // Check existing state
        $stmt = $db->prepare("
            SELECT id, is_revoked, unlocked_at 
            FROM user_achievements 
            WHERE user_id = ? AND achievement_key = ?
        ");
        $stmt->execute([$userId, $achievementKey]);
        $existing = $stmt->fetch();

        if ($existing && (int)$existing['is_revoked'] === 0 && !empty($existing['unlocked_at'])) {
            // Already unlocked and active: zero double-award
            return null;
        }

        // Fetch definition metadata
        $stmtDef = $db->prepare("SELECT id, name, icon, color, xp_value FROM achievement_definitions WHERE slug = ?");
        $stmtDef->execute([$achievementKey]);
        $def = $stmtDef->fetch();

        $defId = $def ? (int)$def['id'] : null;
        $finalFxp = $def ? (int)$def['xp_value'] : $fxpReward;

        $db->beginTransaction();
        try {
            // Insert or unrevoke
            $stmtSave = $db->prepare("
                INSERT INTO user_achievements 
                    (user_id, achievement_id, achievement_key, progress, target, unlocked_at, source_reference_id, fxp_awarded, is_revoked)
                VALUES 
                    (?, ?, ?, 100.00, 100.00, NOW(), ?, ?, 0)
                ON DUPLICATE KEY UPDATE 
                    achievement_id = VALUES(achievement_id),
                    progress = 100.00,
                    target = 100.00,
                    unlocked_at = NOW(),
                    source_reference_id = VALUES(source_reference_id),
                    fxp_awarded = VALUES(fxp_awarded),
                    is_revoked = 0,
                    revoked_at = NULL,
                    updated_at = NOW()
            ");
            $stmtSave->execute([$userId, $defId, $achievementKey, $sourceReferenceId, $finalFxp]);

            // Record in audit fxp_ledger
            $stmtLedger = $db->prepare("
                INSERT INTO fxp_ledger (user_id, event_type, reference_id, fxp_amount, created_at)
                VALUES (?, 'achievement_unlock', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE fxp_amount = VALUES(fxp_amount)
            ");
            $stmtLedger->execute([$userId, $achievementKey, $finalFxp]);

            $db->commit();

            return [
                'slug' => $achievementKey,
                'name' => $def['name'] ?? ucwords(str_replace('_', ' ', $achievementKey)),
                'icon' => $def['icon'] ?? 'fa-trophy',
                'color' => $def['color'] ?? '#10b981',
                'fxp_awarded' => $finalFxp
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("Failed to unlock achievement {$achievementKey}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Anti-Exploit Reversal Workflow:
     * When a transaction is reversed or deleted, claw back any achievement derived from it.
     */
    public static function handleTransactionReversal(int $userId, int $transactionId): array
    {
        $db = Database::getInstance()->getConnection();
        $sourceRef = 'txn_' . $transactionId;

        $stmt = $db->prepare("
            SELECT achievement_key, fxp_awarded 
            FROM user_achievements 
            WHERE user_id = ? AND source_reference_id = ? AND is_revoked = 0
        ");
        $stmt->execute([$userId, $sourceRef]);
        $revokedList = $stmt->fetchAll();

        if (empty($revokedList)) {
            return [];
        }

        $db->beginTransaction();
        try {
            $totalClawedBack = 0;
            foreach ($revokedList as $item) {
                $key = $item['achievement_key'];
                $fxp = (int)$item['fxp_awarded'];
                $totalClawedBack += $fxp;

                // Revoke achievement
                $stmtRev = $db->prepare("
                    UPDATE user_achievements 
                    SET is_revoked = 1, revoked_at = NOW(), updated_at = NOW() 
                    WHERE user_id = ? AND achievement_key = ?
                ");
                $stmtRev->execute([$userId, $key]);

                // Record negative adjustment in audit ledger
                $stmtLedger = $db->prepare("
                    INSERT INTO fxp_ledger (user_id, event_type, reference_id, fxp_amount, created_at)
                    VALUES (?, 'achievement_clawback', ?, ?, NOW())
                ");
                $stmtLedger->execute([$userId, $key . '_rev_' . $transactionId, -$fxp]);
            }

            // Decrement user_financial_stats total_xp
            $stmtStats = $db->prepare("
                UPDATE user_financial_stats 
                SET total_xp = GREATEST(0, total_xp - ?), updated_at = NOW() 
                WHERE user_id = ?
            ");
            $stmtStats->execute([$totalClawedBack, $userId]);

            $db->commit();
            self::syncUser($userId);

            return $revokedList;
        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("Failed to execute transaction gamification reversal: " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // STRUCTURED 4-TRACK MILESTONE EVALUATORS
    // =========================================================================

    /**
     * TRACK 1: Budget Disciplinarian
     * Evaluates consecutive closed monthly cycles without exceeding budgets.
     */
    public static function evaluateBudgetStreaks(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $unlocked = [];

        // Fetch monthly budget vs spent history for closed months (excluding current month)
        $currentMonth = date('Y-m');
        $stmt = $db->prepare("
            SELECT b.month,
                   SUM(b.amount + b.rolled_over_amount) as total_cap,
                   COALESCE(SUM(t.spent), 0) as total_spent
            FROM budgets b
            LEFT JOIN (
                SELECT ts.category_id, 
                       DATE_FORMAT(t.transaction_date, '%Y-%m') as txn_month,
                       SUM(ts.amount) as spent
                FROM transaction_splits ts
                JOIN transactions t ON ts.transaction_id = t.id
                WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
                GROUP BY ts.category_id, txn_month
            ) t ON b.category_id = t.category_id AND b.month = t.txn_month
            WHERE b.user_id = ? AND b.month < ?
            GROUP BY b.month
            ORDER BY b.month DESC
        ");
        $stmt->execute([$userId, $userId, $currentMonth]);
        $months = $stmt->fetchAll();

        $consecutiveCleanMonths = 0;
        foreach ($months as $m) {
            $cap = (float)$m['total_cap'];
            $spent = (float)$m['total_spent'];
            if ($cap > 0 && $spent <= $cap) {
                $consecutiveCleanMonths++;
            } else {
                break; // Streak broken
            }
        }

        if ($consecutiveCleanMonths >= 1) {
            $u = self::unlockAchievement($userId, 'budget_disciplinarian_1m', 150, "streak_{$consecutiveCleanMonths}m");
            if ($u) $unlocked[] = $u;
        }
        if ($consecutiveCleanMonths >= 3) {
            $u = self::unlockAchievement($userId, 'budget_disciplinarian_3m', 500, "streak_{$consecutiveCleanMonths}m");
            if ($u) $unlocked[] = $u;
        }
        if ($consecutiveCleanMonths >= 6) {
            $u = self::unlockAchievement($userId, 'budget_disciplinarian_6m', 1200, "streak_{$consecutiveCleanMonths}m");
            if ($u) $unlocked[] = $u;
        }

        return $unlocked;
    }

    /**
     * TRACK 2: Savings Velocity
     * Evaluates net savings rate >= 20%, 40%, 60% over monthly cycle.
     */
    public static function evaluateSavingsVelocity(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $unlocked = [];

        $currentMonth = date('Y-m');
        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense
            FROM transactions
            WHERE user_id = ? 
              AND status = 'posted' 
              AND deleted_at IS NULL
              AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $currentMonth]);
        $flow = $stmt->fetch();

        $income = (float)($flow['total_income'] ?? 0);
        $expense = (float)($flow['total_expense'] ?? 0);

        if ($income > 0) {
            $netSavings = $income - $expense;
            $savingsRate = ($netSavings / $income) * 100.0;

            if ($savingsRate >= 20.0) {
                $u = self::unlockAchievement($userId, 'savings_velocity_20', 200, "rate_{$currentMonth}");
                if ($u) $unlocked[] = $u;
            }
            if ($savingsRate >= 40.0) {
                $u = self::unlockAchievement($userId, 'savings_velocity_40', 600, "rate_{$currentMonth}");
                if ($u) $unlocked[] = $u;
            }
            if ($savingsRate >= 60.0) {
                $u = self::unlockAchievement($userId, 'savings_velocity_60', 1500, "rate_{$currentMonth}");
                if ($u) $unlocked[] = $u;
            }
        }

        return $unlocked;
    }

    /**
     * TRACK 3: Reconciliation Master
     * Evaluates consecutive distinct calendar days logging transactions.
     * Uses DATE(transaction_date) to prevent time-spoofing and duplicate intraday logging.
     */
    public static function evaluateReconciliationStreaks(int $userId): array
    {
        $streak = self::calculateCalendarLoggingStreak($userId);
        $unlocked = [];

        if ($streak >= 7) {
            $u = self::unlockAchievement($userId, 'reconciliation_master_7d', 100, "streak_{$streak}d");
            if ($u) $unlocked[] = $u;
        }
        if ($streak >= 30) {
            $u = self::unlockAchievement($userId, 'reconciliation_master_30d', 450, "streak_{$streak}d");
            if ($u) $unlocked[] = $u;
        }
        if ($streak >= 90) {
            $u = self::unlockAchievement($userId, 'reconciliation_master_90d', 1500, "streak_{$streak}d");
            if ($u) $unlocked[] = $u;
        }

        return $unlocked;
    }

    /**
     * TRACK 4: Debt & Liquidity Resilience
     * Evaluates emergency vault reserve covering >= 3 months and >= 6 months of baseline expenses.
     */
    public static function evaluateLiquidityResilience(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $unlocked = [];

        // 1. Total active vault reserves
        $stmtVault = $db->prepare("
            SELECT COALESCE(SUM(current_amount), 0) as total_reserves 
            FROM vaults 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmtVault->execute([$userId]);
        $vaultRow = $stmtVault->fetch();
        $reserves = (float)($vaultRow['total_reserves'] ?? 0);

        if ($reserves <= 0) {
            return [];
        }

        // 2. Average monthly expense over past 3 months
        $stmtExp = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) / 3.0 as avg_monthly_expense
            FROM transactions
            WHERE user_id = ? 
              AND type = 'expense' 
              AND status = 'posted' 
              AND deleted_at IS NULL
              AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ");
        $stmtExp->execute([$userId]);
        $expRow = $stmtExp->fetch();
        $avgExpense = (float)($expRow['avg_monthly_expense'] ?? 0);

        if ($avgExpense > 0) {
            $coverageMonths = $reserves / $avgExpense;

            if ($coverageMonths >= 3.0) {
                $u = self::unlockAchievement($userId, 'liquidity_resilience_3m', 800, "coverage_" . round($coverageMonths, 1));
                if ($u) $unlocked[] = $u;
            }
            if ($coverageMonths >= 6.0) {
                $u = self::unlockAchievement($userId, 'liquidity_resilience_6m', 2000, "coverage_" . round($coverageMonths, 1));
                if ($u) $unlocked[] = $u;
            }
        }

        return $unlocked;
    }

    /**
     * Compute actual consecutive calendar day logging streak.
     * Guaranteed immune to multiple transactions on the same day.
     */
    public static function calculateCalendarLoggingStreak(int $userId): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT DISTINCT DATE(transaction_date) as log_date 
            FROM transactions 
            WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL 
            ORDER BY log_date DESC 
            LIMIT 180
        ");
        $stmt->execute([$userId]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($dates)) {
            return 0;
        }

        $streak = 0;
        $expectedDate = new DateTimeImmutable('today');
        
        // If no transaction today yet, check if yesterday was logged to keep streak active
        $firstDate = new DateTimeImmutable($dates[0]);
        $diffFromToday = (int) $expectedDate->diff($firstDate)->format('%r%a');

        if ($diffFromToday === 0) {
            $streak = 1;
            $currentCheck = $expectedDate->modify('-1 day');
        } elseif ($diffFromToday === -1) {
            $streak = 1;
            $currentCheck = $expectedDate->modify('-2 day');
        } else {
            return 0; // Streak broken
        }

        $count = count($dates);
        for ($i = 1; $i < $count; $i++) {
            $actualDate = $dates[$i];
            if ($actualDate === $currentCheck->format('Y-m-d')) {
                $streak++;
                $currentCheck = $currentCheck->modify('-1 day');
            } else {
                break;
            }
        }

        return $streak;
    }
}