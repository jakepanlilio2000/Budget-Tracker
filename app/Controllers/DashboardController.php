<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Account;
use App\Models\CurrencyService;
use App\Models\PendingLedger;
use App\Models\Bill;
use App\Models\Salary;
use App\Core\Cache;
use App\Models\Vault;
use App\Services\CashFlowService;
use App\Services\CalendarService;
use App\Services\AchievementEngine;
use App\Models\Preference;
use App\Services\FxpEngine;
use App\Services\StreakEngine;

class DashboardController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }

    public function index(): void
    {
        $userId = Auth::id();
        $accounts = Account::getAllByUser($userId);
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);
        $netIncome = $this->getNetIncome($userId);
        $pendingItems = PendingLedger::getUpcoming($userId, 5);
        $upcomingBills = method_exists(Bill::class, 'getUpcoming') ? Bill::getUpcoming($userId, 3) : [];
        $latestSalary = Salary::getRecent($userId, 1);
        AchievementEngine::syncUser($userId);
        $stmt = Database::getInstance()->getConnection()->prepare("
            SELECT ad.name, ad.icon, ad.color, ua.unlocked_at 
            FROM user_achievements ua 
            JOIN achievement_definitions ad ON ua.achievement_id = ad.id 
            WHERE ua.user_id = ? AND ua.unlocked_at IS NOT NULL 
            ORDER BY ua.unlocked_at DESC LIMIT 3
        ");
        $stmt->execute([$userId]);
        $recentAchievements = $stmt->fetchAll();

        $fxpStats = FxpEngine::getUserStats($userId);

        $rpgStats = [
            'level' => $fxpStats['global']['current_level'],
            'total_xp' => $fxpStats['global']['lifetime_fxp'],
            'wealth_tier' => $fxpStats['global']['current_title']
        ];
        $topVaults = Vault::getByStatus($userId, 'active');
        $topVaults = array_slice($topVaults, 0, 3);
        foreach ($topVaults as &$v) {
            $v['metrics'] = Vault::calculateMetrics($v, $userId);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM timeline_events WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$userId]);
        $recentTimeline = $stmt->fetchAll();
        $cashFlowForecast = CashFlowService::generateForecast($userId, 7);
        $upcomingEvents = CalendarService::getEvents($userId, date('Y-m-d'), date('Y-m-d', strtotime('+14 days')));
        usort($upcomingEvents, fn($a, $b) => strtotime($a['start']) <=> strtotime($b['start']));
        $upcomingEvents = array_slice($upcomingEvents, 0, 5);
        $prefs = Preference::get($userId);
        $dashboardConfig = $prefs['dashboard_config'] ?? null;

        StreakEngine::checkStreak($userId, 'daily_login');
        $fxpStats = FxpEngine::getUserStats(Auth::id());
        $this->view('dashboard.index', [
            'user' => Auth::user(),
            'accounts' => $accounts,
            'baseCurrency' => $baseCurrency,
            'netIncome' => $netIncome,
            'pendingItems' => $pendingItems,
            'upcomingBills' => $upcomingBills,
            'latestSalary' => $latestSalary[0] ?? null,
            'topVaults' => $topVaults,
            'recentTimeline' => $recentTimeline,
            'cashFlowForecast' => $cashFlowForecast,
            'upcomingEvents' => $upcomingEvents,
            'recentAchievements' => $recentAchievements,
            'rpgStats' => $rpgStats,
            'fxpStats' => $fxpStats,
            'dashboardConfig' => $dashboardConfig,
            'prefs' => $prefs,
        ]);
    }

    public function getStats(): void
    {
        header('Content-Type: application/json');
        $userId = Auth::id();

        $data = Cache::remember("dashboard_stats_{$userId}", 300, function () use ($userId) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT 
                    SUM(CASE WHEN type = 'income' AND status = 'posted' THEN total_amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' AND status = 'posted' THEN total_amount ELSE 0 END) as total_expense
                FROM transactions 
                WHERE user_id = ? AND deleted_at IS NULL 
                AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$userId]);
            $monthlyFlow = $stmt->fetch();
            $stmt = $db->prepare("
                SELECT c.name, c.color, SUM(ts.amount) as total 
                FROM transaction_splits ts
                JOIN categories c ON ts.category_id = c.id
                JOIN transactions t ON ts.transaction_id = t.id
                WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
                AND t.transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                GROUP BY c.id, c.name, c.color
                ORDER BY total DESC LIMIT 5
            ");
            $stmt->execute([$userId]);
            $categoryData = $stmt->fetchAll();

            $stmt = $db->prepare("
                SELECT DATE_FORMAT(transaction_date, '%b %Y') as month,
                    SUM(CASE WHEN type = 'income' AND status = 'posted' THEN total_amount ELSE 0 END) as income,
                    SUM(CASE WHEN type = 'expense' AND status = 'posted' THEN total_amount ELSE 0 END) as expense
                FROM transactions 
                WHERE user_id = ? AND deleted_at IS NULL
                AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY MIN(transaction_date) ASC
            ");
            $stmt->execute([$userId]);
            $trendData = $stmt->fetchAll();

            return [
                'monthly_flow' => [
                    'income' => (float) ($monthlyFlow['total_income'] ?? 0),
                    'expense' => (float) ($monthlyFlow['total_expense'] ?? 0)
                ],
                'categories' => $categoryData,
                'trend' => $trendData
            ];
        });

        $this->json(['success' => true, 'data' => $data]);
    }

    public function getNetIncome(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'income' AND status = 'posted' THEN total_amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' AND status = 'posted' THEN total_amount ELSE 0 END), 0) as total_expense
            FROM transactions 
            WHERE user_id = ? AND deleted_at IS NULL
            AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        return [
            'total_income' => (float) ($result['total_income'] ?? 0),
            'total_expense' => (float) ($result['total_expense'] ?? 0)
        ];
    }

    public function saveLayout(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        if (isset($_POST['reset']) && $_POST['reset'] === '1') {
            Preference::save($userId, ['dashboard_config' => null]);
            $this->json(['success' => true, 'reset' => true]);
            return;
        }

        $layoutJson = $_POST['layout'] ?? null;
        if ($layoutJson) {
            $layout = json_decode($layoutJson, true);
            if ($layout) {
                Preference::save($userId, ['dashboard_config' => $layout]);
                $this->json(['success' => true]);
                return;
            }
        }

        $this->json(['success' => false, 'error' => 'Invalid layout data'], 400);
    }
}