<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <div class="top-bar-left">
        <h1>Modify Category</h1>
        <p style="color: var(--text-secondary);">Update properties for "<?= htmlspecialchars($category['name']) ?>"</p>
    </div>
</header>

<div class="card" style="max-width: 600px;">
    <form action="<?= $basePath ?>/categories/<?= $category['id'] ?>/update" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label style="font-weight: 500; font-size: 14px; color: var(--text-secondary);">Category Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
        </div>

        <div class="form-group">
            <label style="font-weight: 500; font-size: 14px; color: var(--text-secondary);">Flow Type *</label>
            <select name="type" required>
                <option value="outflow" <?= $category['type'] === 'outflow' ? 'selected' : '' ?>>Outflow (Expense/Loan)</option>
                <option value="inflow" <?= $category['type'] === 'inflow' ? 'selected' : '' ?>>Inflow (Earnings/Deposits)</option>
                <option value="savings" <?= $category['type'] === 'savings' ? 'selected' : '' ?>>Savings (Targets/Allocations)</option>
            </select>
        </div>

        <div class="form-group" style="display: flex; gap: 16px;">
            <div style="flex: 1;">
                <label style="font-weight: 500; font-size: 14px; color: var(--text-secondary);">UI Color Swatch</label>
                <input type="color" name="color" value="<?= htmlspecialchars($category['color']) ?>" style="height: 44px; padding: 2px; cursor: pointer;">
            </div>
            <div style="flex: 1;">
                <label style="font-weight: 500; font-size: 14px; color: var(--text-secondary);">Visual Emoji Icon</label>
                <input type="text" name="icon" value="<?= htmlspecialchars($category['icon']) ?>" required style="text-align: center;">
            </div>
        </div>

        <div style="margin-top: 32px; display: flex; gap: 12px;">
            <button type="submit" class="btn primary">Save Changes</button>
            <a href="<?= $basePath ?>/categories/<?= $profile_id ?>" class="btn ghost">Cancel</a>
        </div>
    </form>
</div>