<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Category;
use App\Services\BudgetService;
use App\Services\RequestValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthorizationException;

class BudgetController extends Controller
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
        $month = $_GET['month'] ?? date('Y-m');
        try {
            $month = RequestValidator::validateMonthPeriod($month);
        } catch (\Throwable $e) {
            $month = date('Y-m');
        }

        $overview = BudgetService::getMonthlyBudgetOverview($userId, $month);
        $categories = Category::getAllByUser($userId, 'expense');

        $this->view('budgets.index', [
            'budgets' => $overview['budgets'],
            'categories' => $categories,
            'currentMonth' => $month,
            'overview' => $overview
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $month = $_POST['month'] ?? date('Y-m');
        $amount = (string) ($_POST['amount'] ?? '0.00');
        $currencyId = !empty($_POST['currency_id']) ? (int) $_POST['currency_id'] : null;
        $carryOver = !empty($_POST['carry_over']);

        try {
            BudgetService::upsertBudget($userId, $categoryId, $month, $amount, $currencyId, 'monthly', $carryOver);
            Session::set('success', 'Budget saved successfully.');
        } catch (ValidationException | AuthorizationException $e) {
            Session::set('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::set('error', 'Failed to save budget: ' . $e->getMessage());
        }

        $this->redirect('/budgets?month=' . urlencode($month));
    }

    public function rollover(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $fromMonth = $_POST['from_month'] ?? date('Y-m');

        try {
            $applied = BudgetService::applyCarryOver($userId, $fromMonth);
            Session::set('success', count($applied) . ' category budget surplus(es) rolled over successfully.');
        } catch (\Throwable $e) {
            Session::set('error', 'Failed to process rollover: ' . $e->getMessage());
        }

        $this->redirect('/budgets?month=' . urlencode($fromMonth));
    }
}