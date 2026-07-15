<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Logger;
use App\Models\PendingLedger;
use App\Models\Currency;

class PendingLedgerController extends Controller
{
    public function __construct() { if (!Auth::check()) $this->redirect('/login'); }

    public function index(): void
    {
        $items = PendingLedger::getUpcoming(Auth::id(), 50);
        $currencies = Currency::getAll();
        $this->view('pending_ledger.index', ['items' => $items, 'currencies' => $currencies]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $data = [
            'type' => $_POST['type'] ?? 'expense',
            'description' => trim($_POST['description'] ?? ''),
            'amount' => (float)($_POST['amount'] ?? 0),
            'currency_id' => (int)($_POST['currency_id'] ?? 1),
            'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
            'priority' => $_POST['priority'] ?? 'medium',
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['description']) || $data['amount'] <= 0) {
            Session::set('error', 'Description and valid amount are required.');
            $this->redirect('/pending-ledger');
        }

        PendingLedger::create(Auth::id(), $data);
        Logger::info("Pending ledger item created", ['user_id' => Auth::id(), 'desc' => $data['description']]);
        Session::set('success', 'Scheduled item added to pending ledger.');
        $this->redirect('/pending-ledger');
    }

    public function markPaid(int $id): void
    {
        $this->validateCsrf();
        PendingLedger::markAsPaid($id, Auth::id());
        Session::set('success', 'Item marked as paid.');
        $this->redirect('/pending-ledger');
    }
}