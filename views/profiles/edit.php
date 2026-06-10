<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <h1><i class="fa-solid fa-user-pen" style="color: var(--accent-blue); margin-right: 8px;"></i> Edit Profile: <?= htmlspecialchars($profile['name'] ?? '') ?></h1>
</header>

<div class="card" style="max-width: 600px;">
    <form action="<?= $basePath ?>/profile/<?= htmlspecialchars((string)$profile['id']) ?>/edit" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        
        <div class="form-group">
            <label>Profile Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>
        </div>
        
        <div class="form-group" style="display: flex; gap: 16px;">
            <div style="flex: 1;">
                <label>Currency Symbol</label>
                <input type="text" name="currency" value="<?= htmlspecialchars($profile['currency'] ?? '') ?>" required>
            </div>
            <div style="flex: 1;">
                <label>Profile Color</label>
                <input type="color" name="color" value="<?= htmlspecialchars($profile['color'] ?? '') ?>" style="height: 48px; padding: 4px;">
            </div>
        </div>

        <div class="form-group">
            <label>Base Income per Period</label>
            <input type="text" inputmode="decimal" name="base_income" value="<?= htmlspecialchars($profile['base_income'] ?? '0.00') ?>">
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

        <div class="form-group">
            <label>Pay Schedule *</label>
            <select name="pay_schedule" required>
                <option value="semi_monthly" <?= ($profile['pay_schedule'] ?? '') === 'semi_monthly' ? 'selected' : '' ?>>Semi-Monthly (e.g., 15th & 30th)</option>
                <option value="monthly" <?= ($profile['pay_schedule'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="weekly" <?= ($profile['pay_schedule'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="bi_weekly" <?= ($profile['pay_schedule'] ?? '') === 'bi_weekly' ? 'selected' : '' ?>>Bi-Weekly</option>
            </select>
        </div>

        <div class="form-group" style="display: flex; gap: 16px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 120px;">
                <label>Pay Day 1</label>
                <input type="number" name="pay_day_1" value="<?= htmlspecialchars((string)($profile['pay_day_1'] ?? 15)) ?>" min="1" max="31">
            </div>
            <div style="flex: 1; min-width: 120px;">
                <label>Pay Day 2 (If Semi-Monthly)</label>
                <input type="number" name="pay_day_2" value="<?= htmlspecialchars((string)($profile['pay_day_2'] ?? 30)) ?>" min="1" max="31">
            </div>
            <div style="flex: 1; min-width: 120px;">
                <label>Weekly Pay Day</label>
                <select name="weekly_day">
                    <option value="1" <?= ($profile['weekly_day'] ?? 5) == 1 ? 'selected' : '' ?>>Monday</option>
                    <option value="2" <?= ($profile['weekly_day'] ?? 5) == 2 ? 'selected' : '' ?>>Tuesday</option>
                    <option value="3" <?= ($profile['weekly_day'] ?? 5) == 3 ? 'selected' : '' ?>>Wednesday</option>
                    <option value="4" <?= ($profile['weekly_day'] ?? 5) == 4 ? 'selected' : '' ?>>Thursday</option>
                    <option value="5" <?= ($profile['weekly_day'] ?? 5) == 5 ? 'selected' : '' ?>>Friday</option>
                    <option value="6" <?= ($profile['weekly_day'] ?? 5) == 6 ? 'selected' : '' ?>>Saturday</option>
                    <option value="7" <?= ($profile['weekly_day'] ?? 5) == 7 ? 'selected' : '' ?>>Sunday</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; justify-content: space-between;">
            <div>
                <button type="submit" class="btn primary">Update Profile</button>
                <a href="<?= $basePath ?>/dashboard/<?= htmlspecialchars((string)$profile['id']) ?>" class="btn ghost">Cancel</a>
            </div>
            <button type="button" class="btn danger" onclick="confirmAction('Delete Profile', 'Are you sure? This deletes ALL transactions.', () => { window.location.href='<?= $basePath ?>/profile/<?= htmlspecialchars((string)$profile['id']) ?>/delete'; })"><i class="fa-solid fa-trash-can"></i> Delete</button>
        </div>
    </form>
</div>