<?php
$pageTitle = 'Accounts';
ob_start();
?>
<div class="page-header flex-between">
    <h1>Financial Accounts</h1>
    <a href="<?= url('/accounts/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Account</a>
</div>

<div class="grid grid-2">
    <?php if (empty($accounts)): ?>
        <div class="card glass text-center" style="grid-column: 1 / -1;">
            <i class="fas fa-university" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <h3>No Accounts Yet</h3>
            <p class="text-secondary">Start by adding your first bank account, cash, or e-wallet.</p>
            <a href="<?= url('/accounts/create') ?>" class="btn btn-primary mt-3">Create Account</a>
        </div>
    <?php else: ?>
        <?php foreach ($accounts as $acc): ?>
            <div class="card glass account-card">
                <div class="flex-between">
                    <div>
                        <h3><?= e($acc['name']) ?></h3>
                        <span class="badge badge-<?= e($acc['type']) ?>"><?= ucfirst(e($acc['type'])) ?></span>
                        <span class="text-secondary ml-2"><?= e($acc['institution'] ?: 'Personal') ?></span>
                    </div>
                    <div class="text-right">
                        <h2 class="sensitive-data" style="color: var(--accent);">
                            <?= e($acc['currency_symbol']) ?>        <?= number_format($acc['current_balance'], 2) ?></h2>
                        <span class="text-secondary" style="font-size: 0.8rem;"><?= e($acc['currency_code']) ?></span>
                    </div>
                </div>
                            <div class="mt-3 pt-3" style="border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.5rem;">
                <a href="<?= url('/accounts/edit/' . $acc['id']) ?>" class="btn btn-sm" style="background: var(--accent); color: white;"><i class="fas fa-edit"></i> Edit</a>
                <form method="POST" action="<?= url('/accounts/delete/' . $acc['id']) ?>" onsubmit="return confirm('Archive this account?');" style="display:inline;">
                    <?= \App\Core\CSRF::field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-sm" style="color: var(--danger); background: transparent;"><i class="fas fa-trash"></i></button>
                </form>
            </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>