<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Logger;
use App\Core\Mailer;
use App\Models\User;
use App\Core\Database;
use App\Models\Preference;
class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check())
            $this->redirect('/dashboard');
        $this->view('auth.login');
    }

    public function login(): void
    {
        $this->validateCsrf();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::set('error', 'Invalid email format.');
            $this->redirect('/login');
        }

        if (Auth::attempt($email, $password, $remember)) {
            $prefs = Preference::get(Auth::id());
            $landingPage = $prefs['default_landing_page'] ?? '/dashboard';
            
            $this->redirect($landingPage);
        }

        Session::set('error', 'Invalid credentials or account inactive.');
        Session::set('old_input', ['email' => $email]);
        $this->redirect('/login');
    }

    public function showRegister(): void
    {
        if (Auth::check())
            $this->redirect('/dashboard');
        $this->view('auth.register');
    }

    public function register(): void
    {
        $this->validateCsrf();
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];

        if (empty($data['full_name']) || empty($data['username']) || empty($data['email'])) {
            Session::set('error', 'All fields are required.');
            $this->redirect('/register');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Session::set('error', 'Invalid email format.');
            $this->redirect('/register');
        }
        if (strlen($data['password']) < 8) {
            Session::set('error', 'Password must be at least 8 characters.');
            $this->redirect('/register');
        }
        if ($data['password'] !== $data['password_confirm']) {
            Session::set('error', 'Passwords do not match.');
            $this->redirect('/register');
        }
        if (User::findByEmail($data['email'])) {
            Session::set('error', 'Email is already registered.');
            $this->redirect('/register');
        }

        try {
            User::create($data);
            Session::set('success', 'Registration successful! Please log in.');
            $this->redirect('/login');
        } catch (\Exception $e) {
            Logger::error("Registration failed", ['error' => $e->getMessage()]);
            Session::set('error', 'An error occurred during registration.');
            $this->redirect('/register');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }

    public function showForgot(): void
    {
        $this->view('auth.forgot_password');
    }

    public function forgot(): void
    {
        $this->validateCsrf();
        $email = trim($_POST['email'] ?? '');
        Session::set('success', 'If the email exists, a reset link has been sent.');

        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            $stmt->execute([$email, $token]);

            $link = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/reset-password?token={$token}&email=" . urlencode($email);
            $mailer = new Mailer();
            $mailer->send($email, "Password Reset Request", "<p>Click <a href='{$link}'>here</a> to reset your password. Link expires in 1 hour.</p>");
        }

        $this->redirect('/forgot-password');
    }

    public function showReset(): void
    {
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';
        $this->view('auth.reset_password', ['token' => $token, 'email' => $email]);
    }

    public function reset(): void
    {
        $this->validateCsrf();
        $token = $_POST['token'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (strlen($password) < 8) {
            Session::set('error', 'Password must be at least 8 characters.');
            $this->redirect("/reset-password?token={$token}&email=" . urlencode($email));
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $token]);
        $reset = $stmt->fetch();

        if ($reset) {
            User::updatePassword(User::findByEmail($email)['id'], $password);
            $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            Session::set('success', 'Password reset successful. Please log in.');
            $this->redirect('/login');
        }

        Session::set('error', 'Invalid or expired reset token.');
        $this->redirect('/forgot-password');
    }
}