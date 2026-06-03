<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>🏦 The Vault</h1>
        <p style="color: var(--text-secondary);">Your total locked savings: <strong style="color: var(--accent-green); font-size: 18px; font-family: 'JetBrains Mono', monospace;"><?= number_format($total_vault, 2) ?></strong></p>
    </div>
    <div class="top-bar-right">
        <button class="btn primary" onclick="document.getElementById('new-goal-modal').classList.add('active')">➕ New Goal</button>
    </div>
</header>

<div class="dashboard-widgets" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
    <?php foreach ($goals as $goal): 
        $pct = ($goal['target_amount'] > 0) ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
        $pct = min(100, max(0, $pct)); // Clamp between 0-100 for the bar
        $isComplete = $goal['current_amount'] >= $goal['target_amount'];
    ?>
    <div class="card widget-card" style="position: relative; overflow: hidden; border-top: 4px solid <?= $goal['color'] ?>;">
        <?php if($isComplete): ?>
            <div style="position: absolute; top: 12px; right: 12px; background: var(--accent-green); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase;">Achieved</div>
        <?php endif; ?>
        
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <span style="font-size: 32px;"><?= htmlspecialchars($goal['icon']) ?></span>
            <div>
                <h3 style="margin: 0; font-size: 18px;"><?= htmlspecialchars($goal['name']) ?></h3>
                <?php if($goal['target_date']): ?>
                    <span style="font-size: 12px; color: var(--text-secondary);">Target: <?= date('M j, Y', strtotime($goal['target_date'])) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px;">
            <div>
                <span class="amount" style="font-size: 24px; font-weight: bold; color: <?= $isComplete ? 'var(--accent-green)' : 'var(--text-primary)' ?>;" id="val-<?= $goal['id'] ?>"><?= number_format($goal['current_amount'], 2) ?></span>
                <span style="color: var(--text-secondary); font-size: 12px;">/ <?= number_format($goal['target_amount'], 2) ?></span>
            </div>
            <span style="font-size: 14px; font-weight: bold; color: <?= $goal['color'] ?>;" id="pct-<?= $goal['id'] ?>"><?= round($pct) ?>%</span>
        </div>

        <div class="progress-bg" style="height: 12px; border-radius: 6px; background: var(--bg-primary); margin-bottom: 24px;">
            <div class="progress-fill" id="bar-<?= $goal['id'] ?>" style="width: <?= $pct ?>%; background: <?= $goal['color'] ?>; box-shadow: 0 0 10px <?= $goal['color'] ?>80;"></div>
        </div>

        <div style="display: flex; gap: 8px; margin-top: auto;">
            <button class="btn ghost fund-btn" style="flex: 1; border: 1px solid var(--border);" data-id="<?= $goal['id'] ?>" data-name="<?= htmlspecialchars($goal['name']) ?>">Deposit / Withdraw</button>
            <button class="btn ghost delete-goal-btn" data-id="<?= $goal['id'] ?>" data-name="<?= htmlspecialchars($goal['name']) ?>" style="padding: 10px;">🗑️</button>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if(empty($goals)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-secondary);">
            <h2>The Vault is empty.</h2>
            <p>Create a savings goal to start locking money away for the future.</p>
        </div>
    <?php endif; ?>
</div>

<div id="new-goal-modal" class="modal">
    <div class="modal-content drawer">
        <h3>Create Savings Goal</h3>
        <form action="<?= $basePath ?>/vault/<?= $profile_id ?>/store" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label>Goal Name</label>
                <input type="text" name="name" required placeholder="e.g., Emergency Fund">
            </div>
            
            <div class="form-group">
                <label>Target Amount</label>
                <input type="text" inputmode="decimal" name="target_amount" required placeholder="50000">
            </div>

            <div class="form-group" style="display: flex; gap: 16px;">
                <div style="flex: 1;">
                    <label>Theme Color</label>
                    <input type="color" name="color" value="#3fb950" style="height: 48px; padding: 4px;">
                </div>
                <div style="flex: 1;">
                    <label>Icon (Emoji)</label>
                    <input type="text" name="icon" value="🎯" style="text-align: center;">
                </div>
            </div>

            <div class="form-group">
                <label>Target Date (Optional)</label>
                <input type="date" name="target_date">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn ghost close-modal">Cancel</button>
                <button type="submit" class="btn primary">Create Goal</button>
            </div>
        </form>
    </div>
</div>

<div id="fund-modal" class="modal">
    <div class="modal-content drawer">
        <h3 id="fund-title">Update Balance</h3>
        <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 13px;">Use a positive number to deposit, or a negative number (e.g., -500) to withdraw.</p>
        <div class="form-group">
            <input type="text" inputmode="decimal" id="fund-amount" placeholder="Amount (e.g. 1000)" style="font-size: 24px; font-weight: bold; text-align: center;">
            <input type="hidden" id="fund-goal-id">
        </div>
        <div class="modal-actions">
            <button type="button" class="btn ghost close-modal">Cancel</button>
            <button type="button" class="btn primary" id="confirm-fund-btn">Apply Change</button>
        </div>
    </div>
</div>

<script>
(function initVault() {
    
    document.querySelectorAll('.fund-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = e.target.dataset.id;
            const name = e.target.dataset.name;
            document.getElementById('fund-title').innerText = `Update: ${name}`;
            document.getElementById('fund-goal-id').value = id;
            document.getElementById('fund-amount').value = '';
            document.getElementById('fund-modal').classList.add('active');
            setTimeout(() => document.getElementById('fund-amount').focus(), 100);
        });
    });
    const confirmBtn = document.getElementById('confirm-fund-btn');
    if (confirmBtn) {
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', async () => {
            const id = document.getElementById('fund-goal-id').value;
            const amount = document.getElementById('fund-amount').value;
            
            if(!amount) return;

            const formData = new FormData();
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
            formData.append('amount', amount);

            try {
                const res = await fetch(`<?= $basePath ?>/vault/fund/${id}`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    window.location.reload(); 
                } else {
                    throw new Error('Update failed');
                }
            } catch (err) {
                if (typeof showToast === 'function') showToast('Failed to update funds', 'error');
            }
        });
    }
    document.querySelectorAll('.delete-goal-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const target = e.target.closest('button');
            const id = target.dataset.id;
            const name = target.dataset.name;

            if (typeof confirmAction === 'function') {
                confirmAction('Smash the Piggy Bank?', `Are you sure you want to delete "${name}"? This will erase its history.`, async () => {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
                    
                    try {
                        const res = await fetch(`<?= $basePath ?>/vault/delete/${id}`, { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.success) {
                            target.closest('.widget-card').remove();
                            if (typeof showToast === 'function') showToast('Goal deleted', 'success');
                        } else {
                            throw new Error('Delete failed');
                        }
                    } catch (err) {
                        if (typeof showToast === 'function') showToast('Failed to delete', 'error');
                    }
                });
            }
        });
    });
})();
</script>