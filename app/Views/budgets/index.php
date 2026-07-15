<?php
$pageTitle = 'Budgets';
ob_start();
?>
<div class="page-header flex-between">
    <h1>Monthly Budgets</h1>
    <input type="month" name="month" value="<?= e($currentMonth) ?>"
        onchange="window.location.href='?month='+this.value" class="btn"
        style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary);">
</div>

<div class="grid grid-2">
    <!-- Add Budget Form -->
    <div class="card glass">
        <h3>Set New Budget</h3>
        <form method="POST" action="<?= url('/budgets/store') ?>" class="form-stack mt-3">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="month" value="<?= e($currentMonth) ?>">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>">
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Limit Amount</label>
                <input type="number" step="0.01" name="amount" required placeholder="0.00">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Save Budget</button>
        </form>
    </div>

    <!-- Budget Progress List -->
    <div class="card glass">
        <h3>Budget Progress (
            <?= e($currentMonth) ?>)
        </h3>
        <?php if (empty($budgets)): ?>
            <p class="text-secondary mt-3">No budgets set for this month.</p>
        <?php else: ?>
            <div class="mt-3" style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($budgets as $budget):
                    $percentage = $budget['amount'] > 0 ? min(100, ($budget['spent_amount'] / $budget['amount']) * 100) : 0;
                    $isOver = $budget['spent_amount'] > $budget['amount'];
                    ?>
                    <div>
                        <div class="flex-between" style="margin-bottom: 0.25rem;">
                            <span style="font-weight: 600;">
                                <?= e($budget['category_name']) ?>
                            </span>
                            <span style="font-size: 0.9rem; color: <?= $isOver ? 'var(--danger)' : 'var(--text-secondary)' ?>">
                                <?= number_format($budget['spent_amount'], 2) ?> /
                                <?= number_format($budget['amount'], 2) ?>
                            </span>
                        </div>
                        <div style="background: var(--border-color); border-radius: 99px; height: 8px; overflow: hidden;">
                            <div
                                style="width: <?= $percentage ?>%; height: 100%; background: <?= $isOver ? 'var(--danger)' : $budget['color'] ?>; transition: width 0.5s ease;">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>