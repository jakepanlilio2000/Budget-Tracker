<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Account;
use App\Models\Currency;
use App\Services\AchievementEngine;
use App\Services\FxpEngine;
use App\Services\LifetimeStatsService;
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
        $userId = Auth::id();
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
        $achResult = AchievementEngine::syncUser($userId);
        if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
            Session::set('achievement_notification', $achResult);
        }
        FxpEngine::award($userId, 'create_account', 1);
        LifetimeStatsService::clearCache($userId);
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
        $userId = Auth::id();
        $account = Account::findById($id, Auth::id());
        if (!$account) {
            Session::set('error', 'Account not found.');
            $this->redirect('/accounts');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? 'bank',
            'currency_id' => (int) ($_POST['currency_id'] ?? 1),
            'institution' => trim($_POST['institution'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['name'])) {
            Session::set('error', 'Account name is required.');
            $this->redirect('/accounts/edit/' . $id);
        }

        Account::update($id, Auth::id(), $data);
        $achResult = AchievementEngine::syncUser($userId);

        if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
            Session::set('achievement_notification', $achResult);
        }
        Session::set('success', 'Account updated successfully.');
        $this->redirect('/accounts');
    }
    public function adjust(int $id): void
    {
        $account = Account::findById($id, Auth::id());
        if (!$account) {
            Session::set('error', 'Account not found.');
            $this->redirect('/accounts');
        }
        $this->view('accounts.adjust', ['account' => $account]);
    }

    public function processAdjustment(int $id): void
    {
        $this->validateCsrf();
        $account = Account::findById($id, Auth::id());
        if (!$account) {
            Session::set('error', 'Account not found.');
            $this->redirect('/accounts');
        }

        $newBalance = (float) ($_POST['new_balance'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if (empty($reason)) {
            Session::set('error', 'A reason for the adjustment is required for audit purposes.');
            $this->redirect('/accounts/adjust/' . $id);
        }

        if (Account::adjustBalance($id, Auth::id(), $newBalance, $reason, (int) $account['currency_id'])) {
            Session::set('success', 'Account balance adjusted successfully. An adjustment transaction has been recorded.');
            $this->redirect('/accounts');
        } else {
            Session::set('error', 'Failed to adjust account balance.');
            $this->redirect('/accounts/adjust/' . $id);
        }
    }
}