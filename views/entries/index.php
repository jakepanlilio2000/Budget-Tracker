<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <div class="top-bar-left">
        <h1>📝 Manage Master Budget Entries</h1>
        <p style="color: var(--text-secondary);">Toggle tracking nodes, update parameters, or clear unneeded allocations.</p>
    </div>
</header>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px; background: var(--bg-elevated); border-bottom: 1px solid var(--border); display: flex; gap: 12px;">
        <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;">TOTAL RUNNING SYSTEM ENTRIES (<?= count($entries) ?>)</span>
    </div>

    <?php 
    $current_cat = null;
    foreach ($entries as $entry): 
        if ($current_cat !== $entry['category_id']): 
            $current_cat = $entry['category_id'];
    ?>
        <div style="padding: 12px 16px; background: var(--bg-primary); font-weight: bold; font-size: 14px; border-bottom: 1px solid var(--border); border-top: 1px solid var(--border); display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 16px;"><?= htmlspecialchars($entry['category_icon'] ?? '🏷️') ?></span>
            <span style="color: var(--accent-blue); font-family: 'DM Sans', sans-serif;"><?= htmlspecialchars($entry['category_name']) ?></span>
        </div>
    <?php endif; ?>
    
        <div class="tx-row <?= $entry['is_active'] ? '' : 'unchecked' ?>" style="display: grid; grid-template-columns: auto 1fr auto auto; gap: 16px; padding: 16px; border-bottom: 1px solid var(--border);" data-id="<?= $entry['id'] ?>">
            <div style="align-self: center;">
                <label class="checkbox-container toggle-active-btn" data-id="<?= $entry['id'] ?>" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin: 0;">
                    <input type="checkbox" <?= (int)$entry['is_active'] === 1 ? 'checked' : '' ?>>
                    <span class="checkmark" style="border-radius:10px;"></span>
                </label>
            </div>
            <div style="display: flex; flex-direction: column; justify-content: center;">
                <span style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($entry['name']) ?></span>
                <span style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;"><?= str_replace('_', ' ', strtoupper($entry['frequency_type'])) ?></span>
            </div>
            <div class="amount <?= $entry['type'] ?>" style="align-self: center; font-weight: 700; font-size: 15px;">
                <?= ($entry['type'] === 'inflow') ? '+' : '-' ?> <?= $profile['currency'] ?> <?= number_format((float)$entry['amount'], 2) ?>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; justify-self: end;">
                <button type="button" class="icon-btn ghost open-edit-modal-btn" data-url="<?= $basePath ?>/entries/edit/<?= $entry['id'] ?>" title="Edit Entry">✏️</button>
                <button type="button" class="delete-entry-btn" data-id="<?= $entry['id'] ?>" data-name="<?= htmlspecialchars($entry['name']) ?>" title="Delete Entry">🗑️</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="edit-entry-modal" class="modal">
    <div class="modal-content drawer" style="max-height: 90vh; overflow-y: auto; background: var(--bg-card); border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
            <h3 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">🔧 Edit Budget Entry</h3>
            <button type="button" class="icon-btn ghost close-modal" aria-label="Close Modal" style="padding: 4px 8px; font-size: 18px;">✕</button>
        </div>
        <div id="edit-modal-form-body">
            <div style="padding: 32px; text-align: center; color: var(--text-secondary);">Assembling entry framework configurations...</div>
        </div>
    </div>
</div>