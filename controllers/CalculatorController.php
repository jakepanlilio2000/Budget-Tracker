<?php
namespace controllers;
use core\Controller;
use models\Transaction;

class CalculatorController extends Controller {
    public function index(int $profile_id): void {
        $this->view('calculator/index', ['profile_id' => $profile_id]);
    }

    public function importPeriod(int $profile_id): void {
        $period_date = $_GET['period_date'] ?? date('Y-m-d');
        $txModel = new Transaction();
        $transactions = $txModel->getForPeriod($profile_id, $period_date);
        
        $items = array_map(function($tx) {
            return [
                'label' => $tx['name'],
                'amount' => $tx['amount'],
                'type' => $tx['type'],
                'checked' => (bool)$tx['is_checked']
            ];
        }, $transactions);

        $this->json(['items' => $items]);
    }
}