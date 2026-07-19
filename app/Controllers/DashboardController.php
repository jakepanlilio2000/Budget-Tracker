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

            $incStmt = $db->prepare("
                SELECT SUM(total) as total_income FROM (
                    SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'income' AND status = 'posted' AND deleted_at IS NULL AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COALESCE(SUM(net_pay), 0) as total FROM salaries WHERE user_id = ? AND status = 'paid' AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COALESCE(SUM(amount), 0) as total FROM pending_ledger WHERE user_id = ? AND type = 'income' AND status = 'paid' AND MONTH(due_date) = MONTH(CURRENT_DATE()) AND YEAR(due_date) = YEAR(CURRENT_DATE())
                ) as inc
            ");
            $incStmt->execute([$userId, $userId, $userId]);
            $totalIncome = (float) $incStmt->fetchColumn();

            $expStmt = $db->prepare("
                SELECT SUM(total) as total_expense FROM (
                    SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COALESCE(SUM(total_spent), 0) as total FROM daily_logs WHERE user_id = ? AND MONTH(log_date) = MONTH(CURRENT_DATE()) AND YEAR(log_date) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COALESCE(SUM(amount_paid), 0) as total FROM bill_payments WHERE user_id = ? AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                    UNION ALL
                    SELECT COALESCE(SUM(amount), 0) as total FROM pending_ledger WHERE user_id = ? AND type = 'expense' AND status = 'paid' AND MONTH(due_date) = MONTH(CURRENT_DATE()) AND YEAR(due_date) = YEAR(CURRENT_DATE())
                ) as exp
            ");
            $expStmt->execute([$userId, $userId, $userId, $userId]);
            $totalExpense = (float) $expStmt->fetchColumn();
            $stmt = $db->prepare("
                SELECT c.name, c.color, SUM(sub.total) as total 
                FROM (
                    SELECT category_id, SUM(total_amount) as total 
                    FROM transactions 
                    WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL
                    AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                    GROUP BY category_id
                    
                    UNION ALL
                    
                    SELECT category_id, SUM(total_amount) as total 
                    FROM bills 
                    WHERE user_id = ? 
                    GROUP BY category_id
                ) as sub
                JOIN categories c ON sub.category_id = c.id
                WHERE c.user_id = ?
                GROUP BY c.id, c.name, c.color
                ORDER BY total DESC LIMIT 5
            ");
            $stmt->execute([$userId, $userId, $userId]);
            $categoryData = $stmt->fetchAll();

            $stmt = $db->prepare("
                SELECT DATE_FORMAT(date_val, '%b %Y') as month,
                    SUM(income) as income,
                    SUM(expense) as expense
                FROM (
                    SELECT transaction_date as date_val, 
                        CASE WHEN type = 'income' THEN total_amount ELSE 0 END as income,
                        CASE WHEN type = 'expense' THEN total_amount ELSE 0 END as expense
                    FROM transactions 
                    WHERE user_id = ? AND deleted_at IS NULL AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                    
                    UNION ALL
                    
                    SELECT payment_date as date_val, net_pay as income, 0 as expense
                    FROM salaries
                    WHERE user_id = ? AND status = 'paid' AND payment_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                    
                    UNION ALL
                    
                    SELECT log_date as date_val, 0 as income, total_spent as expense
                    FROM daily_logs
                    WHERE user_id = ? AND log_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                    
                    UNION ALL
                    
                    SELECT payment_date as date_val, 0 as income, amount_paid as expense
                    FROM bill_payments
                    WHERE user_id = ? AND payment_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                ) as combined
                GROUP BY DATE_FORMAT(date_val, '%Y-%m'), DATE_FORMAT(date_val, '%b %Y')
                ORDER BY MIN(date_val) ASC
            ");
            $stmt->execute([$userId, $userId, $userId, $userId]);
            $trendData = $stmt->fetchAll();

            return [
                'monthly_flow' => [
                    'income' => $totalIncome,
                    'expense' => $totalExpense
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

        $incStmt = $db->prepare("
            SELECT SUM(total) as total_income FROM (
                SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'income' AND status = 'posted' AND deleted_at IS NULL AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COALESCE(SUM(net_pay), 0) as total FROM salaries WHERE user_id = ? AND status = 'paid' AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COALESCE(SUM(amount), 0) as total FROM pending_ledger WHERE user_id = ? AND type = 'income' AND status = 'paid' AND MONTH(due_date) = MONTH(CURRENT_DATE()) AND YEAR(due_date) = YEAR(CURRENT_DATE())
            ) as inc
        ");
        $incStmt->execute([$userId, $userId, $userId]);
        $totalIncome = (float) $incStmt->fetchColumn();

        $expStmt = $db->prepare("
            SELECT SUM(total) as total_expense FROM (
                SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND status = 'posted' AND deleted_at IS NULL AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COALESCE(SUM(total_spent), 0) as total FROM daily_logs WHERE user_id = ? AND MONTH(log_date) = MONTH(CURRENT_DATE()) AND YEAR(log_date) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COALESCE(SUM(amount_paid), 0) as total FROM bill_payments WHERE user_id = ? AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                UNION ALL
                SELECT COALESCE(SUM(amount), 0) as total FROM pending_ledger WHERE user_id = ? AND type = 'expense' AND status = 'paid' AND MONTH(due_date) = MONTH(CURRENT_DATE()) AND YEAR(due_date) = YEAR(CURRENT_DATE())
            ) as exp
        ");
        $expStmt->execute([$userId, $userId, $userId, $userId]);
        $totalExpense = (float) $expStmt->fetchColumn();

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense
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