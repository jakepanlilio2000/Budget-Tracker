<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <h1>Manage Entries</h1>
    <!-- <a href="<?= $basePath ?>/entries/<?= $profile_id ?>/create" class="btn primary">➕ New Entry</a> -->
</header>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px; background: var(--bg-elevated); border-bottom: 1px solid var(--border); display: flex; gap: 12px;">
        <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;">ALL ENTRIES (<?= count($entries) ?>)</span>
    </div>

    <?php 
    $current_cat = null;
    foreach ($entries as $entry): 
        if ($current_cat !== $entry['category_id']): 
            $current_cat = $entry['category_id'];
    ?>
        <div style="padding: 12px 16px; background: var(--bg-primary); font-weight: bold; font-size: 14px; border-bottom: 1px solid var(--border); border-top: 1px solid var(--border);">
            <?= htmlspecialchars($entry['category_name']) ?>
        </div>
    <?php endif; ?>
    
        <div class="tx-row <?= $entry['is_active'] ? '' : 'unchecked' ?>" style="display: grid; grid-template-columns: auto 1fr auto auto; gap: 16px; padding: 16px; border-bottom: 1px solid var(--border);">
            <div>
                <label class="checkbox-container toggle-active-btn"
                    data-id="<?= $entry['id'] ?>"
                    style="display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                    <input type="checkbox" <?= (int)$entry['is_active'] === 1 ? 'checked' : '' ?>>
                    <span class="checkmark" style="border-radius:10px;"></span>
                </label>
            </div>
            <div style="display: flex; flex-direction: column;">
                <span style="font-weight: 500;"><?= htmlspecialchars($entry['name']) ?></span>
                <span style="font-size: 12px; color: var(--text-secondary);"><?= str_replace('_', ' ', strtoupper($entry['frequency_type'])) ?></span>
            </div>
            <div class="amount <?= $entry['type'] ?>">
                <?= number_format((float)$entry['amount'], 2) ?>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="<?= $basePath ?>/entries/edit/<?= $entry['id'] ?>" class="icon-btn ghost">✏️</a>
                <button class="icon-btn ghost delete-entry-btn" data-id="<?= $entry['id'] ?>" data-name="<?= htmlspecialchars($entry['name']) ?>" style="cursor:pointer;">🗑️</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.toggle-active-btn input').forEach(toggle => {
    toggle.addEventListener('change', async (e) => {
        const id = e.target.closest('label').dataset.id;
        const formData = new FormData();
        formData.append('csrf_token', CSRF_TOKEN);
        
        try {
            await fetch(`<?= $basePath ?>/entries/${id}/toggle`, { method: 'POST', body: formData });
            e.target.closest('.tx-row').classList.toggle('unchecked', !e.target.checked);
        } catch (err) {
            e.target.checked = !e.target.checked;
        }
    });
});
document.querySelectorAll('.delete-entry-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const id = e.target.dataset.id;
        const name = e.target.dataset.name;
        
        confirmAction('Delete Entry', `Are you sure you want to permanently delete "${name}"? Past transactions will remain intact.`, async () => {
            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);
            
            try {
                const res = await fetch(`<?= $basePath ?>/entries/${id}/delete`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    e.target.closest('.tx-row').remove();
                    showToast('Entry deleted', 'success');
                }
            } catch (err) {
                showToast('Failed to delete entry', 'error');
            }
        });
    });
});
</script>