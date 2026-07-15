<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Account;
use App\Models\Currency;
use App\Models\CurrencyService;
use App\Models\PendingLedger;
use App\Models\Bill;
use App\Models\Salary;
use App\Core\Cache;
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
        $accounts = Account::getAllByUser(Auth::id());
        $baseCurrency = CurrencyService::getUserBaseCurrency(Auth::id());
        $netIncome = $this->getNetIncome(Auth::id());
        $pendingItems = PendingLedger::getUpcoming(Auth::id(), 5);
        $upcomingBills = Bill::getUpcoming(Auth::id(), 3);
        $latestSalary = Salary::getRecent(Auth::id(), 1);
        $topVaults = \App\Models\Vault::getByStatus(Auth::id(), 'active');
        $topVaults = array_slice($topVaults, 0, 3);
        foreach ($topVaults as &$v) {
            $v['metrics'] = \App\Models\Vault::calculateMetrics($v, Auth::id());
        }

        $this->view('dashboard.index', [
            'accounts' => $accounts,
            'baseCurrency' => $baseCurrency,
            'netIncome' => $netIncome,
            'pendingItems' => $pendingItems,
            'upcomingBills' => $upcomingBills,
            'latestSalary' => $latestSalary[0] ?? null,
            'topVaults' => $topVaults
        ]);
    }

        public function getStats(): void
    {
        header('Content-Type: application/json');
        $userId = Auth::id();
        $data = Cache::remember("dashboard_stats_{$userId}", 300, function() use ($userId) {
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
                    'income' => (float)($monthlyFlow['total_income'] ?? 0),
                    'expense' => (float)($monthlyFlow['total_expense'] ?? 0)
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
            'total_income' => (float)($result['total_income'] ?? 0),
            'total_expense' => (float)($result['total_expense'] ?? 0)
        ];
    }
}