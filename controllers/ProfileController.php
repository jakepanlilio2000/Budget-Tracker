<?php
namespace controllers;
use core\Controller;
use models\Profile;

class ProfileController extends Controller {
    public function index(): void {

        if (!isset($_SESSION['user_id'])) {
            $this->view('pages/landing');
            return;
        }

        $profileModel = new \models\Profile();
        $profiles = $profileModel->findAll(['user_id' => $_SESSION['user_id']]);
        
        $db = \config\Database::getInstance();
        $globalNetWorth = 0.0;
        $globalInflow = 0.0;
        $globalOutflow = 0.0;
        
        $chartData = ['labels' => [], 'inflow' => [], 'outflow' => []];

        foreach ($profiles as &$p) {
            $stmt = $db->prepare("SELECT type, SUM(amount) as total FROM transactions WHERE profile_id = :pid AND is_checked = 1 GROUP BY type");
            $stmt->execute(['pid' => $p['id']]);
            $rows = $stmt->fetchAll();
            
            $in = 0.0; $out = 0.0;
            foreach ($rows as $r) {
                if ($r['type'] === 'inflow') $in = (float)$r['total'];
                if ($r['type'] === 'outflow') $out = (float)$r['total'];
            }
            
            $net = $in - $out;
            $p['calculated_net'] = $net;
            
            $globalInflow += $in;
            $globalOutflow += $out;
            $globalNetWorth += $net;

            $chartData['labels'][] = $p['name'];
            $chartData['inflow'][] = $in;
            $chartData['outflow'][] = $out;
        }

        $this->view('profiles/index', [
            'profiles' => $profiles,
            'globalNetWorth' => $globalNetWorth,
            'globalInflow' => $globalInflow,
            'globalOutflow' => $globalOutflow,
            'chartData' => $chartData
        ]);
    }

    public function create(): void {
        $this->view('profiles/create');
    }

    public function store(): void {
        $this->checkCsrf();
        $profileModel = new Profile();
        
        $data = [
            'user_id' => $_SESSION['user_id'],
            'name' => htmlspecialchars($_POST['name']),
            'currency' => htmlspecialchars($_POST['currency']),
            'color' => htmlspecialchars($_POST['color']),
            'pay_schedule' => $_POST['pay_schedule'],
            'pay_day_1' => (int)($_POST['pay_day_1'] ?? 15),
            'pay_day_2' => (int)($_POST['pay_day_2'] ?? 30),
            'weekly_day' => (int)($_POST['weekly_day'] ?? 5),
            'base_income' => preg_replace('/[^0-9.]/', '', $_POST['base_income']),
            'notes' => htmlspecialchars($_POST['notes'] ?? '')
        ];

        $id = $profileModel->create($data);
        
        if (!empty($_POST['clone_id'])) {
            // (Implementation of clone logic would copy categories, entries, and entry_frequencies)
        }

        $this->redirect("/dashboard/{$id}");
    }

    public function edit(int $id): void {
        $profileModel = new Profile();
        $profile = $profileModel->find($id);
        $this->view('profiles/edit', ['profile' => $profile]);
    }

   public function delete(int $id): void {
        $this->checkCsrf();
        $profileModel = new \models\Profile();
        $profileModel->deleteProfileFull($id);
        
        $this->redirect('/');
    }

    public function update(int $id): void {
        $this->checkCsrf();
        $profileModel = new \models\Profile();
        
        $data = [
            'name' => htmlspecialchars($_POST['name']),
            'currency' => htmlspecialchars($_POST['currency']),
            'color' => htmlspecialchars($_POST['color']),
            'pay_schedule' => $_POST['pay_schedule'],
            'pay_day_1' => (int)($_POST['pay_day_1'] ?? 15),
            'pay_day_2' => (int)($_POST['pay_day_2'] ?? 30),
            'base_income' => preg_replace('/[^0-9.]/', '', $_POST['base_income']),
            'notes' => htmlspecialchars($_POST['notes'] ?? '')
        ];

        $profileModel->update($id, $data);
        $this->redirect("/dashboard/{$id}");
    }
}