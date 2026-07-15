<?php
$pageTitle = 'Add Account';
ob_start();
?>
<div class="page-header">
    <h1>Add New Account</h1>
</div>

<div class="card glass" style="max-width: 600px;">
    <form method="POST" action="<?= url('/accounts/store') ?>" class="form-stack">
        <?= \App\Core\CSRF::field() ?>

        <div class="form-group">
            <label>Account Name *</label>
            <input type="text" name="name" required placeholder="e.g., Main Checking">
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Account Type *</label>
                <select name="type" required>
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
                <select name="currency_id" required>
                    <?php foreach ($currencies as $curr): ?>
                        <option value="<?= $curr['id'] ?>" <?= $curr['is_base'] ? 'selected' : '' ?>>
                            <?= e($curr['code']) ?> -
                            <?= e($curr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Institution</label>
                <input type="text" name="institution" placeholder="e.g., Chase, PayPal">
            </div>
            <div class="form-group">
                <label>Account Number (Last 4)</label>
                <input type="text" name="account_number" maxlength="4" placeholder="****">
            </div>
        </div>

        <div class="form-group">
            <label>Opening Balance</label>
            <input type="number" step="0.01" name="opening_balance" value="0.00">
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3"></textarea>
        </div>

        <div class="flex-between mt-4">
            <a href="<?= url('/accounts') ?>" class="btn"
                style="background: var(--text-secondary); color: white;">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>