<?php 
$pageTitle = 'Edit Account';
ob_start(); 
?>
<div class="page-header">
    <h1>Edit Account</h1>
</div>

<div class="card glass" style="max-width: 600px;">
    <form method="POST" action="<?= url('/accounts/update/' . $account['id']) ?>" class="form-stack">
        <?= \App\Core\CSRF::field() ?>
        
        <div class="form-group">
            <label>Account Name *</label>
            <input type="text" name="name" value="<?= e($account['name']) ?>" required>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Account Type *</label>
                <select name="type" required>
                    <option value="bank" <?= $account['type'] === 'bank' ? 'selected' : '' ?>>Bank Account</option>
                    <option value="cash" <?= $account['type'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="credit_card" <?= $account['type'] === 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                    <option value="debit_card" <?= $account['type'] === 'debit_card' ? 'selected' : '' ?>>Debit Card</option>
                    <option value="ewallet" <?= $account['type'] === 'ewallet' ? 'selected' : '' ?>>E-Wallet</option>
                    <option value="digital" <?= $account['type'] === 'digital' ? 'selected' : '' ?>>Digital Wallet</option>
                </select>
            </div>
            <div class="form-group">
                <label>Currency *</label>
                <select name="currency_id" required>
                    <?php foreach ($currencies as $curr): ?>
                        <option value="<?= $curr['id'] ?>" <?= $curr['id'] == $account['currency_id'] ? 'selected' : '' ?>>
                            <?= e($curr['code']) ?> - <?= e($curr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Institution</label>
                <input type="text" name="institution" value="<?= e($account['institution']) ?>" placeholder="e.g., Chase, PayPal">
            </div>
            <div class="form-group">
                <label>Account Number (Last 4)</label>
                <input type="text" name="account_number" value="<?= e($account['account_number']) ?>" maxlength="4" placeholder="****">
            </div>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3"><?= e($account['notes']) ?></textarea>
        </div>

        <div class="flex-between mt-4">
            <a href="<?= url('/accounts') ?>" class="btn" style="background: var(--text-secondary); color: white;">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Account</button>
        </div>
    </form>
</div>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>