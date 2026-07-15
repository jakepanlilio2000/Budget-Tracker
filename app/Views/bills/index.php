<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Models\Account;

$pageTitle = 'Bills & Recurring';
ob_start(); 
?>
<div class="page-header flex-between">
    <h1>Bills & Recurring</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addBillModal').style.display='flex'">
        <i class="fas fa-plus"></i> Add Bill
    </button>
</div>

<div class="grid grid-2">
    <?php if (empty($bills)): ?>
        <div class="card glass text-center" style="grid-column: 1 / -1; padding: 3rem;">
            <i class="fas fa-file-invoice" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem; opacity: 0.5;"></i>
            <h3>No Active Bills</h3>
            <p class="text-secondary">Add your first recurring bill to start tracking due dates and penalties.</p>
        </div>
    <?php else: ?>
        <?php foreach ($bills as $bill): 
            $progress = $bill['total_amount'] > 0 ? min(100, ($bill['paid'] / $bill['total_amount']) * 100) : 0;
            $isOverdue = $bill['is_overdue'] ?? false;
        ?>
        <div class="card glass bill-card" style="<?= $isOverdue ? 'border-left: 4px solid var(--danger);' : 'border-left: 4px solid var(--accent);' ?>">
            <div class="flex-between" style="margin-bottom: 1rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.1rem;"><?= e($bill['name']) ?></h3>
                    <div style="margin-top: 0.25rem; display: flex; align-items: center; gap: 0.5rem;">
                        <small class="text-secondary">
                            <i class="far fa-calendar-alt"></i> Due: <?= e(date('M d, Y', strtotime($bill['next_due_date']))) ?>
                        </small>
                        <?php if ($isOverdue): ?>
                            <span class="badge badge-danger">OVERDUE</span>
                        <?php else: ?>
                            <span class="badge badge-success">ACTIVE</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="sensitive-data" style="margin: 0; color: var(--text-primary); font-size: 1.5rem;"><?= number_format((float)$bill['total_amount'], 2) ?></h2>
                    <small class="text-secondary" style="text-transform: capitalize;"><?= e($bill['frequency']) ?></small>
                </div>
            </div>

            <div style="margin-bottom: 0.75rem;">
                <div style="background: var(--border-color); border-radius: 99px; height: 8px; overflow: hidden; width: 100%;">
                    <div style="width: <?= $progress ?>%; height: 100%; background: <?= $isOverdue ? 'var(--danger)' : 'var(--success)' ?>; transition: width 0.5s ease;"></div>
                </div>
                <div class="flex-between" style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                    <span class="sensitive-data">Paid: <?= number_format((float)$bill['paid'], 2) ?></span>
                    <span class="sensitive-data">Remaining: <?= number_format((float)$bill['remaining'], 2) ?></span>
                </div>
            </div>

            <?php if (($bill['penalty'] ?? 0) > 0): ?>
                <div class="alert alert-danger" style="margin-top: 0.5rem; padding: 0.6rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <span>Late Penalty: <strong>+<?= number_format((float)$bill['penalty'], 2) ?></strong></span>
                </div>
            <?php endif; ?>

            <div style="margin-top: 1rem; text-align: right; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button class="btn btn-sm btn-primary" onclick="openPayModal(<?= $bill['id'] ?>, '<?= e($bill['name']) ?>', <?= ((float)($bill['remaining'] ?? 0)) + ((float)($bill['penalty'] ?? 0)) ?>, <?= (float)($bill['penalty'] ?? 0) ?>)">
                    <i class="fas fa-credit-card"></i> Pay Now
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Bill Modal -->
<div id="addBillModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 500px;">
        <h3>Add New Bill</h3>
        <form method="POST" action="<?= url('/bills/store') ?>" class="form-stack mt-3">
            <?= \App\Core\CSRF::field() ?>
            <div class="form-group"><label>Bill Name</label><input type="text" name="name" required placeholder="e.g., Internet, Rent"></div>
            <div class="grid grid-2">
                <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="total_amount" required></div>
                <div class="form-group"><label>Frequency</label>
                    <select name="frequency">
                        <option value="weekly">Weekly</option><option value="monthly" selected>Monthly</option>
                        <option value="quarterly">Quarterly</option><option value="yearly">Yearly</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-2">
                <div class="form-group"><label>Category (Optional)</label>
                    <select name="category_id">
                        <option value="">-- None --</option>
                        <?php foreach (\App\Models\Category::getAllByUser(Auth::id(), 'expense') as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Duration (Months)</label>
                    <input type="number" name="recurring_count" min="1" placeholder="e.g., 6 (Leave blank for indefinite)">
                </div>
            </div>
            <div class="grid grid-2">
                <div class="form-group"><label>Penalty Rate</label><input type="number" step="0.01" name="penalty_rate" value="0"></div>
                <div class="form-group"><label>Penalty Type</label>
                    <select name="penalty_type"><option value="fixed">Fixed</option><option value="percentage">Percentage (%)</option></select>
                </div>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Save Bill</button>
        </form>
    </div>
</div>

<!-- Pay Bill Modal -->
<div id="payBillModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 400px;">
        <h3>Pay Bill: <span id="payBillName"></span></h3>
        <p class="text-secondary">Total Due: <strong id="payBillDue" style="color:var(--text-primary)"></strong></p>
        <p class="text-secondary" id="payBillPenaltyWrap" style="display:none; color:var(--danger);">Includes Penalty: <strong id="payBillPenalty"></strong></p>
        
        <form method="POST" id="payBillForm" class="form-stack mt-3">
            <?= \App\Core\CSRF::field() ?>
            <div class="form-group">
                <label>Amount to Pay <small class="text-secondary">(Edit this value to pay partially)</small></label>
                <input type="number" step="0.01" name="amount_paid" id="payAmountInput" required>
            </div>
            <div class="form-group"><label>Pay from Account (Optional)</label>
                <select name="account_id">
                    <option value="">-- Do not deduct from account --</option>
                    <?php foreach (Account::getAllByUser(Auth::id()) as $acc): ?>
                        <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?> (<?= e($acc['currency_symbol']) ?><?= number_format((float)$acc['current_balance'], 2) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Notes</label><input type="text" name="payment_notes" placeholder="e.g., Paid via check"></div>
            <button type="submit" class="btn btn-primary btn-block">Confirm Payment</button>
        </form>
    </div>
</div>

<script>
function openPayModal(id, name, due, penalty) {
    document.getElementById('payBillForm').action = '<?= url('/bills/pay/') ?>' + id;
    document.getElementById('payBillName').textContent = name;
    document.getElementById('payBillDue').textContent = due.toFixed(2);
    document.getElementById('payAmountInput').value = due.toFixed(2);
    
    if (penalty > 0) {
        document.getElementById('payBillPenaltyWrap').style.display = 'block';
        document.getElementById('payBillPenalty').textContent = penalty.toFixed(2);
    } else {
        document.getElementById('payBillPenaltyWrap').style.display = 'none';
    }
    document.getElementById('payBillModal').style.display = 'flex';
}
</script>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>