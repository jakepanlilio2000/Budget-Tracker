<?php
namespace controllers;
use core\Controller;
use models\BudgetEntry;
use models\Category;
use config\Database;

class EntryController extends Controller {
    public function index(int $profile_id): void {
        $entryModel = new BudgetEntry();
        $catModel = new Category();
        
        $entries = $entryModel->getWithFrequencies($profile_id);
        $categories = $catModel->findAll(['profile_id' => $profile_id], 'sort_order ASC');
        
        $this->view('entries/index', [
            'entries' => $entries, 
            'categories' => $categories,
            'profile_id' => $profile_id
        ]);
    }

    public function store(int $profile_id): void {
        $this->checkCsrf();
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $amount = preg_replace('/[^0-9.]/', '', $_POST['amount']);
            $entryData = [
                'profile_id' => $profile_id,
                'category_id' => (int)$_POST['category_id'],
                'name' => htmlspecialchars($_POST['name']),
                'amount' => $amount,
                'type' => $_POST['type'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'notes' => htmlspecialchars($_POST['notes'] ?? '')
            ];
            
            $entryModel = new BudgetEntry();
            $entry_id = $entryModel->create($entryData);
            $freqModel = new \models\Frequency();
            $freqData = [
                'entry_id' => $entry_id,
                'frequency_type' => $_POST['frequency_type'],
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null
            ];

            if ($_POST['frequency_type'] === 'semi_monthly') {
                if (isset($_POST['sm_first'])) {
                    $freqModel->create(array_merge($freqData, ['is_first_half' => 1]));
                }
                if (isset($_POST['sm_second'])) {
                    $freqModel->create(array_merge($freqData, ['is_first_half' => 0]));
                }
            } elseif ($_POST['frequency_type'] === 'custom_months') {
                $freqData['total_months'] = (int)$_POST['total_months'];
                $freqData['specific_day'] = (int)$_POST['specific_day'];
                $freqModel->create($freqData);
            } else {
                $freqData['specific_day'] = !empty($_POST['specific_day']) ? (int)$_POST['specific_day'] : null;
                $freqData['specific_date'] = !empty($_POST['specific_date']) ? $_POST['specific_date'] : null;
                $freqModel->create($freqData);
            }

            $db->commit();
            $this->redirect("/entries/{$profile_id}");

        } catch (\Exception $e) {
            $db->rollBack();
            die("Error saving entry: " . $e->getMessage());
        }
    }

    public function toggleActive(int $id): void {
        $this->checkCsrf();
        $entryModel = new BudgetEntry();
        $success = $entryModel->toggleActive($id);
        $this->json(['success' => $success]);
    }
    
    public function delete(int $id): void {
        $this->checkCsrf();
        $entryModel = new \models\BudgetEntry();
        
        if ($entryModel->delete($id)) {
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false, 'error' => 'Failed to delete entry'], 500);
        }
    }

    public function create(int $profile_id): void {
        $catModel = new \models\Category();
        $categories = $catModel->findAll(['profile_id' => $profile_id], 'sort_order ASC');
        $this->view('entries/create', [
            'profile_id' => $profile_id,
            'categories' => $categories
        ]);
    }

    public function edit(int $id): void {
        $entryModel = new \models\BudgetEntry();
        $entry = $entryModel->find($id);
        if (!$entry) $this->redirect('/');
        
        $freqModel = new \models\Frequency();
        $freqs = $freqModel->findAll(['entry_id' => $id]);
        if (!empty($freqs)) {
            $freqData = $freqs[0];
            unset($freqData['id']); 
            $entry = array_merge($entry, $freqData);
        }

        $catModel = new \models\Category();
        $categories = $catModel->findAll(['profile_id' => $entry['profile_id']], 'sort_order ASC');

        $this->view('entries/edit', [
            'entry' => $entry,
            'profile_id' => $entry['profile_id'],
            'categories' => $categories
        ]);
    }

    public function update(int $id): void {
        $this->checkCsrf();
        $db = \config\Database::getInstance();
        $db->beginTransaction();

        try {
            $entryModel = new \models\BudgetEntry();
            $entry = $entryModel->find($id);
            if (!$entry) throw new \Exception("Entry not found");
            
            $profile_id = $entry['profile_id'];
            $amount = preg_replace('/[^0-9.]/', '', $_POST['amount']);
            
            $entryModel->update($id, [
                'category_id' => (int)$_POST['category_id'],
                'name' => htmlspecialchars($_POST['name']),
                'amount' => $amount,
                'type' => $_POST['type'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'notes' => htmlspecialchars($_POST['notes'] ?? '')
            ]);

            $stmt = $db->prepare("DELETE FROM entry_frequencies WHERE entry_id = :eid");
            $stmt->execute(['eid' => $id]);

            $freqModel = new \models\Frequency();
            $freqData = [
                'entry_id' => $id,
                'frequency_type' => $_POST['frequency_type']
            ];

            if ($_POST['frequency_type'] === 'semi_monthly') {
                if (isset($_POST['sm_first'])) $freqModel->create(array_merge($freqData, ['is_first_half' => 1]));
                if (isset($_POST['sm_second'])) $freqModel->create(array_merge($freqData, ['is_first_half' => 0]));
            } elseif ($_POST['frequency_type'] === 'custom_months') {
                $freqData['total_months'] = (int)$_POST['total_months'];
                $freqData['specific_day'] = (int)$_POST['specific_day'];
                $freqModel->create($freqData);
            } else {
                $freqData['specific_day'] = !empty($_POST['specific_day']) ? (int)$_POST['specific_day'] : null;
                $freqModel->create($freqData);
            }

            $db->commit();
            $this->redirect("/entries/{$profile_id}");

        } catch (\Exception $e) {
            $db->rollBack();
            die("Error updating entry: " . $e->getMessage());
        }
    }
}