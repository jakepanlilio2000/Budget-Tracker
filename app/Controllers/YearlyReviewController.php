<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\YearlyReview;
use App\Models\CurrencyService;
use App\Core\Database;

class YearlyReviewController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();
        $year = $_GET['year'] ?? date('Y');

        if (!preg_match('/^\d{4}$/', $year)) {
            $year = date('Y');
        }

        $reviewData = YearlyReview::generate($userId, $year);
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $this->view('reviews.yearly', [
            'review' => $reviewData,
            'currentYear' => $year,
            'baseCurrency' => $baseCurrency
        ]);
    }

    public function exportCsv(): void
    {
        $year = $_GET['year'] ?? date('Y');
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT t.transaction_date, t.type, t.description, t.total_amount, c.name as category, a.name as account
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN accounts a ON t.account_id = a.id
            WHERE t.user_id = ? AND t.status = 'posted' AND t.deleted_at IS NULL
            AND YEAR(t.transaction_date) = ?
            ORDER BY t.transaction_date DESC
        ");
        $stmt->execute([$userId, $year]);
        $data = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="yearly_report_' . $year . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Date', 'Type', 'Description', 'Amount', 'Category', 'Account'], ',', '"', '');

        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '');
        }
        fclose($output);
        exit;
    }
}