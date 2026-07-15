<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\User;

class ProfileController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }

    public function index(): void
    {
        $user = Auth::user();
        $this->view('profile.index', ['user' => $user]);
    }

    public function update(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? '')
        ];

        if (empty($data['full_name']) || empty($data['username']) || empty($data['email'])) {
            Session::set('error', 'All fields are required.');
            $this->redirect('/profile');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Session::set('error', 'Invalid email format.');
            $this->redirect('/profile');
        }

        User::updateProfile($userId, $data);
        Session::set('success', 'Profile updated successfully.');
        $this->redirect('/profile');
    }

    public function changePassword(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = Auth::user();
        if (!password_verify($current, $user['password_hash'])) {
            Session::set('error', 'Current password is incorrect.');
            $this->redirect('/profile');
        }

        if (strlen($new) < 8) {
            Session::set('error', 'New password must be at least 8 characters.');
            $this->redirect('/profile');
        }

        if ($new !== $confirm) {
            Session::set('error', 'New passwords do not match.');
            $this->redirect('/profile');
        }

        User::updatePassword($userId, $new);
        Session::set('success', 'Password changed successfully.');
        $this->redirect('/profile');
    }
}