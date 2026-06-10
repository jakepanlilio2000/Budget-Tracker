<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-magnifying-glass-chart" style="color: var(--accent-blue); margin-right: 8px;"></i> Macro Insights</h1>
        <p style="color: var(--text-secondary);">Year-in-review analytics and cashflow trends.</p>
    </div>
    <div class="top-bar-right">
        <div class="custom-select-wrapper">
            <select id="year-selector" data-pid="<?= htmlspecialchars((string)$profile['id']) ?>">
                <?php for($y = date('Y')-1; $y <= date('Y')+2; $y++): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
</header>

<div class="summary-grid">
    <div class="card summary-card inflow-card">
        <div class="card-meta">
            <span class="icon-indicator"><i class="fa-solid fa-sack-dollar"></i></span>
            <span>Yearly Inflow</span>
        </div>
        <h3 class="inflow" style="display: flex; gap: 4px; align-items: baseline;">
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount" data-full-val="<?= (float)$yearlyInflow ?>"><?= number_format($yearlyInflow, 2) ?></span>
        </h3>
    </div>
    
    <div class="card summary-card outflow-card">
        <div class="card-meta">
            <span class="icon-indicator"><i class="fa-solid fa-money-bill-wave"></i></span>
            <span>Yearly Outflow</span>
        </div>
        <h3 class="outflow" style="display: flex; gap: 4px; align-items: baseline;">
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount" data-full-val="<?= (float)$yearlyOutflow ?>"><?= number_format($yearlyOutflow, 2) ?></span>
        </h3>
    </div>
    
    <div class="card summary-card <?= ($yearlyInflow - $yearlyOutflow) >= 0 ? 'positive' : 'negative' ?>">
        <div class="card-meta">
            <span class="icon-indicator"><i class="fa-solid fa-arrow-trend-up"></i></span>
            <span>Net Growth</span>
        </div>
        <h3 style="display: flex; gap: 4px; align-items: baseline;">
            <span class="sign-label"><?= ($yearlyInflow - $yearlyOutflow) >= 0 ? '+' : '' ?></span>
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount" data-full-val="<?= (float)($yearlyInflow - $yearlyOutflow) ?>"><?= number_format(abs($yearlyInflow - $yearlyOutflow), 2) ?></span>
        </h3>
    </div>
    
    <div class="card summary-card" style="border-top: 4px solid var(--accent-blue);">
        <div class="card-meta">
            <span class="icon-indicator" style="color: var(--accent-blue);"><i class="fa-solid fa-piggy-bank"></i></span>
            <span>Savings Rate</span>
        </div>
        <h3 style="color: var(--accent-blue); display: flex; gap: 4px; align-items: baseline;">
            <span class="amount" data-full-val="<?= (float)$savingsRate ?>"><?= number_format($savingsRate, 1) ?></span>
            <span>%</span>
        </h3>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
    <div class="card" style="padding: 24px;">
        <h3 style="margin-bottom: 24px;"><i class="fa-solid fa-chart-column" style="color: var(--accent-blue); margin-right: 8px;"></i> Month-Over-Month Cashflow</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="cashflowChart" style="width: 100%; height: 100%;"></canvas>
        </div>
    </div>

    <div class="card" style="padding: 24px;">
        <h3 style="margin-bottom: 24px;"><i class="fa-solid fa-chart-pie" style="color: var(--accent-blue); margin-right: 8px;"></i> Expense Allocation (<?= htmlspecialchars((string)$year) ?>)</h3>
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