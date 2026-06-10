<?php 
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); 
// FAILSAFE: Ensures the profile ID is always captured so the router doesn't crash on form submit
$pid = $profile['id'] ?? $profile_id ?? 0; 
?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-tags" style="color: var(--accent-blue); margin-right: 8px;"></i> Manage Categories</h1>
        <p style="color: var(--text-secondary);">Drag and drop rows to reorder. Changes apply live to your active tables.</p>
    </div>
</header>

<div class="card" style="padding: 0; overflow: hidden; max-width: 900px;">
    <ul id="category-list" style="list-style: none; margin: 0; padding: 0;">
        <?php foreach ($categories as $cat): ?>
            <li class="cat-row" data-id="<?= htmlspecialchars((string)$cat['id']) ?>" draggable="true" style="display: flex; align-items: center; gap: 16px; padding: 16px; border-bottom: 1px solid var(--border); background: var(--bg-primary); cursor: grab; transition: background 0.2s;">
                <span class="drag-handle" style="color: var(--text-muted); padding: 4px; font-size: 18px;" title="Drag to reorder">
                    <i class="fa-solid fa-grip-lines"></i>
                </span>
                
                <div style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: <?= htmlspecialchars($cat['color']) ?>20; border: 1px solid <?= htmlspecialchars($cat['color']) ?>50;">
                    <span style="font-size: 20px; color: <?= htmlspecialchars($cat['color']) ?>;">
                        <?php if (str_starts_with($cat['icon'] ?? '', 'fa-')): ?>
                            <i class="<?= htmlspecialchars($cat['icon']) ?>"></i>
                        <?php else: ?>
                            <?= htmlspecialchars($cat['icon'] ?? '🏷️') ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div style="flex: 1; display: flex; flex-direction: column;">
                    <span style="font-weight: bold; color: var(--text-primary); font-size: 16px;"><?= htmlspecialchars($cat['name']) ?></span>
                    <span style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($cat['type']) ?></span>
                </div>
                
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button type="button" class="btn ghost open-cat-edit-modal-btn" data-url="<?= $basePath ?>/categories/edit/<?= htmlspecialchars((string)$cat['id']) ?>" style="border: 1px solid var(--border); padding: 8px 12px; font-size: 13px; color: var(--accent-blue);">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </button>
                    <button type="button" class="btn ghost delete-cat-btn" data-id="<?= htmlspecialchars((string)$cat['id']) ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" style="border: 1px solid var(--border); padding: 8px 12px; font-size: 13px; color: var(--accent-red);">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <div style="padding: 20px; background: var(--bg-elevated); border-top: 1px solid var(--border);">
        <h4 style="margin-top: 0; margin-bottom: 12px; color: var(--text-primary);"><i class="fa-solid fa-plus" style="margin-right: 8px;"></i> Quick Add Category</h4>
        <form action="<?= $basePath ?>/categories/<?= $pid ?>/store" method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="text" name="name" placeholder="Category Title" required style="flex: 1; min-width: 180px; margin: 0;">
            <select name="type" required style="width: auto; margin: 0;">
                <option value="outflow">Outflow</option>
                <option value="inflow">Inflow</option>
                <option value="savings">Savings</option>
            </select>
            <input type="color" name="color" value="#58a6ff" style="height: 42px; width: 50px; padding: 2px; margin: 0; cursor: pointer;" title="Theme Color">
            
            <div class="icon-picker-trigger" style="display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; background: var(--bg-primary); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; font-size: 18px; color: var(--text-primary);" title="Select Icon">
                <i class="fa-solid fa-tag"></i>
            </div>
            <input type="hidden" name="icon" value="fa-solid fa-tag">
            
            <button type="submit" class="btn primary" style="white-space: nowrap;">Add Category</button>
        </form>
    </div>
</div>

<div id="edit-category-modal" class="modal">
    <div class="modal-content drawer" style="max-height: 90vh; overflow-y: auto; background: var(--bg-card); border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
            <h3 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-wrench" style="color: var(--accent-blue);"></i> Modify Category</h3>
            <button type="button" class="icon-btn ghost close-modal" style="padding: 4px 8px; font-size: 18px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="edit-category-modal-body">
            <div style="padding: 32px; text-align: center; color: var(--text-secondary);"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
        </div>
    </div>
</div>

<div id="icon-picker-modal" class="modal multi-layer-modal">
    <div class="modal-content drawer" style="background: var(--bg-elevated); border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.7);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin:0; color: var(--text-primary);"><i class="fa-solid fa-icons" style="margin-right: 8px;"></i> Select Category Icon</h3>
            <button type="button" class="icon-btn ghost close-modal-icon" style="font-size: 18px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="emoji-grid-layout" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap: 8px;">
            <?php 
            $icons = [
                'fa-solid fa-briefcase', 'fa-solid fa-coins', 'fa-solid fa-rocket', 'fa-solid fa-chart-line', 
                'fa-solid fa-building', 'fa-solid fa-handshake', 'fa-solid fa-gift', 'fa-solid fa-building-columns',
                'fa-solid fa-house', 'fa-solid fa-money-bill-wave', 'fa-solid fa-cart-shopping', 'fa-solid fa-burger', 
                'fa-solid fa-car', 'fa-solid fa-plug', 'fa-solid fa-wifi', 'fa-solid fa-shield-halved',
                'fa-solid fa-clapperboard', 'fa-solid fa-hospital', 'fa-solid fa-graduation-cap', 'fa-solid fa-plane', 
                'fa-solid fa-gamepad', 'fa-solid fa-dumbbell', 'fa-solid fa-bullseye', 'fa-solid fa-gem',
                'fa-solid fa-tag', 'fa-solid fa-credit-card', 'fa-solid fa-mobile-screen', 'fa-solid fa-bolt',
                'fa-solid fa-droplet', 'fa-solid fa-umbrella', 'fa-solid fa-shirt', 'fa-solid fa-paw'
            ];
            foreach($icons as $iconClass): ?>
                <div class="icon-option" data-icon="<?= $iconClass ?>" style="font-size: 20px; text-align: center; cursor: pointer; padding: 10px; border-radius: 4px; color: var(--text-primary);" onmouseover="this.style.background='var(--bg-primary)'" onmouseout="this.style.background='transparent'">
                    <i class="<?= $iconClass ?>"></i>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Safe dynamic bootstrapper execution
    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();

    // Custom Icon Picker Logic
    let activeIconInput = null;
    let activeIconVisual = null;

    document.addEventListener('click', function(e) {
        // Open Modal
        const trigger = e.target.closest('.icon-picker-trigger');
        if (trigger) {
            // Find the hidden input that immediately follows the trigger div
            activeIconInput = trigger.nextElementSibling; 
            // The icon element inside the trigger we want to visually update
            activeIconVisual = trigger.querySelector('i') || trigger; 
            
            document.getElementById('icon-picker-modal').classList.add('active');
        }

        // Select Icon
        const option = e.target.closest('.icon-option');
        if (option && activeIconInput) {
            const selectedClass = option.dataset.icon;
            
            // Update hidden form input
            activeIconInput.value = selectedClass;
            
            // Update visual trigger
            activeIconVisual.className = selectedClass;
            
            // Close modal
            document.getElementById('icon-picker-modal').classList.remove('active');
        }

        // Close Modal manually
        if (e.target.closest('.close-modal-icon')) {
            document.getElementById('icon-picker-modal').classList.remove('active');
        }
    });
</script>