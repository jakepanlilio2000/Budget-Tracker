<?php
namespace controllers;
use core\Controller;
use models\Profile;

class ProfileController extends Controller {
    public function index(): void {
        $profileModel = new Profile();
        $profiles = $profileModel->findAll([], 'updated_at DESC');
        $this->view('profiles/index', ['profiles' => $profiles]);
    }

    public function create(): void {
        $this->view('profiles/create');
    }

    public function store(): void {
        $this->checkCsrf();
        $profileModel = new Profile();
        
        $data = [
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