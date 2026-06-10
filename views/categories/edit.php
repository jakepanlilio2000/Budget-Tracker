<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<form action="<?= $basePath ?>/categories/<?= htmlspecialchars((string)$category['id']) ?>/update" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    
    <div class="form-group">
        <label style="font-weight: 500; font-size: 12px; color: var(--text-secondary);">Category Name *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
    </div>

    <div class="form-group">
        <label style="font-weight: 500; font-size: 12px; color: var(--text-secondary);">Flow Type *</label>
        <select name="type" required>
            <option value="outflow" <?= $category['type'] === 'outflow' ? 'selected' : '' ?>>Outflow (Expense/Loan)</option>
            <option value="inflow" <?= $category['type'] === 'inflow' ? 'selected' : '' ?>>Inflow (Earnings/Deposits)</option>
            <option value="savings" <?= $category['type'] === 'savings' ? 'selected' : '' ?>>Savings (Targets/Allocations)</option>
        </select>
    </div>

    <div class="form-group" style="display: flex; gap: 16px;">
        <div style="flex: 1;">
            <label style="font-weight: 500; font-size: 12px; color: var(--text-secondary);">UI Color Swatch</label>
            <input type="color" name="color" value="<?= htmlspecialchars($category['color']) ?>" style="height: 44px; padding: 2px; cursor: pointer; width: 100%;">
        </div>
        <div style="flex: 1;">
            <label style="font-weight: 500; font-size: 12px; color: var(--text-secondary);">Visual Icon</label>
            
            <div class="icon-picker-trigger" style="display: flex; align-items: center; justify-content: center; height: 44px; background: var(--bg-primary); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; font-size: 20px; color: var(--text-primary);" title="Change Icon">
                <?php if (str_starts_with($category['icon'] ?? '', 'fa-')): ?>
                    <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                <?php else: ?>
                    <i class="fa-solid fa-tag"></i> <?php endif; ?>
            </div>
            <input type="hidden" name="icon" value="<?= htmlspecialchars($category['icon'] ?? 'fa-solid fa-tag') ?>">
        </div>
    </div>

    <div style="margin-top: 32px; display: flex; gap: 12px; border-top: 1px solid var(--border); padding-top: 16px;">
        <button type="submit" class="btn primary" style="flex: 1;"><i class="fa-solid fa-floppy-disk"></i> Save</button>
        <button type="button" class="btn ghost close-modal" style="flex: 1;">Cancel</button>
    </div>
</form>