<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\RecurringIncome;
use App\Models\Account;
use App\Models\Category;
use App\Models\CurrencyService;
use App\Services\RecurringIncomeService;

class RecurringIncomeController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();

        RecurringIncomeService::autoPostDueIncomes($userId);

        $incomes = RecurringIncome::getAllByUser($userId);
        $currencies = CurrencyService::getAllCurrencies();
        $categories = Category::getAllActiveByUser($userId, 'income');
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $this->view('recurring_incomes.index', [
            'incomes' => $incomes,
            'currencies' => $currencies,
            'categories' => $categories,
            'baseCurrency' => $baseCurrency
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'amount' => (float) ($_POST['amount'] ?? 0),
            'currency_id' => (int) ($_POST['currency_id'] ?? CurrencyService::getUserBaseCurrency($userId)['id']),
            'account_id' => (int) ($_POST['account_id'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
            'frequency' => $_POST['frequency'] ?? 'monthly',
            'custom_interval_days' => !empty($_POST['custom_interval_days']) ? (int) $_POST['custom_interval_days'] : null,
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'next_post_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['name']) || $data['amount'] <= 0 || $data['account_id'] <= 0) {
            Session::set('error', 'Name, valid amount, and account are required.');
            $this->redirect('/recurring-incomes/create');
        }

        RecurringIncome::create($userId, $data);
        \App\Services\FxpEngine::award($userId, 'create_recurring_income', 1);
        Session::set('success', 'Recurring income source created successfully.');
        $this->redirect('/recurring-incomes');
    }

    public function toggleStatus(int $id): void
    {
        $this->validateCsrf();
        $income = RecurringIncome::findById($id, Auth::id());
        if (!$income)
            $this->redirect('/recurring-incomes');

        $newStatus = $income['status'] === 'active' ? 'paused' : 'active';
        RecurringIncome::toggleStatus($id, Auth::id(), $newStatus);
        Session::set('success', "Recurring income " . ($newStatus === 'active' ? 'resumed' : 'paused') . ".");
        $this->redirect('/recurring-incomes');
    }

    public function skip(int $id): void
    {
        $this->validateCsrf();
        RecurringIncomeService::skipNextOccurrence($id, Auth::id());
        Session::set('success', 'Next occurrence skipped successfully.');
        $this->redirect('/recurring-incomes');
    }

    public function postNow(int $id): void
    {
        $this->validateCsrf();
        if (RecurringIncomeService::postOccurrence($id, Auth::id())) {
            Session::set('success', 'Income posted successfully!');
        } else {
            Session::set('error', 'Failed to post income. Check if it is active or has reached its end date.');
        }
        $this->redirect('/recurring-incomes');
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        RecurringIncome::delete($id, Auth::id());
        Session::set('success', 'Recurring income source deleted.');
        $this->redirect('/recurring-incomes');
    }
}