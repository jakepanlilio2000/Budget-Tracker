<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Monthly Review - ' . date('F Y', strtotime($currentMonth . '-01'));
ob_start();
$sym = $baseCurrency['symbol'];
$r = $review;
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Monthly Review</h1>
        <p class="text-secondary">Your financial performance for <?= date('F Y', strtotime($currentMonth . '-01')) ?></p>
    </div>
    <form method="GET" action="<?= url('/monthly-review') ?>" style="display: flex; gap: 0.5rem;">
        <input type="month" name="month" value="<?= e($currentMonth) ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); height: 42px;">
        <button type="submit" class="btn btn-primary">View</button>
    </form>
</div>

<!-- Core Financial Stats -->
<div class="grid grid-3 mb-4">
    <div class="card glass stat-card">
        <div class="stat-icon income"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Income</span>
            <h3 class="sensitive-data"><?= $sym ?><?= number_format($r['total_income'], 2) ?></h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon expense"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Expenses</span>
            <h3 class="sensitive-data"><?= $sym ?><?= number_format($r['total_expense'], 2) ?></h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon" style="background: <?= $r['net_income'] >= 0 ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)' ?>; color: <?= $r['net_income'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Net Cash Flow</span>
            <h3 class="sensitive-data" style="color: <?= $r['net_income'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                <?= $sym ?><?= number_format($r['net_income'], 2) ?>
            </h3>
        </div>
    </div>
</div>

<div class="grid grid-2 mb-4">
    <!-- Highlights -->
    <div class="card glass">
        <h3><i class="fas fa-star" style="color: #fbbf24;"></i> Month Highlights</h3>
        <div class="mt-3" style="display: flex; flex-direction: column; gap: 1rem;">
            <div class="flex-between" style="padding: 0.75rem; background: rgba(239,68,68,0.05); border-radius: 8px; border-left: 3px solid var(--danger);">
                <div>
                    <small class="text-secondary">Biggest Expense</small>
                    <h4 style="margin: 0;"><?= e($r['biggest_expense']['description'] ?: 'No expenses') ?></h4>
                </div>
                <span class="sensitive-data" style="font-weight: bold; color: var(--danger);"><?= $sym ?><?= number_format((float)$r['biggest_expense']['total_amount'], 2) ?></span>
            </div>
            <div class="flex-between" style="padding: 0.75rem; background: rgba(59,130,246,0.05); border-radius: 8px; border-left: 3px solid <?= e($r['top_category']['color']) ?>;">
                <div>
                    <small class="text-secondary">Top Category</small>
                    <h4 style="margin: 0;"><?= e($r['top_category']['name']) ?></h4>
                </div>
                <span class="sensitive-data" style="font-weight: bold; color: var(--accent);"><?= $sym ?><?= number_format((float)$r['top_category']['total'], 2) ?></span>
            </div>
            <div class="flex-between" style="padding: 0.75rem; background: rgba(16,185,129,0.05); border-radius: 8px; border-left: 3px solid var(--success);">
                <div>
                    <small class="text-secondary">Vault Deposits</small>
                    <h4 style="margin: 0;">Saved towards goals</h4>
                </div>
                <span class="sensitive-data" style="font-weight: bold; color: var(--success);"><?= $sym ?><?= number_format($r['monthly_savings'], 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Budget Performance -->
    <div class="card glass">
        <h3><i class="fas fa-chart-pie" style="color: var(--accent);"></i> Budget Performance</h3>
        <?php if ($r['total_budgets'] == 0): ?>
            <p class="text-secondary mt-3">No budgets were set for this month. <a href="<?= url('/budgets') ?>" class="link">Set one up!</a></p>
        <?php else: ?>
            <div class="mt-3" style="text-align: center; margin-bottom: 1.5rem;">
                <h1 style="color: <?= $r['budget_success_rate'] >= 80 ? 'var(--success)' : ($r['budget_success_rate'] >= 50 ? 'var(--accent)' : 'var(--danger)') ?>; margin: 0;">
                    <?= $r['budget_success_rate'] ?>%
                </h1>
                <small class="text-secondary">Success Rate</small>
            </div>
            <div style="background: var(--border-color); border-radius: 99px; height: 10px; overflow: hidden; margin-bottom: 1rem;">
                <div style="width: <?= $r['budget_success_rate'] ?>%; height: 100%; background: <?= $r['budget_success_rate'] >= 80 ? 'var(--success)' : 'var(--danger)' ?>;"></div>
            </div>
            <div class="flex-between text-secondary" style="font-size: 0.9rem;">
                <span><?= $r['total_budgets'] - $r['over_budget_count'] ?> budgets met</span>
                <span><?= $r['over_budget_count'] ?> exceeded</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- AI Insights: Achievements & Warnings -->
<div class="grid grid-2">
    <div class="card glass" style="border-top: 3px solid var(--success);">
        <h3 style="color: var(--success);"><i class="fas fa-trophy"></i> Achievements</h3>
        <?php if (empty($r['achievements'])): ?>
            <p class="text-secondary mt-3">Keep tracking your expenses to unlock achievements!</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($r['achievements'] as $ach): ?>
                    <li style="display: flex; align-items: start; gap: 0.5rem;">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 0.2rem;"></i>
                        <span><?= e($ach) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card glass" style="border-top: 3px solid #f59e0b;">
        <h3 style="color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> Warnings & Advice</h3>
        <?php if (empty($r['warnings'])): ?>
            <p class="text-secondary mt-3">No warnings! Your financial health looks great this month.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($r['warnings'] as $warn): ?>
                    <li style="display: flex; align-items: start; gap: 0.5rem;">
                        <i class="fas fa-bell" style="color: #f59e0b; margin-top: 0.2rem;"></i>
                        <span><?= e($warn) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>