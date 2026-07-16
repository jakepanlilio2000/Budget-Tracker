<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\CurrencyService;

class TimelineController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $sql = "SELECT t.*, c.symbol as currency_symbol, a.name as account_name, cat.name as category_name 
                FROM timeline_events t
                LEFT JOIN currencies c ON t.currency_id = c.id
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories cat ON t.category_id = cat.id
                WHERE t.user_id = ?";
        $params = [$userId];

        if (!empty($_GET['module'])) {
            $sql .= " AND t.module = ?";
            $params[] = $_GET['module'];
        }
        if (!empty($_GET['action'])) {
            $sql .= " AND t.action = ?";
            $params[] = $_GET['action'];
        }
        if (!empty($_GET['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $_GET['date_to'];
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        $this->view('timeline.index', [
            'events' => $events,
            'baseCurrency' => $baseCurrency,
            'filters' => $_GET
        ]);
    }

    public function loadMore(): void
    {
        $userId = Auth::id();
        $offset = (int) ($_GET['offset'] ?? 50);
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT t.*, c.symbol as currency_symbol, a.name as account_name 
                FROM timeline_events t
                LEFT JOIN currencies c ON t.currency_id = c.id
                LEFT JOIN accounts a ON t.account_id = a.id
                WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 50 OFFSET ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $offset]);
        $events = $stmt->fetchAll();

        $this->json(['success' => true, 'events' => $events, 'hasMore' => count($events) === 50]);
    }
}