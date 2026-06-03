<?php
namespace controllers;
use core\Controller;
use models\User;

class AuthController extends Controller {
    
    public function login(): void {
        if (isset($_SESSION['user_id'])) $this->redirect('/');
        $this->view('auth/login');
    }

    public function authenticate(): void {
        $this->checkCsrf();
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $this->redirect('/');
        } else {
            $_SESSION['auth_error'] = "Invalid email or password.";
            $this->redirect('/login');
        }
    }

    public function register(): void {
        if (isset($_SESSION['user_id'])) $this->redirect('/');
        $this->view('auth/register');
    }

    public function store(): void {
        $this->checkCsrf();
        $name = htmlspecialchars($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password']; 

        if ($password !== $confirm) {
            $_SESSION['auth_error'] = "Your passwords do not match. Please try again.";
            $this->redirect('/register');
            return;
        }

        $userModel = new User();
        
        if ($userModel->findByEmail($email)) {
            $_SESSION['auth_error'] = "Email is already registered.";
            $this->redirect('/register');
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        
        $this->redirect('/');
    }

    public function logout(): void {
        session_unset();
        session_destroy();
        $this->redirect('/login');
    }
}