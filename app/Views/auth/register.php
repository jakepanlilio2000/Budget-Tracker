<?php
declare(strict_types=1);
use App\Core\Session;
use App\Core\CSRF;

$this->view('layouts.auth', [
    'content' => '
    <h2>Create Account</h2>
    <p class="auth-subtitle">Start managing your expenses today</p>
    
    ' . (Session::get('error') ? '<div class="alert alert-danger">' . e(Session::get('error')) . '</div>' : '') . '

    <form method="POST" action="' . url('/register') . '" class="auth-form">
        ' . CSRF::field() . '
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="' . e(old('full_name')) . '" required>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="' . e(old('username')) . '" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="' . e(old('email')) . '" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="8" required>
        </div>
        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>
    <p class="auth-footer">Already have an account? <a href="' . url('/login') . '" class="link">Sign in here</a></p>
'
]);
?>