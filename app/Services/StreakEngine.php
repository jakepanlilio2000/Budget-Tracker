<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

class StreakEngine
{
    public static function checkStreak(int $userId, string $streakType): array
    {
        $db = Database::getInstance()->getConnection();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $stmt = $db->prepare("SELECT current_streak, best_streak, last_action_date FROM user_streaks WHERE user_id = ? AND streak_type = ?");
        $stmt->execute([$userId, $streakType]);
        $streak = $stmt->fetch();

        $result = ['streak_increased' => false, 'current_streak' => 1, 'best_streak' => 1];

        if (!$streak) {
            // First time
            $db->prepare("INSERT INTO user_streaks (user_id, streak_type, current_streak, best_streak, last_action_date) VALUES (?, ?, 1, 1, ?)")
                ->execute([$userId, $streakType, $today]);
            $result['current_streak'] = 1;
            $result['best_streak'] = 1;
        } else {
            $lastDate = $streak['last_action_date'];
            $current = (int) $streak['current_streak'];
            $best = (int) $streak['best_streak'];

            if ($lastDate === $today) {
                $result['current_streak'] = $current;
                $result['best_streak'] = $best;
            } elseif ($lastDate === $yesterday) {
                $current++;
                $best = max($best, $current);
                $db->prepare("UPDATE user_streaks SET current_streak = ?, best_streak = ?, last_action_date = ? WHERE user_id = ? AND streak_type = ?")
                    ->execute([$current, $best, $today, $userId, $streakType]);
                $result['streak_increased'] = true;
                $result['current_streak'] = $current;
                $result['best_streak'] = $best;
            } else {
                $db->prepare("UPDATE user_streaks SET current_streak = 1, best_streak = ?, last_action_date = ? WHERE user_id = ? AND streak_type = ?")
                    ->execute([max($best, 1), $today, $userId, $streakType]);
                $result['current_streak'] = 1;
                $result['best_streak'] = max($best, 1);
            }
        }

        return $result;
    }

    public static function getUserStreaks(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT streak_type, current_streak, best_streak FROM user_streaks WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}