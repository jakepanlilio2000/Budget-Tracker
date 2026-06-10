<?php
namespace controllers;
use core\Controller;
use models\Transaction;
use models\Profile;

class CalculatorController extends Controller {
    public function index(int $profile_id): void {
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile) $this->redirect('/');

        $this->view('calculator/index', [
            'profile_id' => $profile_id,
            'profile' => $profile
        ]);
    }

    public function importPeriod(int $profile_id): void {
        // FIXED: Switch from exact Date to the new Monthly Bucket format
        $period_month = $_GET['period'] ?? date('Y-m');
        $txModel = new Transaction();
        
        // FIXED: Call the updated getForMonth method
        $transactions = $txModel->getForMonth($profile_id, $period_month);
        
        $items = array_map(function($tx) {
            return [
                'label' => $tx['name'],
                // Import the Planned Master Totality instead of the remaining actual amount
                'amount' => max((float)$tx['master_amount'], (float)$tx['amount']),
                'type' => $tx['type'],
                'checked' => (bool)$tx['is_checked']
            ];
        }, $transactions);

        $this->json(['items' => $items]);
    }
}