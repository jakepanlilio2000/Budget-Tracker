<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Cache;
use App\Core\Logger;
use App\Models\TransactionModel;
use App\Models\Category;
use App\Models\AccountModel;
use App\Models\CurrencyService;
use App\Services\MathService;
use App\Services\TimelineService;
use App\Services\StreakEngine;
use App\Services\LifetimeStatsService;
use App\Services\FinancialSummaryEngine;
use App\Exceptions\ValidationException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\IdempotencyException;

class TransactionController extends Controller
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
        $transactions = TransactionModel::getRecent($userId, 50);

        $accounts = AccountModel::getAllByUser($userId);
        $categories = Category::getAllActiveByUser($userId);
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $this->view('transactions.index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'categories' => $categories,
            'baseCurrency' => $baseCurrency
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $clientMutationId = !empty($_POST['client_mutation_id']) ? trim($_POST['client_mutation_id']) : null;

        try {
            $txnData = [
                'account_id' => (int) ($_POST['account_id'] ?? 0),
                'type' => $_POST['type'] ?? 'expense',
                'total_amount' => (string) ($_POST['total_amount'] ?? '0.00'),
                'currency_id' => !empty($_POST['currency_id']) ? (int) $_POST['currency_id'] : null,
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
                'status' => $_POST['status'] ?? 'posted',
                'description' => trim($_POST['description'] ?? ''),
                'notes' => trim($_POST['notes'] ?? '')
            ];

            $splits = [];
            if (isset($_POST['split_category']) && is_array($_POST['split_category'])) {
                foreach ($_POST['split_category'] as $i => $catId) {
                    $amount = (string) ($_POST['split_amount'][$i] ?? '0.00');
                    if (!empty($catId) && MathService::gt($amount, '0.00')) {
                        $splits[] = [
                            'category_id' => (int) $catId,
                            'amount' => $amount,
                            'notes' => trim($_POST['split_notes'][$i] ?? '')
                        ];
                    }
                }
            }

            $result = TransactionModel::createWithSplits($userId, $txnData, $splits, $clientMutationId);

            // Timeline and caches
            TimelineService::logEvent(
                'transactions',
                $txnData['type'] === 'income' ? 'income_recorded' : 'expense_recorded',
                $txnData['description'] ?: ucfirst($txnData['type']) . ' transaction',
                (float) $result['settled_amount'],
                (int) ($txnData['currency_id'] ?? 1),
                $txnData['account_id'],
                $splits[0]['category_id'] ?? null,
                $result['transaction_id'],
                $txnData['type'] === 'income' ? 'fa-arrow-down' : 'fa-arrow-up',
                $txnData['type'] === 'income' ? '#10b981' : '#ef4444'
            );

            Cache::forget("dashboard_stats_{$userId}");
            LifetimeStatsService::clearCache($userId);
            FinancialSummaryEngine::invalidateCache($userId);
            StreakEngine::checkStreak($userId, 'daily_transaction');

            Session::set('success', 'Transaction saved successfully.');
            $this->redirect('/transactions');

        } catch (ValidationException | InsufficientFundsException | AuthorizationException $e) {
            Session::set('error', $e->getMessage());
            Session::set('old_input', $_POST);
            $this->redirect('/transactions');
        } catch (IdempotencyException $e) {
            Session::set('info', 'This transaction was already processed.');
            $this->redirect('/transactions');
        } catch (\Throwable $e) {
            Logger::error("Transaction store exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Session::set('error', 'An unexpected error occurred while saving the transaction.');
            Session::set('old_input', $_POST);
            $this->redirect('/transactions');
        }
    }

    public function reverse(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $reason = trim($_POST['reason'] ?? 'User initiated reversal');

        try {
            TransactionModel::reverseTransaction($userId, $id, $reason);
            Cache::forget("dashboard_stats_{$userId}");
            LifetimeStatsService::clearCache($userId);
            FinancialSummaryEngine::invalidateCache($userId);

            Session::set('success', "Transaction #{$id} reversed successfully.");
        } catch (\Throwable $e) {
            Session::set('error', $e->getMessage());
        }

        $this->redirect('/transactions');
    }

    /**
     * Granular Selection Export for selected transactions (CSV or Executive PDF).
     */
    public function exportSelected(): void
    {
        $userId = Auth::id();
        $format = strtolower(trim((string) ($_POST['format'] ?? $_GET['format'] ?? 'csv')));
        $rawIds = $_POST['selected_ids'] ?? $_GET['selected_ids'] ?? [];

        if (is_string($rawIds)) {
            $rawIds = explode(',', $rawIds);
        }

        $selectedIds = array_filter(array_map('intval', (array) $rawIds), fn($id) => $id > 0);

        if (empty($selectedIds)) {
            Session::set('error', 'Please select at least one transaction to export.');
            $this->redirect('/transactions');
            return;
        }

        if ($format === 'pdf') {
            $exportService = new \App\Services\ExportBackupService();
            $result = $exportService->generateExecutivePdfStatement($userId, null, $selectedIds);

            \App\Services\ExportService::cleanOutputBuffer();
            $contentType = str_ends_with($result['filename'], '.pdf') ? 'application/pdf' : 'text/html';
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Content-Length: ' . (string) $result['filesize']);
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($result['filepath']);
            @unlink($result['filepath']);
            exit;
        }

        // Default: Stream CSV with PHP 8.4+ escape support
        $exportService = new \App\Services\ExportService();
        $exportService->streamTransactionsCsv($userId, ['selected_ids' => $selectedIds]);
        exit;
    }
}