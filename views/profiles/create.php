<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <h1>Create New Profile</h1>
</header>

<div class="card" style="max-width: 600px;">
    <form action="<?= $basePath ?>/profile/create" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label>Profile Name *</label>
            <input type="text" name="name" required placeholder="e.g., Personal 2026">
        </div>
        
        <div class="form-group" style="display: flex; gap: 16px;">
            <div style="flex: 1;">
                <label>Currency Symbol</label>
                <input type="text" name="currency" value="₱" required>
            </div>
            <div style="flex: 1;">
                <label>Profile Color</label>
                <input type="color" name="color" value="#4F7BF7" style="height: 48px; padding: 4px;">
            </div>
        </div>

        <div class="form-group">
            <label>Base Income per Period</label>
            <input type="text" inputmode="decimal" name="base_income" value="0.00">
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

        <div class="form-group">
            <label>Pay Schedule *</label>
            <select name="pay_schedule" required>
                <option value="semi_monthly">Semi-Monthly (e.g., 15th & 30th)</option>
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
                <option value="bi_weekly">Bi-Weekly</option>
            </select>
        </div>

        <div class="form-group" style="display: flex; gap: 16px;">
            <div style="flex: 1;">
                <label>Pay Day 1</label>
                <input type="number" name="pay_day_1" value="15" min="1" max="31">
            </div>
            <div style="flex: 1;">
                <label>Pay Day 2 (If Semi-Monthly)</label>
                <input type="number" name="pay_day_2" value="30" min="1" max="31">
            </div>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3"></textarea>
        </div>

        <div style="margin-top: 24px;">
            <button type="submit" class="btn primary">Create Profile</button>
            <a href="<?= $basePath ?>/" class="btn ghost">Cancel</a>
        </div>
    </form>
</div>