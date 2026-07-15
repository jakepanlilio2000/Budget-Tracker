<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Yearly Review - ' . $currentYear;
ob_start();
$sym = $baseCurrency['symbol'];
$r = $review;

// Prepare Chart Data for JS
$chartLabels = json_encode(array_column($r['monthly_trend'], 'month'));
$chartIncome = json_encode(array_column($r['monthly_trend'], 'income'));
$chartExpense = json_encode(array_column($r['monthly_trend'], 'expense'));

$catLabels = json_encode(array_column($r['top_categories'], 'name'));
$catData = json_encode(array_column($r['top_categories'], 'total'));
$catColors = json_encode(array_column($r['top_categories'], 'color'));
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Yearly Review</h1>
        <p class="text-secondary">Your financial journey for the year <?= e($currentYear) ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <form method="GET" action="<?= url('/yearly-review') ?>" style="display: flex; gap: 0.5rem;">
            <select name="year" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); height: 42px;">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= $currentYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-primary">View</button>
        </form>
        <a href="<?= url('/yearly-review/export-csv?year=' . $currentYear) ?>" class="btn" style="background: var(--text-secondary); color: white;"><i class="fas fa-file-csv"></i> Export CSV</a>
    </div>
</div>

<!-- Core Yearly Stats -->
<div class="grid grid-4 mb-4">
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
            <span class="stat-label">Net Surplus</span>
            <h3 class="sensitive-data" style="color: <?= $r['net_income'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                <?= $sym ?><?= number_format($r['net_income'], 2) ?>
            </h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon" style="background: rgba(139,92,246,0.15); color: #8b5cf6;"><i class="fas fa-piggy-bank"></i></div>
        <div class="stat-info">
            <span class="stat-label">Vault Savings</span>
            <h3 class="sensitive-data"><?= $sym ?><?= number_format($r['yearly_savings'], 2) ?></h3>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-2 mb-4">
    <div class="card glass">
        <h3>Monthly Cash Flow Trend</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
    <div class="card glass">
        <h3>Top Spending Categories</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Best/Worst Months & Milestones -->
<div class="grid grid-2">
    <div class="card glass">
        <h3><i class="fas fa-crown" style="color: #fbbf24;"></i> Yearly Extremes</h3>
        <div class="mt-3" style="display: flex; flex-direction: column; gap: 1rem;">
            <div class="flex-between" style="padding: 1rem; background: rgba(16,185,129,0.05); border-radius: 8px; border-left: 3px solid var(--success);">
                <div>
                    <small class="text-secondary">Best Month (Highest Surplus)</small>
                    <h4 style="margin: 0;"><?= e($r['best_month']['month']) ?></h4>
                </div>
                <span class="sensitive-data" style="font-weight: bold; color: var(--success);">+<?= $sym ?><?= number_format($r['best_month']['net'], 2) ?></span>
            </div>
            <div class="flex-between" style="padding: 1rem; background: rgba(239,68,68,0.05); border-radius: 8px; border-left: 3px solid var(--danger);">
                <div>
                    <small class="text-secondary">Worst Month (Lowest Surplus/Deficit)</small>
                    <h4 style="margin: 0;"><?= e($r['worst_month']['month']) ?></h4>
                </div>
                <span class="sensitive-data" style="font-weight: bold; color: var(--danger);"><?= $sym ?><?= number_format($r['worst_month']['net'], 2) ?></span>
            </div>
        </div>
    </div>

    <div class="card glass">
        <h3><i class="fas fa-flag-checkered" style="color: var(--accent);"></i> Annual Milestones</h3>
        <ul style="list-style: none; padding: 0; margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <?php foreach ($r['milestones'] as $milestone): ?>
                <li style="display: flex; align-items: start; gap: 0.5rem;">
                    <i class="fas fa-star" style="color: #fbbf24; margin-top: 0.2rem;"></i>
                    <span><?= $milestone ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Chart.js Initialization -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
    const textColor = isDark ? '#9ca3af' : '#64748b';

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { 
                labels: { color: textColor } 
            } 
        },
        scales: {
            x: { ticks: { color: textColor }, grid: { color: gridColor } },
            y: { ticks: { color: textColor }, grid: { color: gridColor } }
        }
    };

    // Trend Chart
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?= $chartLabels ?>,
            datasets: [
                { label: 'Income', data: <?= $chartIncome ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 },
                { label: 'Expense', data: <?= $chartExpense ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.4 }
            ]
        },
        options: defaultOptions
    });

    // Category Chart
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?= $catLabels ?>,
            datasets: [{
                data: <?= $catData ?>,
                backgroundColor: <?= $catColors ?>,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    position: 'bottom',
                    labels: { color: textColor, padding: 15 }
                } 
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>