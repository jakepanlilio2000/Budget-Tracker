<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;

class SearchController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->json(['success' => false], 401);
    }

    public function globalSearch(): void
    {
        $query = '%' . ($_GET['q'] ?? '') . '%';
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();
        $results = [];

        $stmt = $db->prepare("
            SELECT t.description, t.total_amount, c.symbol, a.name as account_name 
            FROM transactions t 
            JOIN accounts a ON t.account_id = a.id 
            JOIN currencies c ON t.currency_id = c.id 
            WHERE t.user_id = ? AND (t.description LIKE ? OR a.name LIKE ?) AND t.deleted_at IS NULL 
            LIMIT 3
        ");
        $stmt->execute([$userId, $query, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Transactions',
                'icon' => 'fas fa-receipt',
                'title' => $row['description'] ?: 'No Description',
                'subtitle' => "{$row['account_name']} • {$row['symbol']}" . number_format((float) $row['total_amount'], 2),
                'url' => url('/transactions')
            ];
        }

        $stmt = $db->prepare("SELECT name, type FROM accounts WHERE user_id = ? AND name LIKE ? AND deleted_at IS NULL LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Accounts',
                'icon' => 'fas fa-building-columns',
                'title' => $row['name'],
                'subtitle' => ucfirst(str_replace('_', ' ', $row['type'])),
                'url' => url('/accounts')
            ];
        }

        $stmt = $db->prepare("SELECT name, current_amount, target_amount FROM savings_vaults WHERE user_id = ? AND name LIKE ? AND status = 'active' LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Savings Vaults',
                'icon' => 'fas fa-vault',
                'title' => $row['name'],
                'subtitle' => number_format((float) $row['current_amount'], 2) . ' / ' . number_format((float) $row['target_amount'], 2),
                'url' => url('/vaults')
            ];
        }

        $stmt = $db->prepare("SELECT name, total_amount, next_due_date FROM bills WHERE user_id = ? AND name LIKE ? AND status = 'active' LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Bills',
                'icon' => 'fas fa-file-invoice',
                'title' => $row['name'],
                'subtitle' => "Due " . date('M d', strtotime($row['next_due_date'])) . " • " . number_format((float) $row['total_amount'], 2),
                'url' => url('/bills')
            ];
        }

        $stmt = $db->prepare("SELECT e.company_name, s.net_pay, s.payment_date FROM salaries s JOIN employers e ON s.employer_id = e.id WHERE s.user_id = ? AND e.company_name LIKE ? LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Salaries',
                'icon' => 'fas fa-briefcase',
                'title' => $row['company_name'],
                'subtitle' => "Paid " . date('M d, Y', strtotime($row['payment_date'])) . " • " . number_format((float) $row['net_pay'], 2),
                'url' => url('/salaries')
            ];
        }

        $stmt = $db->prepare("SELECT description, amount, log_date FROM daily_logs WHERE user_id = ? AND description LIKE ? ORDER BY log_date DESC LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Daily Logs',
                'icon' => 'fas fa-book',
                'title' => $row['description'] ?: 'Daily Log',
                'subtitle' => date('M d, Y', strtotime($row['log_date'])) . ' • ' . number_format((float) $row['amount'], 2),
                'url' => url('/daily-logs')
            ];
        }

        $stmt = $db->prepare("SELECT description, amount, due_date FROM pending_ledger WHERE user_id = ? AND description LIKE ? AND status = 'pending' LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Pending Ledger',
                'icon' => 'fas fa-clock',
                'title' => $row['description'],
                'subtitle' => "Due " . date('M d, Y', strtotime($row['due_date'])) . ' • ' . number_format((float) $row['amount'], 2),
                'url' => url('/pending-ledger')
            ];
        }

        $stmt = $db->prepare("SELECT description, module, created_at FROM timeline_events WHERE user_id = ? AND description LIKE ? ORDER BY created_at DESC LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Timeline',
                'icon' => 'fas fa-history',
                'title' => $row['description'],
                'subtitle' => ucfirst($row['module']) . ' • ' . date('M d, Y', strtotime($row['created_at'])),
                'url' => url('/timeline')
            ];
        }

        $this->json(['success' => true, 'results' => $results]);
    }
}