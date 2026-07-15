<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Budget;
use App\Models\Category;

class BudgetController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $month = $_GET['month'] ?? date('Y-m');
        $budgets = Budget::getMonthlyByUser(Auth::id(), $month);
        $categories = Category::getAllByUser(Auth::id(), 'expense');

        $this->view('budgets.index', [
            'budgets' => $budgets,
            'categories' => $categories,
            'currentMonth' => $month
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $month = $_POST['month'] ?? date('Y-m');
        $amount = (float) ($_POST['amount'] ?? 0);

        if ($categoryId > 0 && $amount > 0) {
            Budget::upsert(Auth::id(), $categoryId, $month, $amount);
            Session::set('success', 'Budget saved successfully.');
        } else {
            Session::set('error', 'Invalid category or amount.');
        }

        $this->redirect('/budgets?month=' . $month);
    }
}