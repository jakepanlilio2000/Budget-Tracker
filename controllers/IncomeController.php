<?php
namespace controllers;
use core\Controller;
use config\Database;
use models\Profile;

class IncomeController extends Controller {
    
    public function index(int $profile_id): void {
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $this->redirect('/');
        }

        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM income_log WHERE profile_id = :pid ORDER BY date_received DESC, id DESC");
        $stmt->execute(['pid' => $profile_id]);
        $incomes = $stmt->fetchAll();
        $currentMonth = date('Y-m');
        $totalIncome = 0.0;
        $breakdown = [];

        foreach ($incomes as $inc) {
            if (strpos($inc['date_received'], $currentMonth) === 0) {
                $amt = (float)$inc['amount'];
                $totalIncome += $amt;
                
                $type = $inc['business_type'];
                if (!isset($breakdown[$type])) {
                    $breakdown[$type] = 0.0;
                }
                $breakdown[$type] += $amt;
            }
        }

        arsort($breakdown);

        $this->view('income/index', [
            'profile' => $profile,
            'incomes' => $incomes,
            'totalIncome' => $totalIncome,
            'breakdown' => $breakdown
        ]);
    }

    public function store(int $profile_id): void {
        $this->checkCsrf();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            INSERT INTO income_log (profile_id, source_name, platform, amount, payment_method, business_type, date_received)
            VALUES (:pid, :source, :platform, :amount, :method, :type, :date)
        ");
        
        $stmt->execute([
            'pid' => $profile_id,
            'source' => htmlspecialchars($_POST['source_name']),
            'platform' => htmlspecialchars($_POST['platform']),
            'amount' => preg_replace('/[^0-9.]/', '', $_POST['amount']),
            'method' => $_POST['payment_method'],
            'type' => $_POST['business_type'],
            'date' => $_POST['date_received'] ?: date('Y-m-d')
        ]);

        $this->redirect("/income/{$profile_id}");
    }

    public function delete(int $id): void {
        $this->checkCsrf();
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT i.profile_id FROM income_log i 
            JOIN profiles p ON i.profile_id = p.id 
            WHERE i.id = :id AND p.user_id = :uid
        ");
        $stmt->execute(['id' => $id, 'uid' => $_SESSION['user_id']]);
        $record = $stmt->fetch();

        if ($record) {
            $db->prepare("DELETE FROM income_log WHERE id = :id")->execute(['id' => $id]);
            $this->redirect("/income/" . $record['profile_id']);
        } else {
            $this->redirect('/');
        }
    }
}