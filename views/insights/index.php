<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

<!-- High-level KPI Cards -->
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
    
    <!-- Cashflow Trend Chart -->
    <div class="card" style="padding: 24px;">
        <h3 style="margin-bottom: 24px;">Month-Over-Month Cashflow</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="cashflowChart" style="width: 100%; height: 100%;"></canvas>
        </div>
    </div>

    <!-- Expense Breakdown Chart -->
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
document.addEventListener('DOMContentLoaded', () => {
    // Year Selector logic
    const yearSelector = document.getElementById('year-selector');
    if (yearSelector) {
        yearSelector.addEventListener('change', (e) => {
            window.location.href = `<?= $basePath ?>/insights/${e.target.dataset.pid}?year=${e.target.value}`;
        });
    }

    // Chart styles extracted from CSS variables for dynamic integration
    const style = getComputedStyle(document.body);
    const colorInflow = style.getPropertyValue('--accent-green').trim() || '#3fb950';
    const colorOutflow = style.getPropertyValue('--accent-red').trim() || '#f85149';
    const colorGrid = style.getPropertyValue('--border').trim() || '#30363d';
    const colorText = style.getPropertyValue('--text-secondary').trim() || '#8b949e';

    // 1. Cashflow Line Chart
    const ctxCashflow = document.getElementById('cashflowChart').getContext('2d');
    new Chart(ctxCashflow, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Inflow',
                    data: <?= json_encode($chartInflow) ?>,
                    borderColor: colorInflow,
                    backgroundColor: colorInflow + '20',
                    borderWidth: 2,
                    tension: 0.4, // Restored the smooth, sweeping aesthetic
                    fill: true
                },
                {
                    label: 'Outflow',
                    data: <?= json_encode($chartOutflow) ?>,
                    borderColor: colorOutflow,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.4 // Restored the smooth, sweeping aesthetic
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: colorText } } },
            scales: {
                x: { grid: { color: colorGrid }, ticks: { color: colorText } },
                y: { 
                    beginAtZero: true,
                    min: 0, // This hard-locks the floor of the graph so it cannot scale downward
                    grid: { color: colorGrid }, 
                    ticks: { color: colorText } 
                }
            }
        }
    });

    // 2. Expense Doughnut Chart
    <?php if(!empty($categoryData)): ?>
    const ctxExpense = document.getElementById('expenseChart').getContext('2d');
    const catLabels = <?= json_encode(array_column($categoryData, 'name')) ?>;
    const catData = <?= json_encode(array_column($categoryData, 'total')) ?>;
    const catColors = <?= json_encode(array_column($categoryData, 'color')) ?>;
    
    new Chart(ctxExpense, {
        type: 'doughnut',
        data: {
            labels: catLabels,
            datasets: [{
                data: catData,
                backgroundColor: catColors,
                borderWidth: 2,
                borderColor: style.getPropertyValue('--bg-card').trim() || '#161b22'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'right', labels: { color: colorText } }
            }
        }
    });
    <?php endif; ?>
});
</script>