<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <h1>Edit Profile: <?= htmlspecialchars($profile['name']) ?></h1>
</header>

<div class="card" style="max-width: 600px;">
    <form action="<?= $basePath ?>/profile/<?= $profile['id'] ?>/edit" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label>Profile Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
        </div>
        
        <div class="form-group" style="display: flex; gap: 16px;">
            <div style="flex: 1;">
                <label>Currency Symbol</label>
                <input type="text" name="currency" value="<?= htmlspecialchars($profile['currency']) ?>" required>
            </div>
            <div style="flex: 1;">
                <label>Profile Color</label>
                <input type="color" name="color" value="<?= htmlspecialchars($profile['color']) ?>" style="height: 48px; padding: 4px;">
            </div>
        </div>

        <div class="form-group">
            <label>Base Income per Period</label>
            <input type="text" inputmode="decimal" name="base_income" value="<?= htmlspecialchars($profile['base_income']) ?>">
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

        <div class="form-group">
            <label>Pay Schedule *</label>
            <select name="pay_schedule" required>
                <option value="semi_monthly" <?= $profile['pay_schedule'] === 'semi_monthly' ? 'selected' : '' ?>>Semi-Monthly (e.g., 15th & 30th)</option>
                <option value="monthly" <?= $profile['pay_schedule'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="weekly" <?= $profile['pay_schedule'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="bi_weekly" <?= $profile['pay_schedule'] === 'bi_weekly' ? 'selected' : '' ?>>Bi-Weekly</option>
            </select>
        </div>

        <div class="form-group" style="display: flex; gap: 16px;">
            <div style="flex: 1;">
                <label>Pay Day 1</label>
                <input type="number" name="pay_day_1" value="<?= htmlspecialchars($profile['pay_day_1']) ?>" min="1" max="31">
            </div>
            <div style="flex: 1;">
                <label>Pay Day 2 (If Semi-Monthly)</label>
                <input type="number" name="pay_day_2" value="<?= htmlspecialchars($profile['pay_day_2']) ?>" min="1" max="31">
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; justify-content: space-between;">
            <div>
                <button type="submit" class="btn primary">Update Profile</button>
                <a href="<?= $basePath ?>/dashboard/<?= $profile['id'] ?>" class="btn ghost">Cancel</a>
            </div>
            <button type="button" class="btn danger" onclick="confirmAction('Delete Profile', 'Are you sure? This deletes ALL transactions.', () => { window.location.href='<?= $basePath ?>/profile/<?= $profile['id'] ?>/delete'; })">Delete</button>
        </div>
    </form>
</div>