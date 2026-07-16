<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class FxpEngine
{
    public static function award(int $userId, string $actionType, int $quantity = 1): array
    {
        $db = Database::getInstance()->getConnection();
        $result = ['xp_gained' => 0, 'leveled_up' => false, 'new_level' => 1, 'mastery_leveled_up' => false];

        try {
            $stmt = $db->prepare("SELECT xp_value, mastery_type FROM fxp_actions WHERE action_type = ? AND is_active = 1");
            $stmt->execute([$actionType]);
            $action = $stmt->fetch();

            if (!$action) {
                return $result;
            }
            $stmt = $db->prepare("SELECT lifetime_fxp, current_level, xp_multiplier FROM user_fxp_stats WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch();

            if (!$stats) {
                $db->prepare("INSERT INTO user_fxp_stats (user_id, lifetime_fxp, current_level, xp_multiplier) VALUES (?, 0, 1, 1.00)")->execute([$userId]);
                $stats = ['lifetime_fxp' => 0, 'current_level' => 1, 'xp_multiplier' => 1.00];
            }
            $baseXp = (int) $action['xp_value'];
            $xpGained = (int) floor($baseXp * $quantity * (float) $stats['xp_multiplier']);
            $newLifetimeFxp = (int) $stats['lifetime_fxp'] + $xpGained;
            $newLevel = (int) floor(pow($newLifetimeFxp / 100, 2 / 3)) + 1;
            $leveledUp = $newLevel > (int) $stats['current_level'];
            $db->prepare("
                INSERT INTO user_fxp_stats (user_id, lifetime_fxp, current_level) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE lifetime_fxp = VALUES(lifetime_fxp), current_level = VALUES(current_level)
            ")->execute([$userId, $newLifetimeFxp, $newLevel]);

            $result['xp_gained'] = $xpGained;
            $result['new_level'] = $newLevel;
            $result['leveled_up'] = $leveledUp;

            $result['rewards'] = [];
            if ($leveledUp && $newLevel % 10 === 0) {
                $result['rewards'][] = [
                    'type' => 'title',
                    'name' => 'Decade Master',
                    'description' => 'Reached Level ' . $newLevel,
                    'icon' => 'fa-crown',
                    'color' => '#f59e0b'
                ];
            }

            $masteryType = $action['mastery_type'];
            if ($masteryType !== 'general') {
                $result['mastery_leveled_up'] = self::updateMastery($userId, $masteryType, $xpGained);
            }

            return $result;
        } catch (\Exception $e) {
            Logger::error("FXP Engine failed", ['error' => $e->getMessage(), 'action' => $actionType]);
            return $result;
        }
    }

    private static function updateMastery(int $userId, string $masteryType, int $xpGained): bool
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT level, current_xp FROM user_mastery_stats WHERE user_id = ? AND mastery_type = ?");
        $stmt->execute([$userId, $masteryType]);
        $mastery = $stmt->fetch();

        if (!$mastery) {
            $mastery = ['level' => 1, 'current_xp' => 0];
        }

        $newXp = (int) $mastery['current_xp'] + $xpGained;
        $xpRequiredForNextLevel = 500 * (int) $mastery['level'];
        $leveledUp = false;
        $newLevel = (int) $mastery['level'];

        if ($newXp >= $xpRequiredForNextLevel) {
            $newLevel++;
            $leveledUp = true;
        }

        $db->prepare("
            INSERT INTO user_mastery_stats (user_id, mastery_type, level, current_xp) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE level = VALUES(level), current_xp = VALUES(current_xp)
        ")->execute([$userId, $masteryType, $newLevel, $newXp]);

        return $leveledUp;
    }

    public static function getUserStats(int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM user_fxp_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $global = $stmt->fetch() ?: ['lifetime_fxp' => 0, 'current_level' => 1, 'prestige_stars' => 0, 'xp_multiplier' => 1.00, 'current_title' => 'Beginner Saver'];

        if (empty($global['current_title']) || $global['current_title'] === 'Beginner Saver') {
            $global['current_title'] = self::getTitleForLevel((int) $global['current_level'], (int) $global['prestige_stars']);
            $db->prepare("UPDATE user_fxp_stats SET current_title = ? WHERE user_id = ?")
                ->execute([$global['current_title'], $userId]);
        }
        $stmt = $db->prepare("SELECT mastery_type, level, current_xp FROM user_mastery_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $masteries = $stmt->fetchAll();
        $masteryMap = [];
        foreach ($masteries as $m) {
            $xpRequired = 500 * $m['level'];
            $masteryMap[$m['mastery_type']] = [
                'level' => $m['level'],
                'xp' => $m['current_xp'],
                'required' => $xpRequired,
                'percent' => min(100, ($m['current_xp'] / $xpRequired) * 100)
            ];
        }
        $currentLevel = (int) $global['current_level'];
        $xpForCurrent = (int) floor(100 * pow($currentLevel - 1, 1.5));
        $xpForNext = (int) floor(100 * pow($currentLevel, 1.5));

        $xpProgress = (int) $global['lifetime_fxp'] - $xpForCurrent;
        $xpNeeded = $xpForNext - $xpForCurrent;
        $globalProgressPercent = $xpNeeded > 0 ? min(100, max(0, ($xpProgress / $xpNeeded) * 100)) : 100;

        return [
            'global' => array_merge($global, [
                'xp_progress' => $xpProgress,
                'xp_needed' => $xpNeeded,
                'progress_percent' => $globalProgressPercent
            ]),
            'masteries' => $masteryMap
        ];
    }

    public static function getTitleForLevel(int $level, int $prestigeStars): string
    {
        if ($prestigeStars >= 3)
            return 'Financial Legend';
        if ($prestigeStars >= 1)
            return 'Wealth Master';
        if ($level >= 50)
            return 'Financial Architect';
        if ($level >= 40)
            return 'Cash Flow Expert';
        if ($level >= 30)
            return 'Investment Explorer';
        if ($level >= 20)
            return 'Wealth Builder';
        if ($level >= 15)
            return 'Money Manager';
        if ($level >= 10)
            return 'Budget Planner';
        if ($level >= 5)
            return 'Careful Spender';
        return 'Beginner Saver';
    }

    public static function prestige(int $userId, int $requiredLevel = 50): bool
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT current_level, lifetime_fxp, prestige_stars, xp_multiplier FROM user_fxp_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();

        if (!$stats || (int) $stats['current_level'] < $requiredLevel) {
            return false;
        }

        try {
            $db->beginTransaction();

            $newPrestigeStars = (int) $stats['prestige_stars'] + 1;
            $newMultiplier = (float) $stats['xp_multiplier'] + 0.10;
            $newTitle = self::getTitleForLevel(1, $newPrestigeStars);
            $db->prepare("INSERT INTO user_prestige_history (user_id, prestige_number, level_at_prestige, fxp_at_prestige) VALUES (?, ?, ?, ?)")
                ->execute([$userId, $newPrestigeStars, $stats['current_level'], $stats['lifetime_fxp']]);
            $db->prepare("
                UPDATE user_fxp_stats 
                SET current_level = 1, 
                    prestige_stars = ?, 
                    xp_multiplier = ?, 
                    current_title = ? 
                WHERE user_id = ?
            ")->execute([$newPrestigeStars, $newMultiplier, $newTitle, $userId]);

            $db->prepare("UPDATE user_mastery_stats SET level = 1, current_xp = 0 WHERE user_id = ?")->execute([$userId]);

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error("Prestige failed", ['error' => $e->getMessage()]);
            return false;
        }
    }
}