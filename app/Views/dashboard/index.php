<?php 
$pageTitle = 'Dashboard';
ob_start(); 
?>
<div class="page-header">
    <h1>Financial Overview</h1>
    <p class="text-secondary">Welcome back, <?= e($user['full_name'] ?? 'User') ?></p>
</div>

<!-- Stats Widgets -->
<div class="grid grid-3 mb-4">
    <div class="card glass stat-card">
        <div class="stat-icon income"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-info">
            <span class="stat-label">Monthly Income</span>
            <h3 id="stat-income" class="sensitive-data stat-value">
                <?= $baseCurrency['symbol'] ?>0.00
                <i class="fas fa-eye widget-eye-toggle" data-target="#stat-income" title="Click to reveal"></i>
            </h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon expense"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-info">
            <span class="stat-label">Monthly Expenses</span>
            <h3 id="stat-expense" class="sensitive-data stat-value">
                <?= $baseCurrency['symbol'] ?>0.00
                <i class="fas fa-eye widget-eye-toggle" data-target="#stat-expense" title="Click to reveal"></i>
            </h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon balance"><i class="fas fa-wallet"></i></div>
        <div class="stat-info">
            <span class="stat-label">Net Cash Flow</span>
            <h3 id="stat-flow" class="sensitive-data stat-value">
                <?= $baseCurrency['symbol'] ?>0.00
                <i class="fas fa-eye widget-eye-toggle" data-target="#stat-flow" title="Click to reveal"></i>
            </h3>
        </div>
    </div>
</div>
<!-- Net Income & Pending Ledger Widgets -->
<div class="grid grid-2 mb-4">
    <div class="card glass">
        <h3>Monthly Net Income</h3>
        <?php 
            $income = (float)($netIncome['total_income'] ?? 0);
            $expense = (float)($netIncome['total_expense'] ?? 0);
            $net = $income - $expense;
        ?>
        <div style="display: flex; align-items: baseline; gap: 1rem; margin-top: 1rem;">
            <h1 class="sensitive-data"
    style="color: <?= $net >= 0 ? 'var(--success)' : 'var(--danger)' ?>; font-size: 2rem; margin: 0;">
    <?= e($baseCurrency['symbol']) ?><?= number_format($net, 2) ?>
</h1>
        </div>
        <div class="flex-between" style="margin-top: 1rem; font-size: 0.9rem; flex-wrap: wrap; gap: 0.5rem;">
            <span class="sensitive-data" style="color: var(--success);">
                <i class="fas fa-arrow-up"></i> In: <?= e($baseCurrency['symbol']) ?><?= number_format($income, 2) ?>
            </span>
            <span class="sensitive-data" style="color: var(--danger);">
                <i class="fas fa-arrow-down"></i> Out: <?= e($baseCurrency['symbol']) ?><?= number_format($expense, 2) ?>
            </span>
        </div>
    </div>

    <div class="card glass">
        <div class="flex-between">
            <h3>Upcoming Pending</h3>
            <a href="<?= url('/pending-ledger') ?>" class="link" style="font-size: 0.85rem;">View All</a>
        </div>
        <?php if (empty($pendingItems)): ?>
            <p class="text-secondary" style="margin-top: 1rem;">No upcoming pending items.</p>
        <?php else: ?>
            <ul style="list-style: none; margin-top: 1rem; padding: 0;">
                <?php foreach ($pendingItems as $item): ?>
                <li class="flex-between" style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 0.5rem;">
                    <span style="font-size: 0.9rem;">
                        <?= e($item['description']) ?> 
                        <small class="text-secondary">(<?= e(date('M d', strtotime($item['due_date']))) ?>)</small>
                    </span>
                    <span style="color: <?= $item['type'] === 'income' ? 'var(--success)' : 'var(--danger)' ?>; font-weight: 600;">
                        <?= $item['type'] === 'income' ? '+' : '-' ?><?= e($item['symbol']) ?><?= number_format((float)$item['amount'], 2) ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<!-- Savings Vaults Widget -->
<?php if (!empty($topVaults)): ?>
<div class="card glass mb-4">
    <div class="flex-between">
        <h3><i class="fas fa-piggy-bank" style="color: var(--accent);"></i> Savings Goals</h3>
        <a href="<?= url('/vaults') ?>" class="link" style="font-size: 0.85rem;">View All</a>
    </div>
    <div class="grid grid-3 mt-3" style="gap: 1rem;">
        <?php foreach ($topVaults as $v): ?>
        <div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px solid var(--border-color);">
            <h4 style="margin: 0 0 0.5rem; font-size: 0.95rem;"><?= e($v['name']) ?></h4>
            <div style="background: var(--border-color); border-radius: 99px; height: 6px; overflow: hidden; margin-bottom: 0.5rem;">
                <div style="width: <?= $v['metrics']['percentage'] ?>%; height: 100%; background: var(--accent);"></div>
            </div>
            <div class="flex-between" style="font-size: 0.8rem; color: var(--text-secondary);">
                <span class="sensitive-data"><?= $baseCurrency['symbol'] ?><?= number_format((float)$v['current_amount'], 2) ?></span>
                <span><?= $v['metrics']['percentage'] ?>%</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<!-- Bills & Salary Widgets -->
<div class="grid grid-2 mb-4">
    <div class="card glass">
        <div class="flex-between">
            <h3>Upcoming Bills</h3>
            <a href="<?= url('/bills') ?>" class="link" style="font-size: 0.85rem;">Manage</a>
        </div>
        <?php if (empty($upcomingBills)): ?>
            <p class="text-secondary mt-3">No upcoming bills.</p>
        <?php else: ?>
            <ul style="list-style: none; margin-top: 1rem; padding: 0;">
                <?php foreach ($upcomingBills as $bill): ?>
                <li class="flex-between" style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                    <span style="font-size: 0.9rem;"><?= e($bill['name']) ?> <small class="text-secondary">(<?= e(date('M d', strtotime($bill['next_due_date']))) ?>)</small></span>
                    <span class="sensitive-data" style="font-weight: 600;"><?= number_format($bill['total_amount'], 2) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card glass">
        <div class="flex-between">
            <h3>Latest Payslip</h3>
            <a href="<?= url('/salaries') ?>" class="link" style="font-size: 0.85rem;">View All</a>
        </div>
        <?php if (!$latestSalary): ?>
            <p class="text-secondary mt-3">No payslips recorded.</p>
        <?php else: ?>
            <div style="margin-top: 1rem; padding: 1rem; background: rgba(16, 185, 129, 0.05); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2);">
                <div class="flex-between">
                    <span class="text-secondary"><?= e($latestSalary['company_name']) ?></span>
                    <span class="text-secondary"><?= e(date('M d, Y', strtotime($latestSalary['payment_date']))) ?></span>
                </div>
                <h2 class="sensitive-data" style="color: var(--success); margin: 0.5rem 0 0;"><?= $baseCurrency['symbol'] ?><?= number_format($latestSalary['net_pay'], 2) ?></h2>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- Charts -->
<div class="grid grid-2">
    <div class="card glass">
        <h3>Spending by Category</h3>
        <div id="categorySkeleton" class="skeleton" style="height: 250px; margin-top: 1rem;"></div>
        <canvas id="categoryChart" style="display: none;"></canvas>
    </div>
    <div class="card glass">
        <h3>6-Month Trend</h3>
        <div id="trendSkeleton" class="skeleton" style="height: 250px; margin-top: 1rem;"></div>
        <canvas id="trendChart" style="display: none;"></canvas>
    </div>
</div>
<!-- Accounts Summary -->
<div class="card glass mt-4">
    <div class="flex-between">
        <h3>Your Accounts</h3>
        <a href="<?= url('/accounts/create') ?>" class="btn btn-primary btn-sm">+ Add Account</a>
    </div>
    <div class="table-responsive mt-3">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Type</th>
                    <th>Institution</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr><td colspan="4" class="text-center text-secondary">No accounts found. <a href="<?= url('/accounts/create') ?>">Create one</a></td></tr>
                <?php else: ?>
                    <?php foreach ($accounts as $acc): ?>
                    <tr>
                        <td><strong><?= e($acc['name']) ?></strong></td>
                        <td><span class="badge badge-<?= e($acc['type']) ?>"><?= ucfirst(e($acc['type'])) ?></span></td>
                        <td><?= e($acc['institution'] ?: 'N/A') ?></td>
                        <td><strong><?= e($acc['currency_symbol']) ?><?= number_format($acc['current_balance'], 2) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// AJAX Data Fetching for Dashboard
document.addEventListener('DOMContentLoaded', () => {
    fetch('<?= url('/api/dashboard/stats') ?>')
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const data = res.data;
                const sym = '<?= $baseCurrency['symbol'] ?>';
                
                // Update Stats
                document.getElementById('stat-income').textContent = sym + data.monthly_flow.income.toFixed(2);
                document.getElementById('stat-expense').textContent = sym + data.monthly_flow.expense.toFixed(2);
                document.getElementById('stat-flow').textContent = sym + (data.monthly_flow.income - data.monthly_flow.expense).toFixed(2);
                document.getElementById('categorySkeleton').style.display = 'none';
                document.getElementById('categoryChart').style.display = 'block';
                document.getElementById('trendSkeleton').style.display = 'none';
                document.getElementById('trendChart').style.display = 'block';
                // Render Category Chart
                new Chart(document.getElementById('categoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.categories.map(c => c.name),
                        datasets: [{
                            data: data.categories.map(c => c.total),
                            backgroundColor: data.categories.map(c => c.color),
                            borderWidth: 0
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                });

                // Render Trend Chart
                new Chart(document.getElementById('trendChart'), {
                    type: 'line',
                    data: {
                        labels: data.trend.map(t => t.month),
                        datasets: [
                            { label: 'Income', data: data.trend.map(t => t.income), borderColor: '#10b981', tension: 0.4 },
                            { label: 'Expense', data: data.trend.map(t => t.expense), borderColor: '#ef4444', tension: 0.4 }
                        ]
                    },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                });
            }
        })
        .catch(err => console.error('Dashboard stats fetch failed:', err));
});
</script>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>