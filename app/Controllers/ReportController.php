<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;

class ReportController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $month = $_GET['month'] ?? date('Y-m');
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT c.name as category_name, c.color, SUM(sub.total_amount) as total_amount, COUNT(sub.id) as transaction_count
            FROM (
                -- 1. Standard Transactions (No splits)
                SELECT t.id, t.category_id, t.total_amount 
                FROM transactions t
                WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
                AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
                AND t.category_id IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM transaction_splits ts WHERE ts.transaction_id = t.id)
                
                UNION ALL
                
                -- 2. Split Transactions
                SELECT t.id, ts.category_id, ts.amount as total_amount
                FROM transactions t
                JOIN transaction_splits ts ON t.id = ts.transaction_id
                WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
                AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            ) as sub
            JOIN categories c ON sub.category_id = c.id
            WHERE c.user_id = ?
            GROUP BY c.id, c.name, c.color
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$userId, $month, $userId, $month, $userId]);
        $reportData = $stmt->fetchAll();

        $this->view('reports.index', [
            'reportData' => $reportData,
            'currentMonth' => $month
        ]);
    }

    public function exportCsv(): void
    {
        $month = $_GET['month'] ?? date('Y-m');
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT sub.transaction_date, sub.description, c.name as category, sub.amount, sub.status
            FROM (
                -- 1. Standard Transactions (No splits)
                SELECT t.transaction_date, t.description, t.category_id, t.total_amount as amount, t.status, t.id
                FROM transactions t
                WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
                AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
                AND t.category_id IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM transaction_splits ts WHERE ts.transaction_id = t.id)
                
                UNION ALL
                
                -- 2. Split Transactions
                SELECT t.transaction_date, CONCAT(t.description, ' (Split)') as description, ts.category_id, ts.amount, t.status, t.id
                FROM transactions t
                JOIN transaction_splits ts ON t.id = ts.transaction_id
                WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
                AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            ) as sub
            JOIN categories c ON sub.category_id = c.id
            WHERE c.user_id = ?
            ORDER BY sub.transaction_date DESC
        ");
        $stmt->execute([$userId, $month, $userId, $month, $userId]);
        $data = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="expense_report_' . $month . '.csv"');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
        fputcsv($output, ['Date', 'Description', 'Category', 'Amount', 'Status'], ',', '"', '');

        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '');
        }

        fclose($output);
        exit;
    }
}