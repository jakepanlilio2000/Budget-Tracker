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
            SELECT c.name as category_name, c.color, SUM(ts.amount) as total_amount, COUNT(t.id) as transaction_count
            FROM transaction_splits ts
            JOIN categories c ON ts.category_id = c.id
            JOIN transactions t ON ts.transaction_id = t.id
            WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
            AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            GROUP BY c.id, c.name, c.color
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$userId, $month]);
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
            SELECT t.transaction_date, t.description, c.name as category, ts.amount, t.status
            FROM transactions t
            JOIN transaction_splits ts ON t.id = ts.transaction_id
            JOIN categories c ON ts.category_id = c.id
            WHERE t.user_id = ? AND t.type = 'expense' AND t.status = 'posted' AND t.deleted_at IS NULL
            AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            ORDER BY t.transaction_date DESC
        ");
        $stmt->execute([$userId, $month]);
        $data = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="expense_report_' . $month . '.csv"');
        header('Pragma: no-cache');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['Date', 'Description', 'Category', 'Amount', 'Status'], ',', '"', '');
        
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '');
        }
        
        fclose($output);
        exit;
    }
}