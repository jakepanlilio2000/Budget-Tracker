<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\DailyLog;
use App\Services\AchievementEngine;
use App\Services\FxpEngine;
use App\Services\StreakEngine;
use App\Services\LifetimeStatsService;
use App\Services\FinancialSummaryEngine;

class DailyLogController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $logs = DailyLog::getRecent(Auth::id(), 30);
        $this->view('daily_logs.index', ['logs' => $logs]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $date = $_POST['log_date'] ?? date('Y-m-d');
        $totalSpent = (float) ($_POST['total_spent'] ?? 0);
        $mood = trim($_POST['mood_context'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        DailyLog::upsert(Auth::id(), $date, $totalSpent, $mood, $notes);
        Session::set('success', 'Daily log saved.');
        $achResult = AchievementEngine::syncUser($userId);
        if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
            Session::set('achievement_notification', $achResult);
        }
        FxpEngine::award($userId, 'create_daily_log', 1);
        StreakEngine::checkStreak($userId, 'daily_log');
        LifetimeStatsService::clearCache($userId);
        FinancialSummaryEngine::invalidateCache($userId);
        $this->redirect('/daily-logs');
    }
}