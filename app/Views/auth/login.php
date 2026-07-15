<?php
declare(strict_types=1);
use App\Core\Session;
use App\Core\CSRF;

$this->view('layouts.auth', [
    'content' => '
    <h2>Welcome Back</h2>
    <p class="auth-subtitle">Sign in to your account</p>
    
    ' . (Session::get('error') ? '<div class="alert alert-danger">' . e(Session::get('error')) . '</div>' : '') . '
    ' . (Session::get('success') ? '<div class="alert alert-success">' . e(Session::get('success')) . '</div>' : '') . '

    <form method="POST" action="' . url('/login') . '" class="auth-form">
        ' . CSRF::field() . '
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="' . e(old('email')) . '" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group form-row">
            <label class="checkbox-label">
                <input type="checkbox" name="remember"> Remember me
            </label>
            <a href="' . url('/forgot-password') . '" class="link">Forgot Password?</a>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>
    <p class="auth-footer">Don\'t have an account? <a href="' . url('/register') . '" class="link">Register here</a></p>
'
]);
?>