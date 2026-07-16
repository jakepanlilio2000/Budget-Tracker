<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Services\AchievementEngine;
use App\Services\FxpEngine;
use App\Core\Session;
use App\Services\LifetimeStatsService;
class AchievementController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();

        AchievementEngine::syncUser($userId);
        $fxpStats = FxpEngine::getUserStats($userId);

        $db = Database::getInstance()->getConnection();
        $filter = $_GET['cat'] ?? 'All';
        $search = $_GET['q'] ?? '';

        $sql = "
            SELECT ad.*, ua.progress, ua.target, ua.unlocked_at, COALESCE(ua.chain_level, 1) as chain_level
            FROM achievement_definitions ad
            LEFT JOIN user_achievements ua ON ad.id = ua.achievement_id AND ua.user_id = ?
            WHERE ad.is_active = 1
        ";
        $params = [$userId];

        if ($filter !== 'All') {
            $sql .= " AND ad.category = ?";
            $params[] = $filter;
        }
        if ($search) {
            $sql .= " AND (ad.name LIKE ? OR ad.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY ad.category, ad.rarity DESC, ad.xp_value DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $achievements = $stmt->fetchAll();
        $grouped = [];
        $totalUnlocked = 0;
        foreach ($achievements as &$a) {
            if ($a['is_chain'] && $a['chain_level'] > 1) {
                $formattedTarget = number_format((float) $a['target'], 0);
                $a['name'] = preg_replace('/\d+(?:,\d+)*/', $formattedTarget, $a['name'], 1);
                $a['description'] = preg_replace('/\d+(?:,\d+)*/', $formattedTarget, $a['description'], 1);
            }

            $grouped[$a['category']][] = $a;
            if ($a['unlocked_at']) {
                $totalUnlocked++;
            }
        }
        unset($a);
        $lifetimeStats = LifetimeStatsService::getStats($userId);


        $this->view('achievements.index', [
            'stats' => [
                'current_level' => (int) $fxpStats['global']['current_level'],
                'lifetime_fxp' => (int) $fxpStats['global']['lifetime_fxp'],
                'prestige_stars' => (int) $fxpStats['global']['prestige_stars'],
                'xp_multiplier' => (float) $fxpStats['global']['xp_multiplier'],
                'current_title' => (string) $fxpStats['global']['current_title'],
                'can_prestige' => (int) $fxpStats['global']['current_level'] >= 50,
            ],
            'xpPercent' => (float) $fxpStats['global']['progress_percent'],
            'xpProgress' => (int) $fxpStats['global']['xp_progress'],
            'xpNeeded' => (int) $fxpStats['global']['xp_needed'],
            'grouped' => $grouped,
            'totalUnlocked' => $totalUnlocked,
            'totalAchievements' => count($achievements),
            'filter' => $filter,
            'search' => $search,
            'masteries' => $fxpStats['masteries'],
            'lifetimeStats' => $lifetimeStats
        ]);
    }

    public function prestige(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        if (FxpEngine::prestige($userId, 50)) {
            Session::set('success', '🌟 Prestige Successful! You are now Level 1 with a permanent XP boost and a new Title!');
        } else {
            Session::set('error', 'You must reach Level 50 to Prestige.');
        }

        $this->redirect('/achievements');
    }
}