<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Logger;
use App\Models\PendingLedger;
use App\Services\TimelineService;
use App\Core\Database;
use App\Services\AchievementEngine;
use App\Services\FxpEngine;
use App\Services\LifetimeStatsService;
use App\Services\FinancialSummaryEngine;

class PendingLedgerController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT pl.*, c.symbol 
            FROM pending_ledger pl 
            LEFT JOIN currencies c ON pl.currency_id = c.id 
            WHERE pl.user_id = ? AND pl.status = 'pending' 
            ORDER BY pl.due_date ASC
        ");
        $stmt->execute([$userId]);
        $pendingItems = $stmt->fetchAll();
        $stmt = $db->prepare("
            SELECT pl.*, c.symbol 
            FROM pending_ledger pl 
            LEFT JOIN currencies c ON pl.currency_id = c.id 
            WHERE pl.user_id = ? AND pl.status = 'paid' 
            ORDER BY pl.updated_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $paidItems = $stmt->fetchAll();
        $stmt = $db->query("SELECT id, code, name FROM currencies ORDER BY code ASC");
        $currencies = $stmt->fetchAll();

        $this->view('pending_ledger.index', [
            'items' => $pendingItems,
            'pendingItems' => $pendingItems,
            'paidItems' => $paidItems,
            'currencies' => $currencies
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $data = [
            'type' => $_POST['type'] ?? 'expense',
            'description' => trim($_POST['description'] ?? ''),
            'amount' => (float) ($_POST['amount'] ?? 0),
            'currency_id' => (int) ($_POST['currency_id'] ?? 1),
            'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
            'priority' => $_POST['priority'] ?? 'medium',
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['description']) || $data['amount'] <= 0) {
            Session::set('error', 'Description and valid amount are required.');
            $this->redirect('/pending-ledger');
        }

        PendingLedger::create(Auth::id(), $data);
        FxpEngine::award($userId, 'create_pending', 1);
        LifetimeStatsService::clearCache($userId);
                    FinancialSummaryEngine::invalidateCache($userId);
        Session::set('success', 'Scheduled item added to pending ledger.');
        $achResult = AchievementEngine::syncUser($userId);
        if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
            Session::set('achievement_notification', $achResult);
        }
        $this->redirect('/pending-ledger');
    }

    public function markPaid(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM pending_ledger WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $item = $stmt->fetch();

        if (!$item) {
            Session::set('error', 'Pending item not found.');
            $this->redirect('/pending-ledger');
        }
        $createTransaction = isset($_POST['create_transaction']) && $_POST['create_transaction'] === '1';
        $accountId = !empty($_POST['account_id']) ? (int) $_POST['account_id'] : null;
        $categoryId = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;

        if ($createTransaction && $accountId) {
            try {
                $db->beginTransaction();
                $txnStmt = $db->prepare("
                INSERT INTO transactions 
                (user_id, account_id, category_id, type, total_amount, currency_id, transaction_date, status, description, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'posted', ?, ?)
            ");
                $txnStmt->execute([
                    $userId,
                    $accountId,
                    $categoryId,
                    $item['type'],
                    (float) $item['amount'],
                    (int) $item['currency_id'],
                    $item['due_date'],
                    $item['description'],
                    $item['notes']
                ]);
                $txnId = (int) $db->lastInsertId();
                if ($categoryId) {
                    $splitStmt = $db->prepare("
                    INSERT INTO transaction_splits (transaction_id, category_id, amount, notes) 
                    VALUES (?, ?, ?, ?)
                ");
                    $splitStmt->execute([$txnId, $categoryId, (float) $item['amount'], $item['notes']]);
                }
                $multiplier = ($item['type'] === 'income') ? 1 : -1;
                $change = (float) $item['amount'] * $multiplier;
                $updateAcc = $db->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
                $updateAcc->execute([$change, $accountId, $userId]);
                PendingLedger::markAsPaid($id, $userId);
                TimelineService::logEvent(
                    'pending_ledger',
                    'paid_with_transaction',
                    $item['description'] . ' (from Pending Ledger)',
                    (float) $item['amount'],
                    (int) $item['currency_id'],
                    $accountId,
                    $categoryId,
                    $id,
                    $item['type'] === 'income' ? 'fa-arrow-down' : 'fa-arrow-up',
                    $item['type'] === 'income' ? '#10b981' : '#ef4444'
                );

                $db->commit();
                $achResult = AchievementEngine::syncUser($userId);
                if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
                    Session::set('achievement_notification', $achResult);
                }
                FxpEngine::award($userId, 'pending_paid', 1);
                LifetimeStatsService::clearCache($userId);
                Session::set('success', 'Transaction created and item marked as paid.');

            } catch (\Exception $e) {
                $db->rollBack();
                Logger::error("Pending ledger payment failed", ['error' => $e->getMessage()]);
                Session::set('error', 'Failed to create transaction: ' . $e->getMessage());
                $this->redirect('/pending-ledger');
            }
        } else {
            PendingLedger::markAsPaid($id, $userId);
            TimelineService::logEvent(
                'pending_ledger',
                'paid',
                $item['description'] . ' (Pending Ledger)',
                (float) $item['amount'],
                (int) $item['currency_id'],
                null,
                null,
                $id,
                $item['type'] === 'income' ? 'fa-arrow-down' : 'fa-arrow-up',
                $item['type'] === 'income' ? '#10b981' : '#ef4444'
            );

            Session::set('success', 'Item marked as paid.');
        }

        $this->redirect('/pending-ledger');
    }

}