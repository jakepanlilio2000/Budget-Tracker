<?php
declare(strict_types=1);
use App\Core\Auth;

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
            <i class="fas fa-clipboard-check"
                style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
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
                                <span class="badge badge-<?= e($item['type']) ?>"
                                    style="margin-left: 0.5rem; font-size: 0.7rem;">
                                    <?= ucfirst(e($item['type'])) ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <div
                                    style="font-weight: bold; color: <?= $item['type'] === 'income' ? 'var(--success)' : 'var(--danger)' ?>">
                                    <?= $item['type'] === 'income' ? '+' : '-' ?>         <?= e($item['symbol']) ?>
                                    <?= number_format((float) $item['amount'], 2) ?>
                                </div>
                                <small class="text-secondary">Due:
                                    <?= e(date('M d, Y', strtotime($item['due_date']))) ?></small>
                            </div>
                        </div>
                        <?php if ($item['notes']): ?>
                            <p class="text-secondary" style="font-size: 0.85rem; margin-top: 0.5rem;"><?= e($item['notes']) ?></p>
                        <?php endif; ?>
                        <div style="margin-top: 0.75rem; text-align: right;">
                            <button class="btn btn-sm" style="background: var(--success); color: white;"
                                onclick="openPayPendingModal(<?= $item['id'] ?>, '<?= e($item['description']) ?>', <?= (float) $item['amount'] ?>, '<?= e($item['type']) ?>')">
                                <i class="fas fa-check"></i> Mark as Paid
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Recently Paid History Section -->
<?php if (!empty($paidItems)): ?>
    <div class="card glass mt-4" style="opacity: 0.85;">
        <div class="flex-between" style="margin-bottom: 1rem;">
            <h3><i class="fas fa-check-circle" style="color: var(--success);"></i> Recently Paid</h3>
            <small class="text-secondary">Showing last 10 completed items</small>
        </div>

        <div class="timeline">
            <?php foreach ($paidItems as $item): ?>
                <div class="timeline-item">
                    <div class="timeline-marker" style="background: var(--success);"></div>
                    <div class="timeline-content glass" style="padding: 1rem; border-left: 3px solid var(--success);">
                        <div class="flex-between">
                            <div>
                                <strong>
                                    <?= e($item['description']) ?>
                                </strong>
                                <span class="badge"
                                    style="background: var(--success); color: white; margin-left: 0.5rem; font-size: 0.7rem;">PAID</span>
                            </div>
                            <div class="text-right">
                                <div style="font-weight: bold; color: var(--success);">
                                    <?= $item['type'] === 'income' ? '+' : '-' ?>
                                    <?= e($item['symbol']) ?>
                                    <?= number_format((float) $item['amount'], 2) ?>
                                </div>
                                <small class="text-secondary">
                                    Paid:
                                    <?= e(date('M d, Y', strtotime($item['updated_at']))) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>


<!-- Add Pending Modal -->
<div id="addPendingModal" class="modal-overlay" style="display: none;"
    onclick="if(event.target===this)this.style.display='none'">
    <div class=" modal-content glass" style="padding: 1.5rem; max-width: 500px;">
        <h3>Add Scheduled Item</h3>
        <form method="POST" action="<?= url('/pending-ledger/mark-paid/' . $item['id']) ?>" style="display:inline;">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="create_transaction" value="1">
            <select name="account_id" required
                style="margin-right: 0.5rem; padding: 0.25rem; border-radius: 4px; border: 1px solid var(--border-color);">
                <option value="">Select Account...</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category_id"
                style="margin-right: 0.5rem; padding: 0.25rem; border-radius: 4px; border: 1px solid var(--border-color);">
                <option value="">Select Category...</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-sm btn-primary" title="Mark as Paid & Create Transaction">
                <i class="fas fa-check"></i> Mark Paid
            </button>
        </form>
    </div>
</div>

<!-- Pay Pending Modal -->
<div id="payPendingModal" class="modal-overlay" style="display: none;"
    onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 500px;">
        <h3>Mark as Paid</h3>
        <p class="text-secondary" id="pendingItemInfo"></p>

        <form method="POST" id="payPendingForm" class="form-stack mt-3">
            <?= \App\Core\CSRF::field() ?>

            <div class="form-group">
                <label class="checkbox-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="create_transaction" value="1" id="createTransactionCheck"
                        onchange="toggleTransactionFields()">
                    Create actual transaction and update account balance
                </label>
            </div>

            <div id="transactionFields" style="display: none;">
                <div class="form-group">
                    <label>Account</label>
                    <select name="account_id" id="pendingAccountId">
                        <option value="">-- Select Account --</option>
                        <?php foreach (\App\Models\Account::getAllByUser(Auth::id()) as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?>
                                (<?= e($acc['currency_symbol']) ?><?= number_format((float) $acc['current_balance'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Category (Optional)</label>
                    <select name="category_id">
                        <option value="">-- None --</option>
                        <?php foreach (\App\Models\Category::getAllByUser(Auth::id()) as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex-between mt-4">
                <button type="button" class="btn" style="background: var(--text-secondary); color: white;"
                    onclick="document.getElementById('payPendingModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPayPendingModal(id, description, amount, type) {
        document.getElementById('payPendingForm').action = '<?= url('/pending-ledger/mark-paid/') ?>' + id;
        document.getElementById('pendingItemInfo').innerHTML = `
        <strong>${description}</strong><br>
        Amount: <span style="color: ${type === 'income' ? 'var(--success)' : 'var(--danger)'}">
            ${type === 'income' ? '+' : '-'}${amount.toFixed(2)}
        </span>
    `;
        document.getElementById('payPendingModal').style.display = 'flex';
    }

    function toggleTransactionFields() {
        const checked = document.getElementById('createTransactionCheck').checked;
        document.getElementById('transactionFields').style.display = checked ? 'block' : 'none';
    }
</script>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>