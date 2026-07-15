<?php 
$pageTitle = 'Pending Ledger';
ob_start(); 
?>
<div class="page-header flex-between">
    <h1>Pending Ledger</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addPendingModal').style.display='flex'">
        <i class="fas fa-plus"></i> Add Scheduled Item
    </button>
</div>

<div class="card glass">
    <?php if (empty($items)): ?>
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-clipboard-check" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <p class="text-secondary">No pending items. You're all caught up!</p>
        </div>
    <?php else: ?>
        <div class="timeline">
            <?php foreach ($items as $item): ?>
            <div class="timeline-item">
                <div class="timeline-marker priority-<?= e($item['priority']) ?>"></div>
                <div class="timeline-content glass" style="padding: 1rem;">
                    <div class="flex-between">
                        <div>
                            <strong><?= e($item['description']) ?></strong>
                            <span class="badge badge-<?= e($item['type']) ?>" style="margin-left: 0.5rem; font-size: 0.7rem;">
                                <?= ucfirst(e($item['type'])) ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <div style="font-weight: bold; color: <?= $item['type'] === 'income' ? 'var(--success)' : 'var(--danger)' ?>">
                                <?= $item['type'] === 'income' ? '+' : '-' ?><?= e($item['symbol']) ?><?= number_format($item['amount'], 2) ?>
                            </div>
                            <small class="text-secondary">Due: <?= e(date('M d, Y', strtotime($item['due_date']))) ?></small>
                        </div>
                    </div>
                    <?php if ($item['notes']): ?>
                        <p class="text-secondary" style="font-size: 0.85rem; margin-top: 0.5rem;"><?= e($item['notes']) ?></p>
                    <?php endif; ?>
                    <div style="margin-top: 0.75rem; text-align: right;">
                        <form method="POST" action="<?= url('/pending-ledger/mark-paid/' . $item['id']) ?>" style="display:inline;">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn btn-sm" style="background: var(--success); color: white;">
                                <i class="fas fa-check"></i> Mark Paid
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Pending Modal (Simplified inline for patch) -->
<div id="addPendingModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content glass" style="padding: 1.5rem;">
        <h3>Add Scheduled Item</h3>
        <form method="POST" action="<?= url('/pending-ledger/store') ?>" class="form-stack mt-3">
            <?= \App\Core\CSRF::field() ?>
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Type</label>
                    <select name="type"><option value="expense">Expense</option><option value="income">Income</option></select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="low">Low</option><option value="medium" selected>Medium</option>
                        <option value="high">High</option><option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Description</label><input type="text" name="description" required></div>
            <div class="grid grid-2">
                <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" required></div>
                <div class="form-group"><label>Due Date</label><input type="date" name="due_date" value="<?= date('Y-m-d') ?>" required></div>
            </div>
            <div class="form-group"><label>Currency</label>
                <select name="currency_id">
                    <?php foreach ($currencies as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['code']) ?> - <?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Add to Ledger</button>
        </form>
    </div>
</div>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>