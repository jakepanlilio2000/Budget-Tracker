<?php
namespace controllers;
use core\Controller;
use models\Profile;
use models\BudgetEntry;
use models\Transaction;
use models\Category;
use models\Frequency;
use config\Database;

class DashboardController extends Controller {
    
    // Removed strict 'int' type to safely accept URL strings from the Router
    public function index($profile_id): void {
        $profile_id = (int)$profile_id; // Cast safely here
        
        $profileModel = new Profile();
        $entryModel = new BudgetEntry();
        $txModel = new Transaction();
        $catModel = new Category();

        $profile = $profileModel->find($profile_id);
        if (!$profile) $this->redirect('/');

        $year = (int)($_GET['year'] ?? date('Y'));
        $activePeriods = $profileModel->getActivePeriods($profile_id, $year);
        $categories = $catModel->findAll(['profile_id' => $profile_id], 'sort_order ASC');
        
        $todayMonth = date('Y-m');
        $selectedPeriod = $_GET['period'] ?? null;
        
        if (!$selectedPeriod) {
            if (in_array($todayMonth, $activePeriods)) {
                $selectedPeriod = $todayMonth;
            } else {
                $selectedPeriod = end($activePeriods);
            }
        }

        $due_entries = $entryModel->getActiveForMonth($profile_id, $selectedPeriod);
        $txModel->syncMonth($profile_id, $selectedPeriod, $due_entries);

        $transactions = $txModel->getForMonth($profile_id, $selectedPeriod);
        $summary = $profileModel->calculateSummary($profile_id, $selectedPeriod);
        $grouped_tx = ['inflow' => [], 'outflow' => []];
        
        foreach ($transactions as $tx) {
            $grouped_tx[$tx['type']][$tx['category_id']]['name'] = $tx['category_name'];
            $grouped_tx[$tx['type']][$tx['category_id']]['items'][] = $tx;
        }

        $monthOutflows = [];
        foreach ($activePeriods as $mp) {
            $sum = $profileModel->calculateSummary($profile_id, $mp);
            $monthOutflows[$mp] = (float)$sum['total_outflow'];
        }
        
        $this->view('dashboard/index', [
            'profile' => $profile,
            'year' => $year,
            'periods' => $activePeriods,
            'selectedPeriod' => $selectedPeriod,
            'transactions' => $grouped_tx,
            'summary' => $summary,
            'categories' => $categories,
            'monthOutflows' => $monthOutflows
        ]);
    }

    public function toggleTx($id): void {
        $id = (int)$id;
        $this->checkCsrf();
        $txModel = new Transaction();
        
        $tx = $txModel->find($id);
        if (!$tx) {
            $this->json(['success' => false, 'error' => 'Transaction not found']);
            return;
        }
        
        $state = filter_var($_POST['state'], FILTER_VALIDATE_BOOLEAN);
        $txModel->toggleCheck($id, $state);
        
        $profileModel = new Profile();
        $summary = $profileModel->calculateSummary($tx['profile_id'], substr($tx['period_date'], 0, 7));
        
        $this->json(['success' => true, 'summary' => $summary]);
    }

    public function updateTxAmount($id): void {
        $id = (int)$id;
        $this->checkCsrf();
        $txModel = new Transaction();
        
        if (!isset($_POST['amount'])) {
            $this->json(['success' => false, 'error' => 'Invalid amount payload']);
            return;
        }
        
        $amount = preg_replace('/[^0-9.]/', '', $_POST['amount']);
        if ($amount === '') $amount = 0;
        
        $txModel->updateAmount($id, $amount);
        $this->json(['success' => true, 'amount' => number_format((float)$amount, 2)]);
    }

    public function partialPayTx($id): void {
        $id = (int)$id;
        $this->checkCsrf();
        $txModel = new Transaction();
        
        $tx = $txModel->find($id);
        if (!$tx) {
            $this->json(['success' => false, 'error' => 'Transaction not found']);
            return;
        }
        
        $amountToPay = preg_replace('/[^0-9.]/', '', $_POST['amount'] ?? '0');
        if ((float)$amountToPay <= 0) {
            $this->json(['success' => false, 'error' => 'Please enter an amount greater than 0']);
            return;
        }

        // Deduct from DB balance
        $currentAmount = (float)$tx['amount'];
        $newAmount = max(0, $currentAmount - (float)$amountToPay);
        
        $profileModel = new Profile();
        $profile = $profileModel->find($tx['profile_id']);
        $currency = $profile['currency'] ?? '₱';

        // Log notation
        $dateStr = date('M j');
        $noteAddition = " [Logged {$currency}" . number_format((float)$amountToPay, 2) . " {$dateStr}]";
        $newName = substr($tx['name'] . $noteAddition, 0, 150);
        
        // Auto-check if balance reaches 0
        $isChecked = ($newAmount == 0) ? 1 : $tx['is_checked'];

        $txModel->update($id, [
            'amount' => $newAmount,
            'name' => $newName,
            'is_checked' => $isChecked
        ]);
        
        $this->json(['success' => true]);
    }

    public function quickAdd($profile_id): void {
        $profile_id = (int)$profile_id;
        $this->checkCsrf();
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $amount_raw = $_POST['amount'] ?? '0';
            $amount = preg_replace('/[^0-9.]/', '', $amount_raw);
            if ($amount === '') $amount = 0;
            
            $type = isset($_POST['type']) && $_POST['type'] === 'inflow' ? 'inflow' : 'outflow';
            
            $period_raw = $_POST['specific_date'] ?? $_POST['period_date'] ?? '';
            $period_month = !empty($period_raw) ? substr($period_raw, 0, 7) : date('Y-m');
            $period_date_db = $period_month . '-01';
            
            $entryModel = new BudgetEntry();
            $entry_id = $entryModel->create([
                'profile_id' => $profile_id,
                'category_id' => (int)$_POST['category_id'],
                'name' => htmlspecialchars($_POST['name'] ?? ''),
                'amount' => $amount,
                'type' => $type,
                'is_active' => isset($_POST['is_active']) ? 1 : 0, 
                'notes' => htmlspecialchars($_POST['notes'] ?? 'Quick added from dashboard')
            ]);

            $freqModel = new Frequency();
            $freqData = [
                'entry_id' => $entry_id,
                'frequency_type' => $_POST['frequency_type'] ?? 'one_time',
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null
            ];

            if ($freqData['frequency_type'] === 'semi_monthly') {
                if (isset($_POST['sm_first'])) $freqModel->create(array_merge($freqData, ['is_first_half' => 1]));
                if (isset($_POST['sm_second'])) $freqModel->create(array_merge($freqData, ['is_first_half' => 0]));
            } elseif ($freqData['frequency_type'] === 'custom_months') {
                $freqData['total_months'] = (int)($_POST['total_months'] ?? 0);
                $freqData['specific_day'] = (int)($_POST['specific_day'] ?? 0);
                $freqModel->create($freqData);
            } elseif ($freqData['frequency_type'] === 'one_time') {
                $freqData['specific_date'] = !empty($_POST['specific_date']) ? $_POST['specific_date'] : date('Y-m-d');
                $freqModel->create($freqData);
            } else { 
                $freqData['specific_day'] = !empty($_POST['specific_day']) ? (int)$_POST['specific_day'] : null;
                $freqModel->create($freqData);
            }

            $txModel = new Transaction();
            $txModel->create([
                'profile_id' => $profile_id,
                'entry_id' => $entry_id,
                'category_id' => (int)$_POST['category_id'],
                'name' => htmlspecialchars($_POST['name'] ?? ''),
                'amount' => $amount,
                'type' => $type,
                'period_date' => $period_date_db,
                'is_checked' => 0 
            ]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            die("Database Error during Quick Add: " . $e->getMessage());
        }

        $this->redirect("/dashboard/{$profile_id}?period=" . $period_month);
    }
}