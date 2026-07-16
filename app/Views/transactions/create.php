<?php
use App\Models\Category;
use App\Core\Auth;
$pageTitle = 'New Transaction';
ob_start();
?>
<div class="page-header">
    <h1>Add Transaction</h1>
</div>

<div class="card glass" style="max-width: 800px;">
    <form method="POST" action="<?= url('/transactions/store') ?>" class="form-stack" id="txnForm">
        <?= \App\Core\CSRF::field() ?>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Type</label>
                <select name="type" id="txnType" required>
                    <option value="expense" selected>Expense</option>
                    <option value="income">Income</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="transaction_date" value="<?= date('Y-m-d') ?>" required>
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
                <input type="number" step="0.01" name="total_amount" id="totalAmount"
                    value="<?= e(old('total_amount', '0.00')) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" value="<?= e(old('description')) ?>"
                placeholder="e.g., Grocery Shopping" required>
        </div>

        <!-- Split Transactions Section -->
        <div class="form-group"
            style="background: rgba(0,0,0,0.03); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
            <div class="flex-between mb-2">
                <label style="margin:0;">Splits (Categories)</label>
                <button type="button" class="btn btn-sm" style="background: var(--accent); color: white;"
                    onclick="addSplitRow()">+ Add Split</button>
            </div>

            <div id="splitsContainer">
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
                    <input type="text" name="split_notes[]" class="split-note" placeholder="Note (optional)">
                </div>
            </div>

            <div class="flex-between mt-2" style="font-size: 0.9rem; font-weight: 600;">
                <span>Unallocated:</span>
                <span id="unallocatedDisplay" style="color: var(--danger);"><?= $baseCurrency['symbol'] ?>0.00</span>
            </div>
            <input type="hidden" name="currency_id" value="<?= $baseCurrency['id'] ?? 1 ?>">
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="2"></textarea>
        </div>

        <div class="flex-between mt-4">
            <a href="<?= url('/transactions') ?>" class="btn"
                style="background: var(--text-secondary); color: white;">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Save Transaction</button>
        </div>
    </form>
</div>

<script>
    const baseSymbol = '<?= $baseCurrency['symbol'] ?? '$' ?>';

    function addSplitRow() {
        const container = document.getElementById('splitsContainer');
        const newRow = document.createElement('div');
        newRow.className = 'grid grid-3 split-row';
        newRow.style.cssText = 'gap: 0.5rem; margin-bottom: 0.5rem;';

        // Clone the entire select element to preserve optgroups
        const originalSelect = document.querySelector('.split-cat');
        const selectClone = originalSelect.cloneNode(true);
        selectClone.name = "split_category[]";
        selectClone.className = "split-cat";
        selectClone.required = true;
        selectClone.onchange = function () { calculateUnallocated(); };

        newRow.innerHTML = `
            <div class="select-wrapper"></div>
            <input type="number" step="0.01" name="split_amount[]" class="split-amt" placeholder="Amount" required oninput="calculateUnallocated()">
            <div style="display:flex; gap:0.5rem;">
                <input type="text" name="split_notes[]" class="split-note" placeholder="Note" style="flex:1;">
                <button type="button" class="btn btn-sm" style="background:var(--danger); color:white; padding: 0 0.75rem;" onclick="this.parentElement.parentElement.remove(); calculateUnallocated();">×</button>
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

        // Allow a tiny floating-point tolerance
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
                display.textContent = '⚠️ Please select a category for ALL splits';
            } else if (amounts.length === 0) {
                display.textContent = '⚠️ Add at least one split';
            } else if (total <= 0) {
                display.textContent = '⚠️ Total amount must be greater than 0';
            } else {
                display.textContent = `⚠️ ${baseSymbol}${Math.abs(unallocated).toFixed(2)} unallocated`;
            }
        }
    }

    // Attach listeners
    document.getElementById('totalAmount').addEventListener('input', calculateUnallocated);

    // Initial calculation on page load
    document.addEventListener('DOMContentLoaded', () => {
        calculateUnallocated();

        // Fallback: If form submits but PHP redirects with an error, and Toasts fail, 
        // this ensures the PHP session error is visible as a standard alert.
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            console.warn("Transaction failed. Check storage/logs/app.log for details.");
        }
    });
</script>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>