<?php
declare(strict_types=1);

$pageTitle = 'Budgets & Expense Allocation';
ob_start();

$totalBudget = $overview['total_budget'] ?? '0.00';
$totalSpent = $overview['spent_total'] ?? ($overview['total_spent'] ?? '0.00');
$totalRemaining = $overview['remaining_total'] ?? ($overview['total_remaining'] ?? '0.00');
$overallPercent = $overview['overall_percent'] ?? 0.0;
$baseCurrSymbol = $overview['base_currency']['symbol'] ?? '$';
$baseCurrCode = $overview['base_currency']['code'] ?? 'USD';
?>

<!-- Header & Period Filter -->
<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
        <h1 style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-chart-pie" style="color: var(--accent);"></i>
            <span>Monthly Budgets</span>
        </h1>
        <p class="text-secondary" style="font-size: 0.9rem; margin-top: 0.25rem;">
            Arbitrary-precision spending allocations with deterministic month-end boundaries and multi-currency ledger snapshots.
        </p>
    </div>
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <label for="budgetMonth" class="text-secondary" style="font-size: 0.85rem; font-weight: 500;">
            <i class="far fa-calendar-alt"></i> Period:
        </label>
        <input type="month" id="budgetMonth" name="month" value="<?= e($currentMonth) ?>"
            onchange="window.location.href='?month='+this.value" class="btn"
            style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); font-weight: 600; padding: 0.45rem 0.85rem; border-radius: 8px;">
    </div>
</div>

<!-- Financial Summary KPIs -->
<div class="grid grid-3 mb-4" style="gap: 1.25rem;">
    <!-- Total Allocated KPI -->
    <div class="card glass stat-card" style="padding: 1.25rem;">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.12); color: var(--accent);">
            <i class="fas fa-bullseye"></i>
        </div>
        <div class="stat-info" style="flex-grow: 1;">
            <div style="display: flex; align-items: center;">
                <span class="stat-label">Total Allocated</span>
                <span class="fintech-tooltip-container">
                    <span class="fintech-tooltip-icon">?</span>
                    <span class="fintech-tooltip-bubble">Sum of all base budgets plus any surplus carried over from previous months.</span>
                </span>
            </div>
            <h3 class="stat-value" style="font-size: 1.45rem; font-weight: 700; margin-top: 0.35rem;">
                <?= e($baseCurrSymbol) ?><?= number_format((float) $totalBudget, 2) ?>
                <span style="font-size: 0.75rem; font-weight: 400; color: var(--text-secondary);"><?= e($baseCurrCode) ?></span>
            </h3>
        </div>
    </div>

    <!-- Total Spent KPI -->
    <div class="card glass stat-card" style="padding: 1.25rem;">
        <div class="stat-icon expense" style="background: var(--fintech-expense-bg); color: var(--fintech-expense);">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-info" style="flex-grow: 1;">
            <div style="display: flex; align-items: center;">
                <span class="stat-label">Settled Spent</span>
                <span class="fintech-tooltip-container">
                    <span class="fintech-tooltip-icon">?</span>
                    <span class="fintech-tooltip-bubble">Total expenses incurred within this period, converted via snapshotted transaction exchange rates.</span>
                </span>
            </div>
            <h3 class="stat-value" style="font-size: 1.45rem; font-weight: 700; margin-top: 0.35rem; color: var(--fintech-expense);">
                <?= e($baseCurrSymbol) ?><?= number_format((float) $totalSpent, 2) ?>
                <span style="font-size: 0.75rem; font-weight: 400; color: var(--text-secondary);"><?= e($baseCurrCode) ?></span>
            </h3>
        </div>
    </div>

    <!-- Remaining / Overdraft KPI -->
    <?php
    $isNetOver = (float) $totalRemaining < 0;
    ?>
    <div class="card glass stat-card" style="padding: 1.25rem;">
        <div class="stat-icon" style="background: <?= $isNetOver ? 'var(--fintech-danger-bg)' : 'var(--fintech-income-bg)' ?>; color: <?= $isNetOver ? 'var(--fintech-danger)' : 'var(--fintech-income)' ?>;">
            <i class="fas <?= $isNetOver ? 'fa-exclamation-triangle' : 'fa-piggy-bank' ?>"></i>
        </div>
        <div class="stat-info" style="flex-grow: 1;">
            <div style="display: flex; align-items: center;">
                <span class="stat-label"><?= $isNetOver ? 'Deficit Overrun' : 'Net Remaining' ?></span>
                <span class="fintech-tooltip-container">
                    <span class="fintech-tooltip-icon">?</span>
                    <span class="fintech-tooltip-bubble"><?= $isNetOver ? 'Allocations exceeded! Negative headroom requires budget adjustment or expense reconciliation.' : 'Unspent liquidity remaining before hitting configured spending ceilings.' ?></span>
                </span>
            </div>
            <h3 class="stat-value" style="font-size: 1.45rem; font-weight: 700; margin-top: 0.35rem; color: <?= $isNetOver ? 'var(--fintech-danger)' : 'var(--fintech-income)' ?>;">
                <?= e($baseCurrSymbol) ?><?= number_format(abs((float) $totalRemaining), 2) ?>
                <span class="badge-pill <?= $isNetOver ? 'badge-danger' : 'badge-income' ?>" style="margin-left: 0.5rem; vertical-align: middle;">
                    <?= $overallPercent ?>% used
                </span>
            </h3>
        </div>
    </div>
</div>

<!-- Overall Budget Velocity Bar -->
<div class="card glass mb-4" style="padding: 1rem 1.25rem;">
    <div class="flex-between" style="font-size: 0.85rem; margin-bottom: 0.5rem; font-weight: 600;">
        <span class="text-secondary">Overall Portfolio Utilization</span>
        <span><?= $overallPercent ?>% of Monthly Cap</span>
    </div>
    <div class="budget-meter-track" style="height: 12px;">
        <?php
        $overallClass = 'budget-bar-safe';
        if ($overallPercent >= 100) {
            $overallClass = 'budget-bar-overbudget';
        } elseif ($overallPercent >= 90) {
            $overallClass = 'budget-bar-danger';
        } elseif ($overallPercent >= 70) {
            $overallClass = 'budget-bar-caution';
        }
        $clampedOverallWidth = min(100, $overallPercent);
        ?>
        <div class="budget-meter-bar <?= $overallClass ?>" style="width: <?= $clampedOverallWidth ?>%;"></div>
    </div>
</div>

<!-- Filter Chips -->
<div class="filter-chips-bar">
    <span class="text-secondary" style="font-size: 0.8rem; font-weight: 600; margin-right: 0.25rem;">
        <i class="fas fa-filter"></i> Filter:
    </span>
    <button class="filter-chip active" onclick="filterBudgets('all', this)">All Budgets (<?= count($budgets) ?>)</button>
    <button class="filter-chip" onclick="filterBudgets('safe', this)">Safe (&lt;70%)</button>
    <button class="filter-chip" onclick="filterBudgets('caution', this)">Caution (70–90%)</button>
    <button class="filter-chip" onclick="filterBudgets('danger', this)">Critical (&gt;90%)</button>
    <button class="filter-chip" onclick="filterBudgets('over', this)">Overbudget (&gt;100%)</button>
</div>

<div class="grid grid-2" style="gap: 1.5rem; align-items: start;">
    <!-- Budget Progress List -->
    <div class="card glass">
        <div class="flex-between" style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1rem;">
            <h3 style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem;">
                <i class="fas fa-list-check" style="color: var(--accent);"></i>
                <span>Category Breakdown (<?= e($currentMonth) ?>)</span>
            </h3>
            <span class="text-secondary" style="font-size: 0.85rem; font-weight: 500;">
                <?= count($budgets) ?> Categories Tracked
            </span>
        </div>

        <?php if (empty($budgets)): ?>
            <div style="text-align: center; padding: 3rem 1.5rem;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(59, 130, 246, 0.1); color: var(--accent); display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <h4 style="font-size: 1.1rem; margin-bottom: 0.35rem;">No Budgets Configured</h4>
                <p class="text-secondary" style="font-size: 0.9rem; max-width: 320px; margin: 0 auto 1.25rem;">
                    You have not established spending limits for this month. Set your first category budget on the right to start enforcing financial boundaries.
                </p>
            </div>
        <?php else: ?>
            <div id="budgetListContainer" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <?php foreach ($budgets as $budget):
                    $percent = (float) ($budget['percent'] ?? 0.0);
                    $isOver = !empty($budget['is_over_budget']) || $percent > 100.0;
                    $statusType = 'safe';
                    $progressClass = 'budget-bar-safe';

                    if ($percent >= 100.0) {
                        $statusType = 'over';
                        $progressClass = 'budget-bar-overbudget';
                    } elseif ($percent >= 90.0) {
                        $statusType = 'danger';
                        $progressClass = 'budget-bar-danger';
                    } elseif ($percent >= 70.0) {
                        $statusType = 'caution';
                        $progressClass = 'budget-bar-caution';
                    }

                    $barWidth = min(100, $percent);
                    $currSymbol = $budget['currency_symbol'] ?? $baseCurrSymbol;
                    $rolledOver = (float) ($budget['rolled_over_amount'] ?? 0.0);
                    ?>
                    <div class="budgetItem card" data-status="<?= $statusType ?>" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: transform 0.2s ease;">
                        <div class="flex-between" style="margin-bottom: 0.65rem; flex-wrap: wrap; gap: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; background: <?= e($budget['color'] ?? '#3b82f6') ?>22; color: <?= e($budget['color'] ?? '#3b82f6') ?>; font-size: 0.95rem;">
                                    <i class="<?= e($budget['icon'] ?? 'fas fa-tag') ?>"></i>
                                </span>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary);">
                                        <?= e($budget['category_name']) ?>
                                    </div>
                                    <?php if ($rolledOver > 0): ?>
                                        <span class="badge-pill badge-indigo" style="font-size: 0.65rem; padding: 0.1rem 0.45rem;">
                                            <i class="fas fa-share-alt"></i> +<?= e($currSymbol) ?><?= number_format($rolledOver, 2) ?> Rollover
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; font-size: 0.95rem; color: <?= $isOver ? 'var(--fintech-danger)' : 'var(--text-primary)' ?>;">
                                    <?= e($currSymbol) ?><?= number_format((float) $budget['spent_amount'], 2) ?>
                                    <span style="font-weight: 400; font-size: 0.8rem; color: var(--text-secondary);">
                                        / <?= e($currSymbol) ?><?= number_format((float) $budget['effective_budget'], 2) ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.75rem; color: <?= $isOver ? 'var(--fintech-danger)' : 'var(--text-secondary)' ?>; font-weight: 500;">
                                    <?= $isOver ? ('Over by ' . e($currSymbol) . number_format(abs((float) $budget['remaining_amount']), 2)) : (e($currSymbol) . number_format((float) $budget['remaining_amount'], 2) . ' remaining') ?>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar Track -->
                        <div class="budget-meter-track" style="height: 10px;">
                            <div class="budget-meter-bar <?= $progressClass ?>" style="width: <?= $barWidth ?>%;"></div>
                        </div>

                        <div class="flex-between" style="margin-top: 0.45rem; font-size: 0.75rem; color: var(--text-secondary);">
                            <span style="display: flex; align-items: center; gap: 0.25rem;">
                                <?php if ($isOver): ?>
                                    <span style="color: var(--fintech-danger); font-weight: 600;">
                                        <i class="fas fa-exclamation-circle"></i> Overbudget Limit Exceeded
                                    </span>
                                <?php elseif ($percent >= 90): ?>
                                    <span style="color: var(--fintech-danger); font-weight: 600;">
                                        <i class="fas fa-bell"></i> Critical: Above 90% threshold
                                    </span>
                                <?php elseif ($percent >= 70): ?>
                                    <span style="color: var(--fintech-caution); font-weight: 600;">
                                        <i class="fas fa-info-circle"></i> Approaching monthly ceiling
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--fintech-income); font-weight: 600;">
                                        <i class="fas fa-check-circle"></i> Within safe trajectory
                                    </span>
                                <?php endif; ?>
                            </span>
                            <span style="font-weight: 600;"><?= $percent ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Set / Update Budget Form -->
    <div class="card glass">
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
            <h3 style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem;">
                <i class="fas fa-sliders-h" style="color: var(--accent);"></i>
                <span>Configure Budget Limit</span>
            </h3>
            <p class="text-secondary" style="font-size: 0.8rem; margin-top: 0.25rem;">
                Assign ceiling caps per expense category. Existing allocations will be updated.
            </p>
        </div>

        <form method="POST" action="<?= url('/budgets/store') ?>" class="form-stack">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="month" value="<?= e($currentMonth) ?>">

            <div class="form-group mb-3">
                <label style="display: flex; align-items: center; justify-content: space-between; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem;">
                    <span>Expense Category <span style="color: var(--fintech-danger);">*</span></span>
                    <a href="<?= url('/categories') ?>" style="font-size: 0.75rem; color: var(--accent); text-decoration: none;">Manage Categories &rarr;</a>
                </label>
                <select name="category_id" required class="form-control" style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-glass-solid); color: var(--text-primary); font-size: 0.9rem;">
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>">
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem;">
                    <span>Limit Amount (<?= e($baseCurrCode) ?>) <span style="color: var(--fintech-danger);">*</span></span>
                    <span class="fintech-tooltip-container">
                        <span class="fintech-tooltip-icon">?</span>
                        <span class="fintech-tooltip-bubble">Maximum permitted spending for this category. Enforced with arbitrary-precision decimal strings.</span>
                    </span>
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--text-secondary);"><?= e($baseCurrSymbol) ?></span>
                    <input type="text" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" name="amount" required placeholder="0.00" class="form-control" style="width: 100%; padding: 0.65rem 0.85rem 0.65rem 2rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-glass-solid); color: var(--text-primary); font-size: 0.95rem; font-weight: 600;">
                </div>
                <small class="text-secondary" style="font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                    Standard decimal format (e.g. 500.00). Must be greater than 0.00.
                </small>
            </div>

            <div class="form-group mb-4" style="background: rgba(148, 163, 184, 0.08); padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <label style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;">
                    <input type="checkbox" name="carry_over" value="1" style="margin-top: 0.2rem; cursor: pointer;">
                    <div>
                        <span style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary); display: flex; align-items: center;">
                            Enable Rollover Allowance
                            <span class="fintech-tooltip-container">
                                <span class="fintech-tooltip-icon">?</span>
                                <span class="fintech-tooltip-bubble">When enabled, any unspent surplus at the end of the month will automatically roll over to augment the subsequent month's budget.</span>
                            </span>
                        </span>
                        <p class="text-secondary" style="font-size: 0.75rem; margin-top: 0.15rem; line-height: 1.35;">
                            Surplus savings carry forward to augment next month's spending ceiling.
                        </p>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 0.75rem 1.25rem; font-weight: 600; border-radius: 8px; font-size: 0.95rem;">
                <i class="fas fa-save" style="margin-right: 0.35rem;"></i> Save Allocation
            </button>
        </form>
    </div>
</div>

<script>
function filterBudgets(type, btn) {
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');

    const items = document.querySelectorAll('.budgetItem');
    items.forEach(item => {
        const itemStatus = item.getAttribute('data-status');
        if (type === 'all' || itemStatus === type) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>