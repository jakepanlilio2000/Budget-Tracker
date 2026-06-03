<?php
namespace controllers;
use core\Controller;
use config\Database;
use models\Profile;

class ShoppingController extends Controller {
    
    public function index(int $profile_id): void {
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $this->redirect('/');
        }

        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT * FROM shopping_log WHERE profile_id = :pid ORDER BY purchase_date DESC, id DESC");
        $stmt->execute(['pid' => $profile_id]);
        $purchases = $stmt->fetchAll();

        $currentMonth = date('Y-m');
        $totalSpent = 0.0;
        $totalNeeds = 0.0;
        $totalWants = 0.0;

        foreach ($purchases as $p) {
            if (strpos($p['purchase_date'], $currentMonth) === 0) {
                $totalSpent += (float)$p['amount'];
                if ($p['is_need']) {
                    $totalNeeds += (float)$p['amount'];
                } else {
                    $totalWants += (float)$p['amount'];
                }
            }
        }

        $this->view('shopping/index', [
            'profile' => $profile,
            'purchases' => $purchases,
            'totalSpent' => $totalSpent,
            'totalNeeds' => $totalNeeds,
            'totalWants' => $totalWants
        ]);
    }

    public function store(int $profile_id): void {
        $this->checkCsrf();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            INSERT INTO shopping_log (profile_id, item_name, store_name, amount, payment_method, is_need, purchase_date)
            VALUES (:pid, :item, :store, :amount, :method, :need, :date)
        ");
        
        $stmt->execute([
            'pid' => $profile_id,
            'item' => htmlspecialchars($_POST['item_name']),
            'store' => htmlspecialchars($_POST['store_name']),
            'amount' => preg_replace('/[^0-9.]/', '', $_POST['amount']),
            'method' => $_POST['payment_method'],
            'need' => isset($_POST['is_need']) ? 1 : 0,
            'date' => $_POST['purchase_date'] ?: date('Y-m-d')
        ]);

        $this->redirect("/shopping/{$profile_id}");
    }

    public function delete(int $id): void {
        $this->checkCsrf();
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT s.profile_id FROM shopping_log s 
            JOIN profiles p ON s.profile_id = p.id 
            WHERE s.id = :id AND p.user_id = :uid
        ");
        $stmt->execute(['id' => $id, 'uid' => $_SESSION['user_id']]);
        $record = $stmt->fetch();

        if ($record) {
            $db->prepare("DELETE FROM shopping_log WHERE id = :id")->execute(['id' => $id]);
            $this->redirect("/shopping/" . $record['profile_id']);
        } else {
            $this->redirect('/');
        }
    }
}