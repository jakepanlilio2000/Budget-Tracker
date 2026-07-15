<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = e($vault['name']);
ob_start();
$sym = $baseCurrency['symbol'];
$metrics = $metrics;
?>
<div class="page-header flex-between">
    <div>
        <h1><?= e($vault['name']) ?></h1>
        <p class="text-secondary"><?= e($vault['description'] ?: 'No description provided.') ?></p>
    </div>
    <a href="<?= url('/vaults') ?>" class="btn" style="background: var(--text-secondary); color: white;">Back to Vaults</a>
</div>

<!-- Progress Card -->
<div class="card glass mb-4">
    <div class="flex-between" style="margin-bottom: 1.5rem;">
        <div>
            <span class="text-secondary">Current Savings</span>
            <h1 class="sensitive-data" style="margin: 0; color: var(--accent);"><?= $sym ?><?= number_format((float)$vault['current_amount'], 2) ?></h1>
        </div>
        <div class="text-right">
            <span class="text-secondary">Target</span>
            <h2 class="sensitive-data" style="margin: 0;"><?= $sym ?><?= number_format((float)$vault['target_amount'], 2) ?></h2>
        </div>
    </div>
    
    <div style="background: var(--border-color); border-radius: 99px; height: 14px; overflow: hidden; margin-bottom: 1rem;">
        <div style="width: <?= $metrics['percentage'] ?>%; height: 100%; background: var(--accent); transition: width 0.5s;"></div>
    </div>
    
    <div class="grid grid-3" style="text-align: center; gap: 1rem;">
        <div>
            <span class="text-secondary" style="font-size: 0.85rem;">Progress</span>
            <h3 style="margin: 0;"><?= $metrics['percentage'] ?>%</h3>
        </div>
        <div>
            <span class="text-secondary" style="font-size: 0.85rem;">Remaining</span>
            <h3 class="sensitive-data" style="margin: 0;"><?= $sym ?><?= number_format($metrics['remaining'], 2) ?></h3>
        </div>
        <div>
            <span class="text-secondary" style="font-size: 0.85rem;">Est. Completion</span>
            <h3 style="margin: 0;"><?= $metrics['estimated_months'] ? $metrics['estimated_months'] . ' mos' : 'N/A' ?></h3>
        </div>
    </div>

    <?php if ($vault['status'] === 'active'): ?>
    <div class="flex-between mt-4" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
        <button class="btn btn-primary" onclick="openVaultModal('deposit')"><i class="fas fa-arrow-down"></i> Deposit</button>
        <button class="btn" style="background: var(--danger); color: white;" onclick="openVaultModal('withdrawal')"><i class="fas fa-arrow-up"></i> Withdraw</button>
        <form method="POST" action="<?= url('/vaults/status/' . $vault['id']) ?>" onsubmit="return confirm('Cancel this goal? Funds remain in the vault but it moves to history.')">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="status" value="cancelled">
            <button type="submit" class="btn" style="background: var(--text-secondary); color: white;">Cancel Goal</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- Activity Timeline -->
<div class="card glass">
    <h3>Activity Timeline</h3>
    <?php if (empty($timeline)): ?>
        <p class="text-secondary mt-3">No transactions yet. Make your first deposit!</p>
    <?php else: ?>
        <div class="timeline mt-3">
            <?php foreach ($timeline as $t): ?>
            <div class="timeline-item">
                <div class="timeline-marker" style="background: <?= $t['type'] === 'deposit' ? 'var(--success)' : 'var(--danger)' ?>;"></div>
                <div class="glass" style="padding: 1rem; border-radius: 8px;">
                    <div class="flex-between">
                        <div>
                            <strong style="color: <?= $t['type'] === 'deposit' ? 'var(--success)' : 'var(--danger)' ?>">
                                <?= $t['type'] === 'deposit' ? '+' : '-' ?><?= $sym ?><?= number_format((float)$t['amount'], 2) ?>
                            </strong>
                            <span class="text-secondary" style="margin-left: 0.5rem; font-size: 0.85rem;"><?= ucfirst($t['type']) ?></span>
                        </div>
                        <small class="text-secondary"><?= e(date('M d, Y h:i A', strtotime($t['created_at']))) ?></small>
                    </div>
                    <?php if (!empty($t['notes'])): ?>
                        <p class="text-secondary" style="margin-top: 0.5rem; font-size: 0.9rem; font-style: italic;">"<?= e($t['notes']) ?>"</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Transaction Modal -->
<div id="vaultModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 400px;">
        <h3 id="modalTitle">Deposit</h3>
        <form method="POST" action="<?= url('/vaults/transact/' . $vault['id']) ?>" class="form-stack mt-3">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="type" id="modalType" value="deposit">
            <div class="form-group">
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" required min="0.01">
            </div>
            <div class="form-group">
                <label>Notes (Optional)</label>
                <input type="text" name="notes" placeholder="e.g., Monthly savings">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Confirm</button>
        </form>
    </div>
</div>

<script>
function openVaultModal(type) {
    document.getElementById('modalType').value = type;
    document.getElementById('modalTitle').textContent = type === 'deposit' ? 'Deposit Funds' : 'Withdraw Funds';
    document.getElementById('vaultModal').style.display = 'flex';
}
</script>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>