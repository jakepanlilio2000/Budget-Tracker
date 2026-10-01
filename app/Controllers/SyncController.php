<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\TransactionModel;
use App\Services\MathService;
use App\Services\RequestValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\IdempotencyException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\AuthorizationException;

class SyncController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
    }

    public function syncTransactions(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $clientMutationId = !empty($_POST['client_mutation_id']) ? trim($_POST['client_mutation_id']) : (!empty($_POST['timestamp']) ? 'offline_' . $_POST['timestamp'] : null);

        try {
            $txnData = [
                'account_id' => (int) ($_POST['account_id'] ?? 0),
                'type' => $_POST['type'] ?? 'expense',
                'total_amount' => (string) ($_POST['total_amount'] ?? '0.00'),
                'currency_id' => !empty($_POST['currency_id']) ? (int) $_POST['currency_id'] : null,
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
                'status' => 'posted',
                'description' => trim($_POST['description'] ?? 'Offline Sync'),
                'notes' => trim($_POST['notes'] ?? 'Synced from offline device')
            ];

            $splits = [];
            if (isset($_POST['split_category']) && is_array($_POST['split_category'])) {
                foreach ($_POST['split_category'] as $i => $catId) {
                    $amount = (string) ($_POST['split_amount'][$i] ?? '0.00');
                    if (!empty($catId) && MathService::gt($amount, '0.00')) {
                        $splits[] = [
                            'category_id' => (int) $catId,
                            'amount' => $amount,
                            'notes' => trim($_POST['split_notes'][$i] ?? '')
                        ];
                    }
                }
            }

            $result = TransactionModel::createWithSplits($userId, $txnData, $splits, $clientMutationId);
            $this->json(['success' => true, 'data' => $result]);

        } catch (IdempotencyException $e) {
            // Idempotent duplicate: Return success so client removes from offline store
            $this->json(['success' => true, 'idempotent' => true, 'message' => 'Already processed']);
        } catch (ValidationException | InsufficientFundsException | AuthorizationException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => 'Sync database error: ' . $e->getMessage()], 500);
        }
    }
}