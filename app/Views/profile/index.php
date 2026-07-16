<?php
declare(strict_types=1);
$pageTitle = 'Profile Settings';
ob_start();
?>
<div class="page-header">
    <h1>Profile Settings</h1>
</div>

<div class="grid grid-2">
    <!-- Personal Information -->
    <div class="card glass">
        <h3>Personal Information</h3>
        <form method="POST" action="<?= url('/profile/update') ?>" class="form-stack">
            <?= \App\Core\CSRF::field() ?>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= e($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($user['email']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card glass">
        <h3>Change Password</h3>
        <form method="POST" action="<?= url('/profile/change-password') ?>" class="form-stack">
            <?= \App\Core\CSRF::field() ?>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" minlength="8" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>