<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<div style="background: var(--bg-card); padding: 40px; border-radius: 16px; border: 1px solid var(--border); width: 100%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <div style="text-align: center; margin-bottom: 32px;">
        <h1 style="font-size: 28px; margin: 0; color: var(--text-primary);">Budget<span style="color: var(--accent-blue);">Suite</span></h1>
        <p style="color: var(--text-secondary); font-size: 14px; margin-top: 8px;">Initialize your secure financial node.</p>
    </div>

    <?php if (isset($_SESSION['auth_error'])): ?>
        <div style="background: rgba(248, 81, 73, 0.1); color: var(--accent-red); padding: 12px; border-radius: 8px; border: 1px solid var(--accent-red); margin-bottom: 24px; font-size: 14px; text-align: center;">
            <?= $_SESSION['auth_error']; unset($_SESSION['auth_error']); ?>
        </div>
    <?php endif; ?>

    <form action="<?= $basePath ?>/register" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" required placeholder="Jake Panlilio" style="padding: 12px; font-size: 16px;">
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="name@example.com" style="padding: 12px; font-size: 16px;">
        </div>
        
<div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••" style="padding: 12px; font-size: 16px;">
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required placeholder="••••••••" style="padding: 12px; font-size: 16px;">
        </div>

        <button type="submit" class="btn primary" style="width: 100%; padding: 12px; font-size: 16px; margin-top: 16px;">Register Account</button>

    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--text-secondary);">
        Already have an account? <a href="<?= $basePath ?>/login" style="color: var(--accent-blue); text-decoration: none;">Sign in</a>
    </div>
</div>