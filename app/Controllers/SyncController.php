<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Transaction;

class SyncController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
    }

    public function syncTransactions(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $txnData = [
            'account_id' => (int) ($_POST['account_id'] ?? 0),
            'type' => $_POST['type'] ?? 'expense',
            'total_amount' => (float) ($_POST['total_amount'] ?? 0),
            'currency_id' => (int) ($_POST['currency_id'] ?? 1),
            'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
            'status' => 'posted',
            'description' => trim($_POST['description'] ?? 'Offline Sync'),
            'notes' => trim($_POST['notes'] ?? 'Synced from offline device')
        ];

        $splits = [];
        if (isset($_POST['split_category']) && is_array($_POST['split_category'])) {
            foreach ($_POST['split_category'] as $i => $catId) {
                $amount = (float) ($_POST['split_amount'][$i] ?? 0);
                if ($amount > 0 && !empty($catId)) {
                    $splits[] = [
                        'category_id' => (int) $catId,
                        'amount' => $amount,
                        'notes' => trim($_POST['split_notes'][$i] ?? '')
                    ];
                }
            }
        }

        if (abs(array_sum(array_column($splits, 'amount')) - $txnData['total_amount']) > 0.01) {
            $this->json(['success' => false, 'error' => 'Split amounts do not match total'], 400);
        }

        if (Transaction::createWithSplits($userId, $txnData, $splits)) {
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false, 'error' => 'Database error'], 500);
        }
    }
}