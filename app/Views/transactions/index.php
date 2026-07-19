<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Models\CurrencyService;

$pageTitle = 'Transactions';
ob_start();
$baseSym = $baseCurrency['symbol'] ?? '$';
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <h1>Transactions</h1>
    <button class="btn btn-primary" onclick="openTxnModal()">
        <i class="fas fa-plus"></i> New Transaction
    </button>
</div>

<div class="card glass">
    <?php if (empty($transactions)): ?>
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-receipt" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <p class="text-secondary">No transactions found. Click "New Transaction" to add one.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Account</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><?= e(date('M d, Y', strtotime($txn['transaction_date']))) ?></td>
                            <td>
                                <strong><?= e($txn['description'] ?: 'No description') ?></strong>
                                <?php if (!empty($txn['is_favorite'])): ?>
                                    <i class="fas fa-star" style="color: #fbbf24; margin-left: 0.5rem;"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= e($txn['account_name']) ?></td>
                            <td style="color: <?= $txn['type'] === 'income' ? 'var(--success)' : 'var(--danger)' ?>">
                                <?= $txn['type'] === 'income' ? '+' : '-' ?>         <?= e($txn['currency_symbol']) ?>
                                <?= number_format((float) $txn['total_amount'], 2) ?>
                            </td>
                            <td>
                                <span class="badge"
                                    style="background: var(--border-color);"><?= ucfirst(e($txn['status'])) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- TRANSACTION CREATE MODAL                   -->
<!-- ========================================== -->
<div id="txnModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this) closeTxnModal()">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <div class="flex-between"
            style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            <h3 id="modalTitle" style="margin:0;">New Transaction</h3>
            <button class="btn-icon" onclick="closeTxnModal()" style="font-size: 1.2rem;"><i
                    class="fas fa-times"></i></button>
        </div>

        <form method="POST" action="<?= url('/transactions/store') ?>" class="form-stack" id="txnForm">
            <?= \App\Core\CSRF::field() ?>

            <div class="grid grid-2">
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" id="txnType" required onchange="updateCategoryOptions()">
                        <option value="expense" selected>Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="transaction_date" id="txnDate" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label>Account</label>
                    <select name="account_id" required>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?> (<?= e($acc['currency_symbol']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Amount</label>
                    <input type="number" step="0.01" name="total_amount" id="totalAmount" value="0.00" required
                        oninput="calculateUnallocated()">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" placeholder="e.g., Grocery Shopping" required>
            </div>

            <!-- Split Transactions Section -->
            <div class="form-group"
                style="background: rgba(0,0,0,0.03); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <div class="flex-between" style="margin-bottom: 1rem;">
                    <label style="margin:0; font-weight: 600;">Splits (Categories)</label>
                    <button type="button" class="btn btn-sm" style="background: var(--accent); color: white;"
                        onclick="addSplitRow()">
                        <i class="fas fa-plus"></i> Add Split
                    </button>
                </div>

                <div id="splitsContainer">
                    <!-- Initial Split Row -->
                    <div class="grid grid-3 split-row" style="gap: 0.5rem; margin-bottom: 0.5rem;">
                        <select name="split_category[]" class="split-cat" required onchange="calculateUnallocated()">
                            <option value="">Select Category</option>
                            <optgroup label="Income">
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($cat['type'] === 'income'): ?>
                                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Expense">
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($cat['type'] === 'expense'): ?>
                                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <input type="number" step="0.01" name="split_amount[]" class="split-amt" placeholder="Amount"
                            required oninput="calculateUnallocated()">
                        <div style="display:flex; gap:0.5rem; align-items: end;">
                            <input type="text" name="split_notes[]" class="split-note" placeholder="Note (optional)"
                                style="flex:1; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-glass-solid); color: var(--text-primary);">
                        </div>
                    </div>
                </div>

                <div class="flex-between mt-3"
                    style="font-size: 0.9rem; font-weight: 600; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                    <span>Unallocated:</span>
                    <span id="unallocatedDisplay" style="color: var(--danger);"><?= $baseSym ?>0.00</span>
                </div>
                <input type="hidden" name="currency_id" value="<?= $baseCurrency['id'] ?? 1 ?>">
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional notes..."></textarea>
            </div>

            <div class="flex-between mt-4" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button type="button" class="btn" style="background: var(--text-secondary); color: white;"
                    onclick="closeTxnModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Save Transaction</button>
            </div>
        </form>
    </div>
</div>

<script>
    const baseSymbol = '<?= $baseSym ?>';

    function openTxnModal() {
        document.getElementById('txnModal').style.display = 'flex';
        document.getElementById('txnForm').reset();
        document.getElementById('txnDate').value = '<?= date('Y-m-d') ?>';
        document.getElementById('totalAmount').value = '0.00';
        const container = document.getElementById('splitsContainer');
        container.innerHTML = container.firstElementChild.outerHTML;
        const firstRow = container.querySelector('.split-row');
        firstRow.querySelector('.split-amt').oninput = calculateUnallocated;
        firstRow.querySelector('.split-cat').onchange = calculateUnallocated;

        calculateUnallocated();
    }

    function closeTxnModal() {
        document.getElementById('txnModal').style.display = 'none';
    }

    function updateCategoryOptions() {
        const type = document.getElementById('txnType').value;
        const selects = document.querySelectorAll('.split-cat');

        selects.forEach(select => {
            const incomeGroup = select.querySelector('optgroup[label="Income"]');
            const expenseGroup = select.querySelector('optgroup[label="Expense"]');

            if (type === 'income') {
                incomeGroup.style.display = 'block';
                expenseGroup.style.display = 'none';
            } else {
                incomeGroup.style.display = 'none';
                expenseGroup.style.display = 'block';
            }
            select.value = "";
        });
        calculateUnallocated();
    }

    function addSplitRow() {
        const container = document.getElementById('splitsContainer');
        const originalSelect = document.querySelector('.split-cat');
        const selectClone = originalSelect.cloneNode(true);

        const type = document.getElementById('txnType').value;
        const incomeGroup = selectClone.querySelector('optgroup[label="Income"]');
        const expenseGroup = selectClone.querySelector('optgroup[label="Expense"]');
        if (type === 'income') {
            incomeGroup.style.display = 'block';
            expenseGroup.style.display = 'none';
        } else {
            incomeGroup.style.display = 'none';
            expenseGroup.style.display = 'block';
        }

        selectClone.required = true;
        selectClone.onchange = calculateUnallocated;

        const newRow = document.createElement('div');
        newRow.className = 'grid grid-3 split-row';
        newRow.style.cssText = 'gap: 0.5rem; margin-bottom: 0.5rem;';
        newRow.innerHTML = `
        <div class="select-wrapper"></div>
        <input type="number" step="0.01" name="split_amount[]" class="split-amt" placeholder="Amount" required oninput="calculateUnallocated()">
        <div style="display:flex; gap:0.5rem; align-items: end;">
            <input type="text" name="split_notes[]" class="split-note" placeholder="Note" style="flex:1; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-glass-solid); color: var(--text-primary);">
            <button type="button" class="btn btn-sm" style="background:var(--danger); color:white; height: 42px;" onclick="this.parentElement.parentElement.remove(); calculateUnallocated();">×</button>
        </div>
    `;
        newRow.querySelector('.select-wrapper').appendChild(selectClone);
        container.appendChild(newRow);
        calculateUnallocated();
    }

    function calculateUnallocated() {
        const total = parseFloat(document.getElementById('totalAmount').value) || 0;
        const amounts = document.querySelectorAll('.split-amt');
        const categories = document.querySelectorAll('.split-cat');
        let allocated = 0;
        let allCategoriesSelected = true;

        amounts.forEach((input, index) => {
            allocated += parseFloat(input.value) || 0;
            if (!categories[index].value) {
                allCategoriesSelected = false;
            }
        });

        const unallocated = total - allocated;
        const display = document.getElementById('unallocatedDisplay');
        const submitBtn = document.getElementById('submitBtn');

        if (Math.abs(unallocated) < 0.01 && total > 0 && allCategoriesSelected && amounts.length > 0) {
            display.style.color = 'var(--success)';
            display.textContent = baseSymbol + '0.00 (Balanced ✓)';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            display.style.color = 'var(--danger)';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';

            if (!allCategoriesSelected) {
                display.textContent = '️ Please select a category for ALL splits';
            } else if (amounts.length === 0) {
                display.textContent = '⚠️ Add at least one split';
            } else if (total <= 0) {
                display.textContent = '️ Total amount must be greater than 0';
            } else {
                display.textContent = `️ ${baseSymbol}${Math.abs(unallocated).toFixed(2)} unallocated`;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateCategoryOptions();
    });
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>