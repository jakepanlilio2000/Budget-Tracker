<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>📈 Macro Insights</h1>
        <p style="color: var(--text-secondary);">Year-in-review analytics and cashflow trends.</p>
    </div>
    <div class="top-bar-right">
        <select id="year-selector" data-pid="<?= $profile['id'] ?>">
            <?php for($y = date('Y')-1; $y <= date('Y')+2; $y++): ?>
                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
</header>

<div class="summary-grid">
    <div class="card summary-card">
        <span>Yearly Inflow</span>
        <h3 class="amount inflow"><?= $profile['currency'] ?> <?= number_format($yearlyInflow, 2) ?></h3>
    </div>
    <div class="card summary-card">
        <span>Yearly Outflow</span>
        <h3 class="amount outflow"><?= $profile['currency'] ?> <?= number_format($yearlyOutflow, 2) ?></h3>
    </div>
    <div class="card summary-card <?= ($yearlyInflow - $yearlyOutflow) >= 0 ? 'positive' : 'negative' ?>">
        <span>Net Growth</span>
        <h3 class="amount"><?= ($yearlyInflow - $yearlyOutflow) >= 0 ? '+' : '' ?><?= $profile['currency'] ?> <?= number_format($yearlyInflow - $yearlyOutflow, 2) ?></h3>
    </div>
    <div class="card summary-card" style="border-top: 3px solid var(--accent-blue);">
        <span>Savings Rate</span>
        <h3 class="amount" style="color: var(--accent-blue);"><?= number_format($savingsRate, 1) ?>%</h3>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
    <div class="card" style="padding: 24px;">
        <h3 style="margin-bottom: 24px;">Month-Over-Month Cashflow</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="cashflowChart" style="width: 100%; height: 100%;"></canvas>
        </div>
    </div>

    <div class="card" style="padding: 24px;">
        <h3 style="margin-bottom: 24px;">Expense Allocation (<?= $year ?>)</h3>
        <div style="position: relative; height: 300px;">
            <?php if(empty($categoryData)): ?>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: var(--text-muted);">No expense data available</div>
            <?php else: ?>
                <canvas id="expenseChart" style="width: 100%; height: 100%;"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    window.insightsCashflowData = {
        inflow: <?= json_encode($chartInflow ?? []) ?>,
        outflow: <?= json_encode($chartOutflow ?? []) ?>
    };
    <?php if(!empty($categoryData)): ?>
    window.insightsExpenseData = {
        labels: <?= json_encode(array_column($categoryData, 'name')) ?>,
        data: <?= json_encode(array_column($categoryData, 'total')) ?>,
        colors: <?= json_encode(array_column($categoryData, 'color')) ?>
    };
    <?php else: ?>
    window.insightsExpenseData = undefined;
    <?php endif; ?>

    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();
</script>