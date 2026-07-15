<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Logger;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Account;

class BillController extends Controller
{
    public function __construct() { if (!Auth::check()) $this->redirect('/login'); }

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
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'total_amount' => (float)($_POST['total_amount'] ?? 0),
            'frequency' => $_POST['frequency'] ?? 'monthly',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'recurring_count' => !empty($_POST['recurring_count']) ? (int)$_POST['recurring_count'] : null, // Null = indefinite
            'next_due_date' => $_POST['next_due_date'] ?? date('Y-m-d'),
            'penalty_rate' => (float)($_POST['penalty_rate'] ?? 0),
            'penalty_type' => $_POST['penalty_type'] ?? 'fixed',
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['name']) || $data['total_amount'] <= 0) {
            Session::set('error', 'Bill name and valid amount are required.');
            $this->redirect('/bills');
        }

        Bill::create(Auth::id(), $data);
        Logger::info("Bill created", ['user_id' => Auth::id(), 'name' => $data['name']]);
        Session::set('success', 'Bill added successfully.');
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

        $amountPaid = (float)($_POST['amount_paid'] ?? 0);
        $accountId = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;
        $notes = trim($_POST['payment_notes'] ?? '');

        $currentPaid = Bill::getTotalPaid($id);
        $penalty = Bill::calculatePenalty($bill, $currentPaid);

        if ($amountPaid <= 0) {
            Session::set('error', 'Payment amount must be greater than zero.');
            $this->redirect('/bills');
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            BillPayment::record($userId, $id, $amountPaid, $penalty, $accountId, $notes);

            // If linked to an account, deduct the balance
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
            Logger::info("Bill payment recorded", ['user_id' => $userId, 'bill_id' => $id, 'amount' => $amountPaid]);
            Session::set('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error("Bill payment failed", ['error' => $e->getMessage()]);
            Session::set('error', 'Failed to record payment.');
        }

        $this->redirect('/bills');
    }
}