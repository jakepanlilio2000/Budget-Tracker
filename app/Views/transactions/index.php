<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Models\CurrencyService;

$pageTitle = 'Transactions';
ob_start();
$baseSym = $baseCurrency['symbol'] ?? '$';
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
        <h1 style="margin: 0 0 0.25rem 0;">Transactions</h1>
        <p class="text-secondary" style="margin: 0; font-size: 0.9rem;">
            Manage, filter, and export granular ledger records with multi-currency snapshots.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <button class="btn btn-primary" onclick="openTxnModal()">
            <i class="fas fa-plus"></i> New Transaction
        </button>
    </div>
</div>

<!-- Quick Presets & Export Actions Bar -->
<div class="card glass" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <div class="flex-between" style="flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="fas fa-filter"></i> Quick Presets:
            </span>
            <a href="<?= url('/transactions') ?>" class="btn btn-sm btn-outline" style="border-radius: 20px; font-size: 0.8rem;">All Records</a>
            <button type="button" class="btn btn-sm btn-outline" onclick="applyPresetExport('current_month')" style="border-radius: 20px; font-size: 0.8rem;">Current Month</button>
            <button type="button" class="btn btn-sm btn-outline" onclick="applyPresetExport('previous_quarter')" style="border-radius: 20px; font-size: 0.8rem;">Previous Quarter</button>
            <button type="button" class="btn btn-sm btn-outline" onclick="applyPresetExport('ytd')" style="border-radius: 20px; font-size: 0.8rem;">Year-to-Date (YTD)</button>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="<?= url('/settings/backup?format=stream_csv') ?>" class="btn btn-sm btn-outline" title="Stream all transactions to CSV">
                <i class="fas fa-file-csv"></i> Export All CSV
            </a>
            <a href="<?= url('/reports/export-pdf') ?>" class="btn btn-sm btn-outline" title="Download Executive Statement PDF">
                <i class="fas fa-file-pdf"></i> Executive Statement
            </a>
        </div>
    </div>
</div>

<!-- Sticky Bulk Actions Floating Bar (appears when 1 or more items are selected) -->
<div id="bulkActionBar" style="display: none; position: sticky; top: 1rem; z-index: 100; margin-bottom: 1.5rem; background: var(--bg-surface, #ffffff); border: 2px solid var(--primary, #2563eb); border-radius: 12px; padding: 0.75rem 1.25rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15), 0 8px 10px -6px rgba(0,0,0,0.1);">
    <div class="flex-between" style="flex-wrap: wrap; gap: 1rem; align-items: center;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span class="badge" id="selectedCountBadge" style="background: var(--primary, #2563eb); color: #fff; font-size: 0.85rem; padding: 0.35rem 0.65rem; border-radius: 9999px;">
                0
            </span>
            <span style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary, #0f172a);">
                transactions selected
            </span>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-sm btn-primary" onclick="submitBulkExport('csv')">
                <i class="fas fa-file-csv"></i> Export Selected to CSV
            </button>
            <button type="button" class="btn btn-sm" style="background: #0f172a; color: white;" onclick="submitBulkExport('pdf')">
                <i class="fas fa-file-pdf"></i> Export Selected to PDF
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="deselectAll()">
                Deselect All
            </button>
        </div>
    </div>
</div>

<!-- Transactions Data Table Card -->
<div class="card glass">
    <?php if (empty($transactions)): ?>
        <div class="text-center" style="padding: 3rem 1.5rem;">
            <i class="fas fa-receipt" style="font-size: 3.5rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <h3 style="margin-bottom: 0.5rem;">No transactions recorded</h3>
            <p class="text-secondary" style="max-width: 450px; margin: 0 auto 1.5rem auto;">
                Get started by recording your income, everyday expenses, or account-to-account transfers.
            </p>
            <button class="btn btn-primary" onclick="openTxnModal()">
                <i class="fas fa-plus"></i> Record First Transaction
            </button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center; vertical-align: middle;">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" style="cursor: pointer; width: 17px; height: 17px;" title="Select / Deselect All">
                        </th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Account</th>
                        <th>Category</th>
                        <th style="text-align: right;">Amount</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center; width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr id="txn-row-<?= (int) $txn['id'] ?>">
                            <td style="text-align: center; vertical-align: middle;">
                                <input type="checkbox" class="txn-select-cb" value="<?= (int) $txn['id'] ?>" onchange="onTxnSelectChange()" style="cursor: pointer; width: 17px; height: 17px;">
                            </td>
                            <td style="font-variant-numeric: tabular-nums; white-space: nowrap; color: var(--text-secondary); font-size: 0.9rem;">
                                <?= e(date('M d, Y', strtotime($txn['transaction_date']))) ?>
                            </td>
                            <td>
                                <strong style="color: var(--text-primary);"><?= e($txn['description'] ?: 'No description') ?></strong>
                                <?php if (!empty($txn['is_favorite'])): ?>
                                    <i class="fas fa-star" style="color: #fbbf24; margin-left: 0.35rem;" title="Favorite"></i>
                                <?php endif; ?>
                                <?php if (!empty($txn['notes'])): ?>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= e($txn['notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(0,0,0,0.06); color: var(--text-primary); font-size: 0.8rem;">
                                    <i class="fas fa-wallet" style="margin-right: 3px;"></i> <?= e($txn['account_name'] ?? 'Primary') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(37,99,235,0.08); color: var(--primary, #2563eb); font-size: 0.8rem;">
                                    <?= e($txn['category_name'] ?? 'General') ?>
                                </span>
                            </td>
                            <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: <?= $txn['type'] === 'income' ? 'var(--success, #16a34a)' : ($txn['type'] === 'transfer' ? 'var(--primary, #2563eb)' : 'var(--danger, #dc2626)') ?>;">
                                <?= $txn['type'] === 'income' ? '+' : ($txn['type'] === 'transfer' ? '⇄ ' : '-') ?>
                                <?= e($txn['currency_symbol'] ?? '$') ?><?= number_format((float) $txn['total_amount'], 2) ?>
                            </td>
                            <td style="text-align: center;">
                                <?php
                                $statusStyle = match($txn['status'] ?? 'posted') {
                                    'posted' => 'background: rgba(22,163,74,0.12); color: #15803d; border: 1px solid rgba(22,163,74,0.2);',
                                    'draft' => 'background: rgba(245,158,11,0.12); color: #b45309; border: 1px solid rgba(245,158,11,0.2);',
                                    default => 'background: rgba(100,116,139,0.12); color: #475569; border: 1px solid rgba(100,116,139,0.2);'
                                };
                                ?>
                                <span class="badge" style="<?= $statusStyle ?> font-size: 0.75rem; text-transform: capitalize;">
                                    <?= e($txn['status'] ?? 'posted') ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <form method="POST" action="<?= url('/transactions/reverse/' . (int) $txn['id']) ?>" style="display: inline;" onsubmit="return confirm('Reverse this transaction? Balance will be rolled back and gamification rewards adjusted.');">
                                    <?= \App\Core\CSRF::field() ?>
                                    <button type="submit" class="btn-icon" style="color: var(--danger); background: transparent; border: none; cursor: pointer; padding: 4px;" title="Reverse Transaction">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Hidden Form for Bulk Action Submission -->
<form id="bulkExportForm" method="POST" action="<?= url('/transactions/export-selected') ?>" style="display: none;">
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="format" id="bulkExportFormat" value="csv">
    <div id="bulkExportIdContainer"></div>
</form>

<!-- ========================================== -->
<!-- TRANSACTION CREATE MODAL (Fixed UX & Stacking) -->
<!-- ========================================== -->
<div id="txnModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 1rem;" onclick="if(event.target===this) closeTxnModal()">
    <div class="modal-content glass" role="dialog" aria-modal="true" aria-labelledby="modalTitle" style="background: var(--bg-surface, #ffffff); border-radius: 16px; padding: 1.75rem; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color);">
        <div class="flex-between" style="margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            <h3 id="modalTitle" style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-primary);">New Transaction</h3>
            <button type="button" class="btn-icon" onclick="closeTxnModal()" style="font-size: 1.25rem; border: none; background: transparent; cursor: pointer; color: var(--text-secondary);" title="Close (Esc)">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('/transactions/store') ?>" class="form-stack" id="txnForm" onsubmit="return handleFormSubmit(this)">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="client_mutation_id" id="clientMutationId" value="">

            <div class="grid grid-2" style="gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Transaction Type</label>
                    <select name="type" id="txnType" required onchange="updateCategoryOptions()" style="width: 100%; padding: 0.65rem; border-radius: 8px; border: 1px solid var(--border-color);">
                        <option value="expense" selected>Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Date</label>
                    <input type="date" name="transaction_date" id="txnDate" value="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 0.65rem; border-radius: 8px; border: 1px solid var(--border-color);">
                </div>
            </div>

            <div class="grid grid-2" style="gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Source Account</label>
                    <select name="account_id" id="txnAccount" required style="width: 100%; padding: 0.65rem; border-radius: 8px; border: 1px solid var(--border-color);">
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?> (<?= e($acc['currency_symbol'] ?? '$') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Total Amount</label>
                    <input type="number" step="0.01" min="0.01" name="total_amount" id="totalAmount" value="0.00" required oninput="calculateUnallocated()" style="width: 100%; padding: 0.65rem; border-radius: 8px; border: 1px solid var(--border-color); font-variant-numeric: tabular-nums;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Description</label>
                <input type="text" name="description" id="txnDescription" placeholder="e.g., Grocery Shopping, Monthly Salary" required style="width: 100%; padding: 0.65rem; border-radius: 8px; border: 1px solid var(--border-color);">
            </div>

            <!-- Split Transactions Section -->
            <div class="form-group" style="background: rgba(0,0,0,0.02); padding: 1rem; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 1rem;">
                <div class="flex-between" style="margin-bottom: 0.75rem;">
                    <label style="margin: 0; font-weight: 600;">Splits & Allocations</label>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addSplitRow()">
                        <i class="fas fa-plus"></i> Add Split
                    </button>
                </div>

                <div id="splitsContainer">
                    <div class="grid grid-3 split-row" style="gap: 0.5rem; margin-bottom: 0.5rem;">
                        <select name="split_category[]" class="split-cat" required onchange="calculateUnallocated()" style="padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border-color);">
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
                        <input type="number" step="0.01" min="0.01" name="split_amount[]" class="split-amt" placeholder="Amount" required oninput="calculateUnallocated()" style="padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border-color); font-variant-numeric: tabular-nums;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" name="split_notes[]" class="split-note" placeholder="Notes (optional)" style="flex: 1; padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border-color);">
                        </div>
                    </div>
                </div>

                <div class="flex-between mt-3" style="font-size: 0.9rem; font-weight: 600; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                    <span>Allocation Balance:</span>
                    <span id="unallocatedDisplay" style="color: var(--danger); font-variant-numeric: tabular-nums;"><?= $baseSym ?>0.00</span>
                </div>
                <input type="hidden" name="currency_id" value="<?= $baseCurrency['id'] ?? 1 ?>">
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Additional Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional audit memo or receipt link..." style="width: 100%; padding: 0.65rem; border-radius: 8px; border: 1px solid var(--border-color);"></textarea>
            </div>

            <div class="flex-between" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="closeTxnModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <i class="fas fa-check"></i> Save Transaction
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const baseSymbol = '<?= $baseSym ?>';

    // --- Modal Focus Trap & Overflow Prevention ---
    function openTxnModal() {
        const modal = document.getElementById('txnModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Lock background scroll

        // Idempotency token generation
        document.getElementById('clientMutationId').value = 'mut_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        // Reset form
        document.getElementById('txnForm').reset();
        document.getElementById('txnDate').value = '<?= date('Y-m-d') ?>';
        document.getElementById('totalAmount').value = '0.00';

        const container = document.getElementById('splitsContainer');
        container.innerHTML = container.firstElementChild.outerHTML;
        const firstRow = container.querySelector('.split-row');
        firstRow.querySelector('.split-amt').oninput = calculateUnallocated;
        firstRow.querySelector('.split-cat').onchange = calculateUnallocated;

        updateCategoryOptions();
        calculateUnallocated();

        setTimeout(() => document.getElementById('txnDescription').focus(), 50);
    }

    function closeTxnModal() {
        document.getElementById('txnModal').style.display = 'none';
        document.body.style.overflow = ''; // Unlock background scroll
    }

    // Escape Key Listener
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('txnModal');
            if (modal && modal.style.display !== 'none') {
                closeTxnModal();
            }
        }
    });

    // Double Submission Guard
    function handleFormSubmit(form) {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn.disabled) {
            return false;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Committing Ledger...';
        return true;
    }

    function updateCategoryOptions() {
        const type = document.getElementById('txnType').value;
        const selects = document.querySelectorAll('.split-cat');

        selects.forEach(select => {
            const incomeGroup = select.querySelector('optgroup[label="Income"]');
            const expenseGroup = select.querySelector('optgroup[label="Expense"]');

            if (incomeGroup && expenseGroup) {
                if (type === 'income') {
                    incomeGroup.style.display = 'block';
                    expenseGroup.style.display = 'none';
                } else {
                    incomeGroup.style.display = 'none';
                    expenseGroup.style.display = 'block';
                }
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
            <input type="number" step="0.01" min="0.01" name="split_amount[]" class="split-amt" placeholder="Amount" required oninput="calculateUnallocated()" style="padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border-color); font-variant-numeric: tabular-nums;">
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="text" name="split_notes[]" class="split-note" placeholder="Notes" style="flex: 1; padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border-color);">
                <button type="button" class="btn btn-sm" style="background: var(--danger, #dc2626); color: white; height: 36px; padding: 0 0.6rem;" onclick="this.parentElement.parentElement.remove(); calculateUnallocated();">✕</button>
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

        if (Math.abs(unallocated) < 0.005 && total > 0 && allCategoriesSelected && amounts.length > 0) {
            display.style.color = 'var(--success, #16a34a)';
            display.textContent = baseSymbol + '0.00 (Balanced ✓)';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            display.style.color = 'var(--danger, #dc2626)';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';

            if (!allCategoriesSelected) {
                display.textContent = '⚠️ Please select a category for each split';
            } else if (amounts.length === 0) {
                display.textContent = '⚠️ Add at least one split item';
            } else if (total <= 0) {
                display.textContent = '⚠️ Total amount must be strictly > 0.00';
            } else {
                display.textContent = `⚠️ ${baseSymbol}${Math.abs(unallocated).toFixed(2)} unallocated`;
            }
        }
    }

    // --- Multi-Select & Bulk Actions Engine ---
    function toggleSelectAll(masterCb) {
        const rowCheckboxes = document.querySelectorAll('.txn-select-cb');
        rowCheckboxes.forEach(cb => {
            cb.checked = masterCb.checked;
        });
        updateBulkToolbar();
    }

    function onTxnSelectChange() {
        const rowCheckboxes = document.querySelectorAll('.txn-select-cb');
        const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
        const anyChecked = Array.from(rowCheckboxes).some(cb => cb.checked);

        const masterCb = document.getElementById('selectAllCheckbox');
        if (masterCb) {
            masterCb.checked = allChecked;
            masterCb.indeterminate = (!allChecked && anyChecked);
        }
        updateBulkToolbar();
    }

    function updateBulkToolbar() {
        const selected = document.querySelectorAll('.txn-select-cb:checked');
        const toolbar = document.getElementById('bulkActionBar');
        const badge = document.getElementById('selectedCountBadge');

        if (selected.length > 0) {
            badge.textContent = selected.length;
            toolbar.style.display = 'block';
        } else {
            toolbar.style.display = 'none';
        }
    }

    function deselectAll() {
        const masterCb = document.getElementById('selectAllCheckbox');
        if (masterCb) {
            masterCb.checked = false;
            masterCb.indeterminate = false;
        }
        document.querySelectorAll('.txn-select-cb').forEach(cb => cb.checked = false);
        updateBulkToolbar();
    }

    function submitBulkExport(format) {
        const selected = document.querySelectorAll('.txn-select-cb:checked');
        if (selected.length === 0) {
            alert('Please select at least one transaction to export.');
            return;
        }

        const form = document.getElementById('bulkExportForm');
        const container = document.getElementById('bulkExportIdContainer');
        container.innerHTML = '';

        document.getElementById('bulkExportFormat').value = format;

        selected.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });

        form.submit();
    }

    function applyPresetExport(presetName) {
        window.location.href = '<?= url('/reports/export-csv') ?>?preset=' + encodeURIComponent(presetName);
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateCategoryOptions();
    });
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>