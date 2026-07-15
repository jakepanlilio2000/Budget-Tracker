<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use App\Models\Currency;
use App\Core\Cache;
use App\Models\CurrencyService;
use App\Core\Logger;

class TransactionController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $transactions = Transaction::getRecent(Auth::id(), 50);
        $this->view('transactions.index', ['transactions' => $transactions]);
    }

    public function create(): void
    {
        $accounts = Account::getAllByUser(Auth::id());
        $categories = Category::getAllByUser(Auth::id(), 'expense');
        $baseCurrency = CurrencyService::getUserBaseCurrency(Auth::id());
        
        $this->view('transactions.create', [
            'accounts' => $accounts,
            'categories' => $categories,
            'baseCurrency' => $baseCurrency
        ]);
    }

        public function store(): void
    {
        Logger::info("Transaction store attempt", ['post_data' => $_POST]);

        $this->validateCsrf();
        $userId = Auth::id();
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);
        
        $txnData = [
            'account_id' => (int)($_POST['account_id'] ?? 0),
            'type' => $_POST['type'] ?? 'expense',
            'total_amount' => (float)($_POST['total_amount'] ?? 0),
            'currency_id' => (int)($_POST['currency_id'] ?? $baseCurrency['id']),
            'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
            'status' => $_POST['status'] ?? 'posted',
            'description' => trim($_POST['description'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if ($txnData['account_id'] <= 0) {
            Session::set('error', 'Please select a valid account.');
            Session::set('old_input', $_POST);
            $this->redirect('/transactions/create');
        }
        if ($txnData['total_amount'] <= 0) {
            Session::set('error', 'Total amount must be greater than zero.');
            Session::set('old_input', $_POST);
            $this->redirect('/transactions/create');
        }
        if (empty($txnData['description'])) {
            Session::set('error', 'Description is required.');
            Session::set('old_input', $_POST);
            $this->redirect('/transactions/create');
        }
        $splits = [];
        $splitTotal = 0;
        if (isset($_POST['split_category']) && is_array($_POST['split_category'])) {
            foreach ($_POST['split_category'] as $i => $catId) {
                $amount = (float)($_POST['split_amount'][$i] ?? 0);
                if ($amount > 0 && !empty($catId)) {
                    $splits[] = [
                        'category_id' => (int)$catId,
                        'amount' => $amount,
                        'notes' => trim($_POST['split_notes'][$i] ?? '')
                    ];
                    $splitTotal += $amount;
                }
            }
        }

        if (empty($splits)) {
            Session::set('error', 'You must add at least one category split.');
            Session::set('old_input', $_POST);
            $this->redirect('/transactions/create');
        }
        if (round($splitTotal, 2) !== round($txnData['total_amount'], 2)) {
            Session::set('error', "Split amounts (" . round($splitTotal, 2) . ") must exactly equal the total amount (" . round($txnData['total_amount'], 2) . ").");
            Session::set('old_input', $_POST);
            $this->redirect('/transactions/create');
        }

        if (Transaction::createWithSplits($userId, $txnData, $splits)) {
            Cache::forget("dashboard_stats_{$userId}");
            Session::set('success', 'Transaction recorded successfully.');
            $this->redirect('/transactions');
        } else {
            Session::set('error', 'Failed to save transaction. Please try again.');
            Session::set('old_input', $_POST);
            $this->redirect('/transactions/create');
        }
    }
}