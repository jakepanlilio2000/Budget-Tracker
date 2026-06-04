<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

<div class="form-group">
    <label>Entry Name *</label>
    <input type="text" name="name" value="<?= htmlspecialchars($entry['name'] ?? '') ?>" required>
</div>

<div class="form-group" style="display: flex; gap: 16px;">
    <div style="flex: 1;">
        <label>Amount *</label>
        <input type="text" inputmode="decimal" name="amount" value="<?= htmlspecialchars($entry['amount'] ?? '') ?>" required>
    </div>
    <div style="flex: 1;">
        <label>Type *</label>
        <select name="type" required>
            <option value="outflow" <?= ($entry['type'] ?? '') === 'outflow' ? 'selected' : '' ?>>Outflow</option>
            <option value="inflow" <?= ($entry['type'] ?? '') === 'inflow' ? 'selected' : '' ?>>Inflow</option>
        </select>
    </div>
</div>

<div class="form-group">
    <label>Category *</label>
    <select name="category_id" required>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($entry['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Notes</label>
    <textarea name="notes" rows="2"><?= htmlspecialchars($entry['notes'] ?? '') ?></textarea>
</div>

<hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">
<h3>Frequency Rules</h3>

<div class="form-group">
    <label>Frequency Type *</label>
    <select name="frequency_type" id="frequency_type" required>
        <option value="semi_monthly" <?= ($entry['frequency_type'] ?? '') === 'semi_monthly' ? 'selected' : '' ?>>Semi-Monthly (15th & 30th)</option>
        <option value="monthly" <?= ($entry['frequency_type'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
        <option value="weekly" <?= ($entry['frequency_type'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
        <option value="custom_months" <?= ($entry['frequency_type'] ?? '') === 'custom_months' ? 'selected' : '' ?>>Installment / Temporary</option>
        <option value="one_time" <?= ($entry['frequency_type'] ?? '') === 'one_time' ? 'selected' : '' ?>>One-Time</option>
    </select>
</div>

<div id="sm-fields" class="freq-subfield form-group" style="display: <?= (!isset($entry['frequency_type']) || $entry['frequency_type'] === 'semi_monthly') ? 'block' : 'none' ?>; background: var(--bg-elevated); padding: 16px; border-radius: 8px;">
    <label class="checkbox-container" style="display: inline-block; margin-right: 16px; width: auto;">
        <input type="checkbox" name="sm_first" value="1" <?= isset($entry['is_first_half']) && $entry['is_first_half'] ? 'checked' : 'checked' ?>>
        <span class="checkmark" style="position:relative; display:inline-block; vertical-align:middle; margin-right:8px;"></span> 1st Half (e.g. 15th)
    </label>
    <label class="checkbox-container" style="display: inline-block; width: auto;">
        <input type="checkbox" name="sm_second" value="1" <?= isset($entry['is_first_half']) && !$entry['is_first_half'] ? 'checked' : 'checked' ?>>
        <span class="checkmark" style="position:relative; display:inline-block; vertical-align:middle; margin-right:8px;"></span> 2nd Half (e.g. 30th)
    </label>
</div>

<div id="installment-fields" class="freq-subfield form-group" style="display: <?= ($entry['frequency_type'] ?? '') === 'custom_months' ? 'block' : 'none' ?>; background: var(--bg-elevated); padding: 16px; border-radius: 8px;">
    <div style="display: flex; gap: 16px;">
        <div style="flex: 1;">
            <label>Total Months</label>
            <input type="number" name="total_months" value="<?= $entry['total_months'] ?? 3 ?>">
        </div>
        <div style="flex: 1;">
            <label>Specific Day of Month</label>
            <input type="number" name="specific_day" min="1" max="31" value="<?= $entry['specific_day'] ?? 15 ?>">
        </div>
    </div>
</div>

<div class="form-group">
    <label class="checkbox-container" style="width: auto;">
        <input type="checkbox" name="is_active" value="1" <?= (!isset($entry) || $entry['is_active']) ? 'checked' : '' ?>>
        <span class="checkmark" style="position:relative; display:inline-block; vertical-align:middle; margin-right:8px;"></span> Is Active
    </label>
</div>

<div style="margin-top: 24px; display: flex; gap: 12px;">
    <button type="submit" class="btn primary">Save Entry Changes</button>
    <button type="button" class="btn ghost close-modal">Cancel</button>
</div>