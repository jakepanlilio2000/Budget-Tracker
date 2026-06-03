<?php
namespace controllers;
use core\Controller;
use models\SavingsGoal;

class VaultController extends Controller {
    public function index(int $profile_id): void {
        $goalModel = new SavingsGoal();
        $goals = $goalModel->findAll(['profile_id' => $profile_id], 'created_at ASC');
        $total_vault = array_sum(array_column($goals, 'current_amount'));

        $this->view('vault/index', [
            'profile_id' => $profile_id,
            'goals' => $goals,
            'total_vault' => $total_vault
        ]);
    }

    public function store(int $profile_id): void {
        $this->checkCsrf();
        $goalModel = new SavingsGoal();
        
        $goalModel->create([
            'profile_id' => $profile_id,
            'name' => htmlspecialchars($_POST['name']),
            'target_amount' => preg_replace('/[^0-9.]/', '', $_POST['target_amount']),
            'color' => htmlspecialchars($_POST['color']),
            'icon' => htmlspecialchars($_POST['icon']),
            'target_date' => !empty($_POST['target_date']) ? $_POST['target_date'] : null
        ]);

        $this->redirect("/vault/{$profile_id}");
    }

    public function addFunds(int $id): void {
        $this->checkCsrf();
        $goalModel = new SavingsGoal();
        $amount = (float)preg_replace('/[^0-9.-]/', '', $_POST['amount']); // Allow negatives for withdrawals
        
        $success = $goalModel->addFunds($id, $amount);
        $this->json(['success' => $success]);
    }

    public function delete(int $id): void {
        $this->checkCsrf();
        $goalModel = new SavingsGoal();
        $success = $goalModel->delete($id);
        $this->json(['success' => $success]);
    }
}