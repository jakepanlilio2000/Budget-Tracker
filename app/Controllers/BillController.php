<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Logger;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Category;
use App\Services\TimelineService;
use App\Services\AchievementEngine;
use App\Models\CurrencyService;
use App\Services\FxpEngine;
use App\Services\LifetimeStatsService;
use App\Services\StreakEngine;
use App\Core\Database;
use App\Services\FinancialSummaryEngine;

class BillController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $bills = Bill::getActiveByUser(Auth::id());
        foreach ($bills as &$bill) {
            $bill['paid'] = Bill::getTotalPaid($bill['id']);
            $bill['remaining'] = max(0, $bill['total_amount'] - $bill['paid']);
            $bill['penalty'] = Bill::calculatePenalty($bill, $bill['paid']);
            $bill['is_overdue'] = strtotime($bill['next_due_date']) < time() && $bill['remaining'] > 0;
        }
        $this->view('bills.index', ['bills' => $bills]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'total_amount' => (float) ($_POST['total_amount'] ?? 0),
            'frequency' => $_POST['frequency'] ?? 'monthly',
            'duration' => (int) ($_POST['duration'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
            'next_due_date' => $_POST['next_due_date'] ?? date('Y-m-d'),
            'penalty_rate' => (float) ($_POST['penalty_rate'] ?? 0),
            'penalty_type' => $_POST['penalty_type'] ?? 'fixed',
            'notes' => trim($_POST['notes'] ?? '')
        ];
        if (empty($data['name'])) {
            Session::set('error', 'Bill name is required.');
            $this->redirect('/bills');
        }
        if ($data['total_amount'] <= 0) {
            Session::set('error', 'Amount must be greater than zero.');
            $this->redirect('/bills');
        }
        if (empty($data['next_due_date']) || strtotime($data['next_due_date']) === false) {
            Session::set('error', 'A valid due date is required.');
            $this->redirect('/bills');
        }

        if (Bill::create($userId, $data)) {
            TimelineService::logEvent(
                'bills',
                'bill_created',
                "Created bill: {$data['name']}",
                $data['total_amount'],
                CurrencyService::getUserBaseCurrency($userId)['id'],
                null,
                $data['category_id'],
                null,
                'fa-file-invoice',
                '#f59e0b'
            );
            FxpEngine::award($userId, 'pay_bill', 1);
            FxpEngine::award($userId, 'create_bill', 1);
            LifetimeStatsService::clearCache($userId);
            Session::set('success', 'Bill created successfully.');
            FxpEngine::award($userId, 'pay_bill', 1);
            FinancialSummaryEngine::invalidateCache($userId);
            $achResult = AchievementEngine::syncUser($userId);
            if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
                Session::set('achievement_notification', $achResult);
            }
        } else {
            Session::set('error', 'Failed to create bill.');
        }

        $this->redirect('/bills');
    }

    public function pay(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $bill = Bill::findById($id, $userId);

        if (!$bill) {
            Session::set('error', 'Bill not found.');
            $this->redirect('/bills');
        }

        $amountPaid = (float) ($_POST['amount_paid'] ?? 0);
        $accountId = !empty($_POST['account_id']) ? (int) $_POST['account_id'] : null;
        $notes = trim($_POST['payment_notes'] ?? '');

        $currentPaid = Bill::getTotalPaid($id);
        $penalty = Bill::calculatePenalty($bill, $currentPaid);

        if ($amountPaid <= 0) {
            Session::set('error', 'Payment amount must be greater than zero.');
            $this->redirect('/bills');
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            BillPayment::record($userId, $id, $amountPaid, $penalty, $accountId, $notes);
            if ($accountId) {
                $totalDeduct = $amountPaid + $penalty;
                $stmt = $db->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$totalDeduct, $accountId, $userId]);
            }

            $newTotalPaid = $currentPaid + $amountPaid;
            if ($newTotalPaid >= $bill['total_amount'] + $penalty) {
                Bill::advanceDueDate($id, $bill['frequency']);
            }

            $db->commit();
            $achResult = AchievementEngine::syncUser($userId);
            if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
                Session::set('achievement_notification', $achResult);
            }
            FxpEngine::award($userId, 'pay_bill', 1);
            StreakEngine::checkStreak($userId, 'daily_bills');
            LifetimeStatsService::clearCache($userId);
            FinancialSummaryEngine::invalidateCache($userId);
            Session::set('success', 'Payment recorded successfully.');

            LifetimeStatsService::clearCache($userId);
        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error("Bill payment failed", ['error' => $e->getMessage()]);
            Session::set('error', 'Failed to record payment.');
        }

        $this->redirect('/bills');
    }
    public function edit(int $id): void
    {
        $bill = Bill::findById($id, Auth::id());
        if (!$bill) {
            Session::set('error', 'Bill not found.');
            $this->redirect('/bills');
        }

        $categories = Category::getAllActiveByUser(Auth::id(), 'expense');
        $this->view('bills.edit', ['bill' => $bill, 'categories' => $categories]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $bill = Bill::findById($id, Auth::id());

        if (!$bill) {
            Session::set('error', 'Bill not found.');
            $this->redirect('/bills');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'total_amount' => (float) ($_POST['total_amount'] ?? 0),
            'frequency' => $_POST['frequency'] ?? 'monthly',
            'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
            'next_due_date' => $_POST['next_due_date'] ?? date('Y-m-d'),
            'penalty_rate' => (float) ($_POST['penalty_rate'] ?? 0),
            'penalty_type' => $_POST['penalty_type'] ?? 'fixed',
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['name']) || $data['total_amount'] <= 0) {
            Session::set('error', 'Bill name and valid amount are required.');
            $this->redirect('/bills/edit/' . $id);
        }

        Bill::update($id, Auth::id(), $data);
        Session::set('success', 'Bill updated successfully.');
        $this->redirect('/bills');
    }

    public function cancel(int $id): void
    {
        $this->validateCsrf();
        $bill = Bill::findById($id, Auth::id());

        if (!$bill) {
            Session::set('error', 'Bill not found.');
            $this->redirect('/bills');
        }

        Bill::cancel($id, Auth::id());
        Session::set('success', 'Bill cancelled successfully.');
        $this->redirect('/bills');
    }
}