<?php
namespace controllers;
use core\Controller;
use models\Profile;
use models\BudgetEntry;
use models\Transaction;

class DashboardController extends Controller {
    public function index(int $profile_id): void {
        $profileModel = new \models\Profile();
        $entryModel = new \models\BudgetEntry();
        $txModel = new \models\Transaction();
        $catModel = new \models\Category(); // Add this

        $profile = $profileModel->find($profile_id);
        if (!$profile) $this->redirect('/');

        $year = (int)($_GET['year'] ?? date('Y'));
        $activePeriods = $profileModel->getActivePeriods($profile_id, $year);
        $categories = $catModel->findAll(['profile_id' => $profile_id], 'sort_order ASC'); // Fetch for modal
        
        // FIX 5: Find the closest upcoming period to today
        $today = date('Y-m-d');
        $selectedPeriod = $_GET['period'] ?? null;
        if (!$selectedPeriod) {
            $selectedPeriod = end($activePeriods); // Default to last if none found
            foreach ($activePeriods as $p) {
                if ($p >= $today) {
                    $selectedPeriod = $p;
                    break;
                }
            }
        }

        $due_entries = $entryModel->getActiveForPeriod($profile_id, $selectedPeriod, $profile);
        $txModel->syncPeriod($profile_id, $selectedPeriod, $due_entries);

        $transactions = $txModel->getForPeriod($profile_id, $selectedPeriod);
        $summary = $profileModel->calculateSummary($profile_id, $selectedPeriod);
        $grouped_tx = ['inflow' => [], 'outflow' => []];
        foreach ($transactions as $tx) {
            $grouped_tx[$tx['type']][$tx['category_id']]['name'] = $tx['category_name'];
            $grouped_tx[$tx['type']][$tx['category_id']]['items'][] = $tx;
        }

        $selectedMonth = substr($selectedPeriod, 0, 7);
        $monthPeriods = array_filter($activePeriods, fn($p) => str_starts_with($p, $selectedMonth));
        $monthOutflows = [];
        foreach ($monthPeriods as $mp) {
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

    public function toggleTx(int $id): void {
        $this->checkCsrf();
        $txModel = new \models\Transaction();
        
        $tx = $txModel->find($id);
        
        $state = filter_var($_POST['state'], FILTER_VALIDATE_BOOLEAN);
        $txModel->toggleCheck($id, $state);
        
        $profileModel = new \models\Profile();
        $summary = $profileModel->calculateSummary($tx['profile_id'], $tx['period_date']);
        
        $this->json(['success' => true, 'summary' => $summary]);
    }

    public function updateTxAmount(int $id): void {
        $this->checkCsrf();
        $txModel = new \models\Transaction();
        $amount = preg_replace('/[^0-9.]/', '', $_POST['amount']);
        $txModel->updateAmount($id, $amount);
        $this->json(['success' => true, 'amount' => number_format((float)$amount, 2)]);
    }

    public function quickAdd(int $profile_id): void {
        $this->checkCsrf();
        $db = \config\Database::getInstance();
        $db->beginTransaction();

        try {
            $amount = preg_replace('/[^0-9.]/', '', $_POST['amount']);
            $period_date = !empty($_POST['period_date']) ? $_POST['period_date'] : date('Y-m-d');
            
            $type = $_POST['type'] === 'inflow' ? 'inflow' : 'outflow';
            
            $entryModel = new \models\BudgetEntry();
            $entry_id = $entryModel->create([
                'profile_id' => $profile_id,
                'category_id' => (int)$_POST['category_id'],
                'name' => htmlspecialchars($_POST['name']),
                'amount' => $amount,
                'type' => $type,
                'is_active' => 0, 
                'notes' => 'Quick added from dashboard'
            ]);

            $freqModel = new \models\Frequency();
            $freqModel->create([
                'entry_id' => $entry_id,
                'frequency_type' => 'one_time',
                'specific_date' => $period_date
            ]);

            $txModel = new \models\Transaction();
            $txModel->create([
                'profile_id' => $profile_id,
                'entry_id' => $entry_id,
                'category_id' => (int)$_POST['category_id'],
                'name' => htmlspecialchars($_POST['name']),
                'amount' => $amount,
                'type' => $type,
                'period_date' => $period_date,
                'is_checked' => 1
            ]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            die("Database Error during Quick Add: " . $e->getMessage());
        }

        $this->redirect("/dashboard/{$profile_id}?period=" . $period_date);
    }
}