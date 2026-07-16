<?php
$pageTitle = 'Transactions';
ob_start();
?>
<div class="page-header flex-between">
    <h1>Transactions</h1>
    <a href="<?= url('/transactions/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> New Transaction</a>
</div>

<div class="card glass">
    <?php if (empty($transactions)): ?>
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-receipt" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <p class="text-secondary">No transactions found.</p>
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
                                <?php if ($txn['is_favorite']): ?><i class="fas fa-star"
                                        style="color: #fbbf24; margin-left: 0.5rem;"></i><?php endif; ?>
                            </td>
                            <td><?= e($txn['account_name']) ?></td>
                            <td style="color: <?= $txn['type'] === 'income' ? 'var(--success)' : 'var(--danger)' ?>">
                                <?= $txn['type'] === 'income' ? '+' : '-' ?>        <?= e($txn['currency_symbol']) ?>        <?= number_format($txn['total_amount'], 2) ?>
                            </td>
                            <td><span class="badge"
                                    style="background: var(--border-color);"><?= ucfirst(e($txn['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>