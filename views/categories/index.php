<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <div class="top-bar-left">
        <h1>Manage Categories</h1>
        <p style="color: var(--text-secondary);">Drag and drop rows to reorder. Changes apply live to your active tables.</p>
    </div>
</header>

<div class="card" style="padding: 0; overflow: hidden; max-width: 800px;">
    
    <ul id="category-list" style="list-style: none; margin: 0; padding: 0;">
        <?php if (empty($categories)): ?>
            <li style="padding: 24px; text-align: center; color: var(--text-secondary);">No custom categories found. Add your first below!</li>
        <?php endif; ?>

        <?php foreach ($categories as $cat): ?>
            <li class="cat-row" data-id="<?= $cat['id'] ?>" draggable="true" style="display: flex; align-items: center; gap: 16px; padding: 16px; border-bottom: 1px solid var(--border); background: var(--bg-primary); cursor: grab; transition: background 0.2s;">
                <span class="drag-handle" style="color: var(--text-muted); cursor: grab; padding: 4px;">☰</span>
                <div style="width: 16px; height: 16px; border-radius: 50%; background: <?= htmlspecialchars($cat['color']) ?>; flex-shrink: 0;"></div>
                <span style="font-size: 20px; line-height: 1;"><?= htmlspecialchars($cat['icon']) ?></span>
                
                <span style="flex: 1; font-weight: 500; font-size: 15px; color: var(--text-primary);">
                    <?= htmlspecialchars($cat['name']) ?>
                </span>
                
                <span style="
                    padding: 4px 8px; 
                    border-radius: 4px; 
                    font-size: 11px; 
                    font-weight: 700; 
                    background: var(--bg-elevated);
                    color: <?= $cat['type'] === 'inflow' ? 'var(--accent-green)' : ($cat['type'] === 'outflow' ? 'var(--accent-red)' : 'var(--accent-blue)') ?>;">
                    <?= strtoupper($cat['type']) ?>
                </span>

                <div style="display: flex; gap: 4px; align-items: center;">
                    <a href="<?= $basePath ?>/categories/edit/<?= $cat['id'] ?>" class="icon-btn ghost" style="padding: 6px;">✏️</a>
                    <button class="icon-btn ghost delete-cat-btn" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" style="padding: 6px; cursor: pointer;">🗑️</button>
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
            <input type="text" name="icon" placeholder="Emoji" value="🏷️" required style="width: 70px; margin: 0; text-align: center;">
            
            <button type="submit" class="btn primary" style="white-space: nowrap;">➕ Add</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('category-list');
    let draggedItem = null;

    // Drag-and-Drop Implementation
    list.addEventListener('dragstart', e => {
        const row = e.target.closest('.cat-row');
        if (!row) return;
        draggedItem = row;
        setTimeout(() => draggedItem.style.opacity = '0.4', 0);
    });

    list.addEventListener('dragend', e => {
        if (!draggedItem) return;
        draggedItem.style.opacity = '1';
        draggedItem = null;
        saveOrder();
    });

    list.addEventListener('dragover', e => {
        e.preventDefault();
        const afterElement = getDragAfterElement(list, e.clientY);
        if (draggedItem) {
            if (afterElement == null) {
                list.appendChild(draggedItem);
            } else {
                list.insertBefore(draggedItem, afterElement);
            }
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.cat-row:not([style*="opacity: 0.4"])')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    async function saveOrder() {
        const rows = [...list.querySelectorAll('.cat-row')];
        const ids = rows.map(row => parseInt(row.dataset.id));

        try {
            const res = await fetch(`<?= $basePath ?>/categories/<?= $profile_id ?>/reorder`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ 
                    csrf_token: CSRF_TOKEN, 
                    ids: ids 
                })
            });
            
            const data = await res.json();
            if (data.success) {
                showToast('Display configurations updated', 'success');
            } else {
                showToast(data.error || 'Failed to update order', 'error');
            }
        } catch (err) {
            showToast('Network error during arrangement sync', 'error');
        }
    }

    document.querySelectorAll('.delete-cat-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetButton = e.target.closest('.delete-cat-btn');
            const id = targetButton.dataset.id;
            const name = targetButton.dataset.name;

            confirmAction('Delete Category', `Are you certain you want to clear "${name}"? This requires no active entries dependency.`, async () => {
                const formData = new FormData();
                formData.append('csrf_token', CSRF_TOKEN);

                try {
                    const res = await fetch(`<?= $basePath ?>/categories/${id}/delete`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.success) {
                        targetButton.closest('.cat-row').remove();
                        showToast('Category deleted successfully', 'success');
                    } else {
                        showToast(data.error || 'Deletion rejected', 'error');
                    }
                } catch (err) {
                    showToast('Communication error processing operation', 'error');
                }
            });
        });
    });
});
</script>