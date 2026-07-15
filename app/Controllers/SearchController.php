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
            LIMIT 5
        ");
        $stmt->execute([$userId, $query, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Transactions',
                'icon' => 'fas fa-receipt',
                'title' => $row['description'] ?: 'No Description',
                'subtitle' => "{$row['account_name']} • {$row['symbol']}{$row['total_amount']}",
                'url' => url('/transactions')
            ];
        }
        $stmt = $db->prepare("
            SELECT name, type FROM accounts 
            WHERE user_id = ? AND name LIKE ? AND deleted_at IS NULL 
            LIMIT 5
        ");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Accounts',
                'icon' => 'fas fa-university',
                'title' => $row['name'],
                'subtitle' => ucfirst($row['type']),
                'url' => url('/accounts')
            ];
        }
        $stmt = $db->prepare("SELECT name, total_amount, next_due_date FROM bills WHERE user_id = ? AND name LIKE ? AND status = 'active' LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Bills',
                'icon' => 'fas fa-file-invoice',
                'title' => $row['name'],
                'subtitle' => "Due " . date('M d', strtotime($row['next_due_date'])) . " • " . number_format($row['total_amount'], 2),
                'url' => url('/bills')
            ];
        }
        $stmt = $db->prepare("SELECT s.net_pay, s.payment_date, e.company_name FROM salaries s JOIN employers e ON s.employer_id = e.id WHERE s.user_id = ? AND e.company_name LIKE ? LIMIT 3");
        $stmt->execute([$userId, $query]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'category' => 'Salaries',
                'icon' => 'fas fa-money-bill-wave',
                'title' => $row['company_name'],
                'subtitle' => "Paid " . date('M d, Y', strtotime($row['payment_date'])) . " • " . number_format($row['net_pay'], 2),
                'url' => url('/salaries')
            ];
        }

        $this->json(['success' => true, 'results' => $results]);
    }
}