<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <div class="top-bar-left">
        <h1>Manage Categories</h1>
        <p style="color: var(--text-secondary);">Drag and drop rows to reorder. Changes apply live to your active tables.</p>
    </div>
</header>

<div class="card" style="padding: 0; overflow: hidden; max-width: 800px;">
    <ul id="category-list" style="list-style: none; margin: 0; padding: 0;">
        <?php foreach ($categories as $cat): ?>
            <li class="cat-row" data-id="<?= $cat['id'] ?>" draggable="true" style="display: flex; align-items: center; gap: 16px; padding: 16px; border-bottom: 1px solid var(--border); background: var(--bg-primary); cursor: grab;">
                <span class="drag-handle" style="color: var(--text-muted); padding: 4px;">☰</span>
                <div style="width: 16px; height: 16px; border-radius: 50%; background: <?= htmlspecialchars($cat['color']) ?>;"></div>
                <span style="font-size: 20px;"><?= htmlspecialchars($cat['icon']) ?></span>
                <span style="flex: 1; font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($cat['name']) ?></span>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button type="button" class="icon-btn ghost open-cat-edit-modal-btn" data-url="<?= $basePath ?>/categories/edit/<?= $cat['id'] ?>" title="Edit Category">✏️</button>
                    <button type="button" class="delete-cat-btn" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>">🗑️</button>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <div style="padding: 16px; background: var(--bg-elevated);">
        <form action="<?= $basePath ?>/categories/<?= $profile_id ?>/store" method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="text" name="name" placeholder="Category Title" required style="flex: 1; min-width: 180px; margin: 0;">
            <select name="type" required style="width: auto; margin: 0;">
                <option value="outflow">Outflow</option>
                <option value="inflow">Inflow</option>
                <option value="savings">Savings</option>
            </select>
            <input type="color" name="color" value="#58a6ff" style="height: 42px; width: 50px; padding: 2px; margin: 0; cursor: pointer;">
            
            <input type="text" name="icon" class="emoji-picker-trigger" value="🏷️" required readonly style="width: 60px; margin: 0; text-align: center; cursor: pointer;" title="Select Icon">
            <button type="submit" class="btn primary" style="white-space: nowrap;">➕ Add</button>
        </form>
    </div>
</div>

<div id="edit-category-modal" class="modal">
    <div class="modal-content drawer" style="max-height: 90vh; overflow-y: auto; background: var(--bg-card); border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
            <h3 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">🔧 Modify Category Properties</h3>
            <button type="button" class="icon-btn ghost close-modal" style="padding: 4px 8px; font-size: 18px;">✕</button>
        </div>
        <div id="edit-category-modal-body">
            </div>
    </div>
</div>

<div id="emoji-picker-modal" class="modal multi-layer-modal">
    <div class="modal-content drawer" style="background: var(--bg-elevated); border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.7);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin:0; color: var(--text-primary);">Select Category Emoji</h3>
            <button type="button" class="icon-btn ghost close-modal" style="font-size: 18px;">✕</button>
        </div>
        <div class="emoji-grid-layout">
            <?php 
            $emojis = [
                '💼', '🪙', '🚀', '📈', '🏢', '🤝', '🎁', '🏦',
                '🏠', '💸', '🛒', '🍔', '🚗', '🔌', '📶', '🛡️',
                '🎬', '🏥', '🎓', '✈️', '🎮', '🏋️', '🎯', '💎'
            ];
            foreach($emojis as $em): ?>
                <div class="emoji-option"><?= $em ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Safe dynamic bootstrapper execution re-binds reorder metrics cleanly after SPA switches
    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();
</script>