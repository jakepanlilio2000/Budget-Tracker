<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-user-gear" style="color: var(--accent-blue); margin-right: 8px;"></i> Account Management</h1>
        <p style="color: var(--text-secondary);">Update your personal details and security credentials.</p>
    </div>
</header>

<?php if (isset($_SESSION['account_success'])): ?>
    <div style="background: rgba(63, 185, 80, 0.1); color: var(--accent-green); padding: 16px; border-radius: 8px; border: 1px solid var(--accent-green); margin-bottom: 24px;">
        <i class="fa-solid fa-circle-check" style="margin-right: 4px;"></i> <?= htmlspecialchars($_SESSION['account_success']); unset($_SESSION['account_success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['account_error'])): ?>
    <div style="background: rgba(248, 81, 73, 0.1); color: var(--accent-red); padding: 16px; border-radius: 8px; border: 1px solid var(--accent-red); margin-bottom: 24px;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 4px;"></i> <?= htmlspecialchars($_SESSION['account_error']); unset($_SESSION['account_error']); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; align-items: start;">
    
    <div class="card">
        <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-id-badge" style="color: var(--text-muted); margin-right: 8px;"></i> Profile Information</h3>
        <form action="<?= $basePath ?>/account/profile" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>

            <button type="submit" class="btn primary" style="width: 100%;"><i class="fa-solid fa-floppy-disk" style="margin-right: 6px;"></i> Save Changes</button>
        </form>
    </div>

    <div class="card" style="border: 1px solid var(--border);">
        <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-shield-halved" style="color: var(--text-muted); margin-right: 8px;"></i> Change Password</h3>
        <form action="<?= $basePath ?>/account/password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required placeholder="••••••••">
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 16px 0;">

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required placeholder="••••••••">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn ghost" style="width: 100%; border: 1px solid var(--border);"><i class="fa-solid fa-key" style="margin-right: 6px;"></i> Update Password</button>
        </form>
    </div>

</div>