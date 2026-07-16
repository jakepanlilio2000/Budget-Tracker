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
        
        <!-- Modern Toggle Switch for "Remember Me" -->
        <div class="form-group form-row" style="align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <label class="toggle-label" style="font-size: 0.9rem; margin: 0;">
                <label class="toggle-switch" style="width: 36px; height: 20px;">
                    <input type="checkbox" name="remember" value="1">
                    <span class="toggle-slider" style="border-radius: 20px;"></span>
                    <style>
                        /* Inline override to make the login toggle slightly smaller and perfectly aligned */
                        .toggle-switch[style*="36px"] .toggle-slider:before {
                            height: 14px;
                            width: 14px;
                            left: 2px;
                            bottom: 2px;
                        }
                        .toggle-switch[style*="36px"] input:checked + .toggle-slider:before {
                            transform: translateX(16px);
                        }
                    </style>
                </label>
                <span>Remember me</span>
            </label>
            <a href="' . url('/forgot-password') . '" class="link" style="font-size: 0.9rem; margin-left: auto;">Forgot Password?</a>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>
    <p class="auth-footer">Don\'t have an account? <a href="' . url('/register') . '" class="link">Register here</a></p>
'
]);
?>