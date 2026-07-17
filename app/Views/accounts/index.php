<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Financial Accounts';
ob_start();
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <h1>Financial Accounts</h1>
    <button class="btn btn-primary" onclick="openAccountModal('create')">
        <i class="fas fa-plus"></i> Add Account
    </button>
</div>

<div class="grid grid-2">
    <?php if (empty($accounts)): ?>
        <div class="card glass text-center" style="grid-column: 1 / -1; padding: 3rem;">
            <i class="fas fa-university" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <h3>No Accounts Yet</h3>
            <p class="text-secondary">Start by adding your first bank account, cash, or e-wallet.</p>
            <button class="btn btn-primary mt-3" onclick="openAccountModal('create')">Create Account</button>
        </div>
    <?php else: ?>
        <?php foreach ($accounts as $acc): ?>
            <div class="card glass account-card">
                <div class="flex-between" style="flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h3><?= e($acc['name']) ?></h3>
                        <span
                            class="badge badge-<?= e($acc['type']) ?>"><?= ucfirst(str_replace('_', ' ', e($acc['type']))) ?></span>
                        <span class="text-secondary ml-2"><?= e($acc['institution'] ?: 'Personal') ?></span>
                    </div>
                    <div class="text-right">
                        <h2 class="sensitive-data" style="color: var(--accent); margin: 0;">
                            <?= e($acc['currency_symbol']) ?>         <?= number_format((float) $acc['current_balance'], 2) ?>
                        </h2>
                        <span class="text-secondary"
                            style="font-size: 0.8rem;"><?= e($acc['currency_code'] ?? $acc['currency_symbol']) ?></span>
                    </div>
                </div>

                <div class="mt-3 pt-3"
                    style="border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="btn btn-sm" style="background: #f59e0b; color: white;"
                        onclick="openAdjustModal(<?= $acc['id'] ?>, '<?= e($acc['name']) ?>', <?= (float) $acc['current_balance'] ?>, '<?= e($acc['currency_symbol']) ?>')">
                        <i class="fas fa-sliders-h"></i> Adjust
                    </button>
                    <button type="button" class="btn btn-sm" style="background: var(--accent); color: white;"
                        onclick="openAccountModal('edit', <?= htmlspecialchars(json_encode($acc), ENT_QUOTES, 'UTF-8') ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" action="<?= url('/accounts/delete/' . $acc['id']) ?>"
                        onsubmit="return confirm('Archive this account?');" style="display:inline;">
                        <?= \App\Core\CSRF::field() ?>
                        <button type="submit" class="btn btn-sm" style="color: var(--danger); background: transparent;"
                            title="Archive">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- ACCOUNT CREATE / EDIT MODAL                -->
<!-- ========================================== -->
<div id="accountModal" class="modal-overlay" style="display: none;"
    onclick="if(event.target===this) closeAccountModal()">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 600px;">
        <div class="flex-between"
            style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            <h3 id="accountModalTitle" style="margin:0;">Add Account</h3>
            <button class="btn-icon" onclick="closeAccountModal()" style="font-size: 1.2rem;"><i
                    class="fas fa-times"></i></button>
        </div>

        <form method="POST" action="" id="accountForm" class="form-stack">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="id" id="accountId" value="">

            <div class="form-group">
                <label>Account Name *</label>
                <input type="text" name="name" id="accName" required placeholder="e.g., Main Checking">
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label>Account Type *</label>
                    <select name="type" id="accType" required>
                        <option value="bank">Bank Account</option>
                        <option value="cash">Cash</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="ewallet">E-Wallet</option>
                        <option value="digital">Digital Wallet</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Currency *</label>
                    <select name="currency_id" id="accCurrency" required>
                        <?php foreach ($currencies as $curr): ?>
                            <option value="<?= $curr['id'] ?>"><?= e($curr['code']) ?> - <?= e($curr['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label>Institution</label>
                    <input type="text" name="institution" id="accInstitution" placeholder="e.g., Chase, PayPal">
                </div>
                <div class="form-group">
                    <label>Account Number (Last 4)</label>
                    <input type="text" name="account_number" id="accNumber" maxlength="4" placeholder="****">
                </div>
            </div>

            <div class="form-group">
                <label>Opening Balance</label>
                <input type="number" step="0.01" name="opening_balance" id="accOpeningBalance" value="0.00">
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" id="accNotes" rows="3"></textarea>
            </div>

            <div class="flex-between mt-4" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button type="button" class="btn" style="background: var(--text-secondary); color: white;"
                    onclick="closeAccountModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="accountSubmitBtn">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- ADJUST BALANCE MODAL                       -->
<!-- ========================================== -->
<div id="adjustModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this) closeAdjustModal()">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 500px;">
        <div class="flex-between"
            style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            <h3 style="margin:0;">Adjust Balance</h3>
            <button class="btn-icon" onclick="closeAdjustModal()" style="font-size: 1.2rem;"><i
                    class="fas fa-times"></i></button>
        </div>

        <form method="POST" action="" id="adjustForm" class="form-stack">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="id" id="adjustId" value="">

            <p class="text-secondary" style="font-size: 0.9rem; margin-bottom: 1rem;">
                This will create an adjustment transaction to preserve your audit trail.
            </p>

            <div class="grid grid-2">
                <div class="form-group">
                    <label>Account</label>
                    <input type="text" id="adjustAccountName" readonly
                        style="background: rgba(0,0,0,0.05); color: var(--text-secondary);">
                </div>
                <div class="form-group">
                    <label>Current Balance</label>
                    <input type="text" id="adjustCurrentBalance" readonly
                        style="background: rgba(0,0,0,0.05); color: var(--text-secondary); font-weight: bold;">
                </div>
            </div>

            <div class="form-group">
                <label>New Balance *</label>
                <input type="number" step="0.01" name="new_balance" id="adjustNewBalance" required
                    oninput="calculateDifference()">
            </div>

            <div class="form-group">
                <label>Difference</label>
                <input type="text" id="adjustDifference" readonly
                    style="font-weight: bold; font-size: 1.1rem; background: rgba(0,0,0,0.05);">
                <small class="text-secondary">This amount will be recorded as an income or expense transaction.</small>
            </div>

            <div class="form-group">
                <label>Reason for Adjustment *</label>
                <textarea name="reason" rows="3" placeholder="e.g., Correcting bank statement discrepancy..."
                    required></textarea>
            </div>

            <div class="flex-between mt-4" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button type="button" class="btn" style="background: var(--text-secondary); color: white;"
                    onclick="closeAdjustModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script>
    let adjustCurrentVal = 0;
    let adjustCurrencySym = '$';

    function openAccountModal(mode, data = null) {
        const modal = document.getElementById('accountModal');
        const form = document.getElementById('accountForm');
        const title = document.getElementById('accountModalTitle');
        const btn = document.getElementById('accountSubmitBtn');

        form.reset();

        if (mode === 'create') {
            title.textContent = 'Add Account';
            form.action = '<?= url('/accounts/store') ?>';
            document.getElementById('accountId').value = '';
            btn.textContent = 'Create Account';
        } else if (mode === 'edit' && data) {
            title.textContent = 'Edit Account';
            form.action = '<?= url('/accounts/update/') ?>' + data.id;
            document.getElementById('accountId').value = data.id;

            // Populate fields
            document.getElementById('accName').value = data.name;
            document.getElementById('accType').value = data.type;
            document.getElementById('accCurrency').value = data.currency_id;
            document.getElementById('accInstitution').value = data.institution || '';
            document.getElementById('accNumber').value = data.account_number || '';
            document.getElementById('accOpeningBalance').value = data.current_balance; // Or opening_balance if tracked separately
            document.getElementById('accNotes').value = data.notes || '';

            btn.textContent = 'Update Account';
        }

        modal.style.display = 'flex';
    }

    function closeAccountModal() {
        document.getElementById('accountModal').style.display = 'none';
    }

    function openAdjustModal(id, name, currentBalance, currencySymbol) {
        const modal = document.getElementById('adjustModal');
        const form = document.getElementById('adjustForm');

        adjustCurrentVal = currentBalance;
        adjustCurrencySym = currencySymbol;

        form.action = '<?= url('/accounts/process-adjustment/') ?>' + id;
        document.getElementById('adjustId').value = id;
        document.getElementById('adjustAccountName').value = name;
        document.getElementById('adjustCurrentBalance').value = currencySymbol + currentBalance.toFixed(2);
        document.getElementById('adjustNewBalance').value = currentBalance;
        document.getElementById('adjustDifference').value = 'No change';
        document.getElementById('adjustDifference').style.color = 'var(--text-secondary)';

        modal.style.display = 'flex';
    }

    function closeAdjustModal() {
        document.getElementById('adjustModal').style.display = 'none';
    }

    function calculateDifference() {
        const newVal = parseFloat(document.getElementById('adjustNewBalance').value) || 0;
        const diff = newVal - adjustCurrentVal;
        const display = document.getElementById('adjustDifference');

        if (diff === 0) {
            display.value = 'No change';
            display.style.color = 'var(--text-secondary)';
        } else if (diff > 0) {
            display.value = '+' + adjustCurrencySym + Math.abs(diff).toFixed(2) + ' (Income)';
            display.style.color = 'var(--success)';
        } else {
            display.value = '-' + adjustCurrencySym + Math.abs(diff).toFixed(2) + ' (Expense)';
            display.style.color = 'var(--danger)';
        }
    }
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>