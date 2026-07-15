<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Account;
use App\Models\Currency;

class AccountController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }

    public function index(): void
    {
        $accounts = Account::getAllByUser(Auth::id());
        $this->view('accounts.index', ['accounts' => $accounts]);
    }

    public function create(): void
    {
        $currencies = Currency::getAll();
        $this->view('accounts.create', ['currencies' => $currencies]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? 'bank',
            'currency_id' => (int) ($_POST['currency_id'] ?? 1),
            'institution' => trim($_POST['institution'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'opening_balance' => (float) ($_POST['opening_balance'] ?? 0),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['name'])) {
            Session::set('error', 'Account name is required.');
            $this->redirect('/accounts/create');
        }

        Account::create(Auth::id(), $data);
        Session::set('success', 'Account created successfully.');
        $this->redirect('/accounts');
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        Account::softDelete($id, Auth::id());
        Session::set('success', 'Account archived successfully.');
        $this->redirect('/accounts');
    }
    public function edit(int $id): void
    {
        $account = Account::findById($id, Auth::id());
        if (!$account) {
            Session::set('error', 'Account not found.');
            $this->redirect('/accounts');
        }
        $currencies = Currency::getAll();
        $this->view('accounts.edit', ['account' => $account, 'currencies' => $currencies]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $account = Account::findById($id, Auth::id());
        if (!$account) {
            Session::set('error', 'Account not found.');
            $this->redirect('/accounts');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? 'bank',
            'currency_id' => (int)($_POST['currency_id'] ?? 1),
            'institution' => trim($_POST['institution'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['name'])) {
            Session::set('error', 'Account name is required.');
            $this->redirect('/accounts/edit/' . $id);
        }

        Account::update($id, Auth::id(), $data);
        Session::set('success', 'Account updated successfully.');
        $this->redirect('/accounts');
    }
}