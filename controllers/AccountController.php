<?php
namespace controllers;
use core\Controller;
use models\User;

class AccountController extends Controller {
    
    public function index(): void {
        $userModel = new User();
        $user = $userModel->findById($_SESSION['user_id']);
        
        if (!$user) $this->redirect('/login');
        
        $this->view('account/index', ['user' => $user]);
    }

    public function updateProfile(): void {
        $this->checkCsrf();
        $name = htmlspecialchars($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $userId = $_SESSION['user_id'];

        $userModel = new User();
        $existing = $userModel->findByEmail($email);
        
        if ($existing && $existing['id'] !== $userId) {
            $_SESSION['account_error'] = "That email address is already in use by another account.";
            $this->redirect('/account');
            return;
        }

        $userModel->updateProfile($userId, $name, $email);
        
        $_SESSION['user_name'] = $name; 
        
        $_SESSION['account_success'] = "Profile details updated successfully.";
        $this->redirect('/account');
    }

    public function updatePassword(): void {
        $this->checkCsrf();
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        $userId = $_SESSION['user_id'];

        if ($new !== $confirm) {
            $_SESSION['account_error'] = "Your new passwords do not match.";
            $this->redirect('/account');
            return;
        }

        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!password_verify($current, $user['password'])) {
            $_SESSION['account_error'] = "Incorrect current password.";
            $this->redirect('/account');
            return;
        }

        $userModel->updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));
        
        $_SESSION['account_success'] = "Password updated securely.";
        $this->redirect('/account');
    }
}