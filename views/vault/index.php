<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-vault" style="color: var(--accent-blue); margin-right: 8px;"></i> The Vault</h1>
        <p style="color: var(--text-secondary);">Your total locked savings: 
            <strong style="color: var(--accent-green); font-size: 18px; font-family: 'JetBrains Mono', monospace; display: inline-flex; gap: 4px;">
                <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                <span class="amount" data-full-val="<?= (float)$total_vault ?>"><?= number_format($total_vault, 2) ?></span>
            </strong>
        </p>
    </div>
    <div class="top-bar-right">
        <button class="btn primary" onclick="document.getElementById('new-goal-modal').classList.add('active')"><i class="fa-solid fa-plus"></i> New Goal</button>
    </div>
</header>

<div class="dashboard-widgets" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
    <?php foreach ($goals as $goal): 
        $pct = ($goal['target_amount'] > 0) ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
        $pct = min(100, max(0, $pct));
        $isComplete = $goal['current_amount'] >= $goal['target_amount'];
    ?>
    <div class="card widget-card" style="position: relative; overflow: hidden; border-top: 4px solid <?= htmlspecialchars($goal['color']) ?>;">
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
            <div style="display: flex; align-items: baseline; gap: 4px;">
                <span class="currency-label" style="font-weight: bold; font-size: 24px; color: <?= $isComplete ? 'var(--accent-green)' : 'var(--text-primary)' ?>;"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                <span class="amount" data-full-val="<?= (float)$goal['current_amount'] ?>" style="font-size: 24px; font-weight: bold; color: <?= $isComplete ? 'var(--accent-green)' : 'var(--text-primary)' ?>;" id="val-<?= $goal['id'] ?>"><?= number_format($goal['current_amount'], 2) ?></span>
                <span style="color: var(--text-secondary); font-size: 12px;">/ <span class="amount" data-full-val="<?= (float)$goal['target_amount'] ?>"><?= number_format($goal['target_amount'], 2) ?></span></span>
            </div>
            <span style="font-size: 14px; font-weight: bold; color: <?= htmlspecialchars($goal['color']) ?>;" id="pct-<?= $goal['id'] ?>"><?= round($pct) ?>%</span>
        </div>

        <div class="progress-bg" style="height: 12px; border-radius: 6px; background: var(--bg-primary); margin-bottom: 24px;">
            <div class="progress-fill" id="bar-<?= $goal['id'] ?>" style="width: <?= $pct ?>%; background: <?= htmlspecialchars($goal['color']) ?>; box-shadow: 0 0 10px <?= htmlspecialchars($goal['color']) ?>80;"></div>
        </div>

        <div style="display: flex; gap: 8px; margin-top: auto;">
            <button class="btn ghost fund-btn" style="flex: 1; border: 1px solid var(--border);" data-id="<?= $goal['id'] ?>" data-name="<?= htmlspecialchars($goal['name']) ?>">Deposit / Withdraw</button>
            <button class="btn ghost delete-goal-btn" data-id="<?= $goal['id'] ?>" data-name="<?= htmlspecialchars($goal['name']) ?>" style="padding: 10px; color: var(--accent-red);"><i class="fa-solid fa-trash-can"></i></button>
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
        <form action="<?= $basePath ?>/vault/<?= htmlspecialchars((string)$profile_id) ?>/store" method="POST">
            <input type="hidden" name="csrf_token" id="global-csrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
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
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.getElementById('global-csrf').value;
    const basePath = '<?= $basePath ?>';

    // 1. Open the Fund Modal & inject data
    document.querySelectorAll('.fund-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('fund-title').textContent = 'Update: ' + btn.dataset.name;
            document.getElementById('fund-goal-id').value = btn.dataset.id;
            document.getElementById('fund-amount').value = '';
            document.getElementById('fund-modal').classList.add('active');
        });
    });

    // 2. Process Deposit/Withdraw AJAX request
    document.getElementById('confirm-fund-btn').addEventListener('click', async function() {
        const goalId = document.getElementById('fund-goal-id').value;
        const amount = document.getElementById('fund-amount').value.trim();
        
        if (!amount || isNaN(amount.replace(/[^0-9.-]/g, ''))) {
            alert('Please enter a valid numeric amount.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Processing...';

        const formData = new FormData();
        formData.append('amount', amount);
        formData.append('csrf_token', csrfToken);

        try {
            const res = await fetch(`${basePath}/vault/fund/${goalId}`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                // Reload to recalculate UI (Vault Totals, Progress Bars, etc)
                window.location.reload(); 
            } else {
                alert(data.error || 'Failed to update the goal balance.');
                this.disabled = false;
                this.textContent = 'Apply Change';
            }
        } catch (e) {
            console.error(e);
            alert('A network error occurred.');
            this.disabled = false;
            this.textContent = 'Apply Change';
        }
    });

    // 3. Process Delete Goal AJAX request
    document.querySelectorAll('.delete-goal-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const goalName = btn.dataset.name;
            if (!confirm(`Are you sure you want to permanently delete the vault goal "${goalName}"?`)) return;

            const goalId = btn.dataset.id;
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);

            try {
                const res = await fetch(`${basePath}/vault/delete/${goalId}`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to delete the goal.');
                }
            } catch (e) {
                console.error(e);
                alert('A network error occurred.');
            }
        });
    });
});
</script>