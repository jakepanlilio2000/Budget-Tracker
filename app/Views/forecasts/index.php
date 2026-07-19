<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Cash Flow Forecast';
ob_start();
$sym = $baseCurrency['symbol'];
$f = $forecast;

$chartLabels = json_encode(array_column($f['projection'], 'label'));
$chartBalances = json_encode(array_column($f['projection'], 'balance'));
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Cash Flow Forecast</h1>
        <p class="text-secondary">Predictive balance analysis for the next
            <?= $days ?> days.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= url('/forecast/sandbox') ?>" class="btn"
            style="background: var(--text-secondary); color: white;"><i class="fas fa-flask"></i> What-If Sandbox</a>
        <form method="GET" action="<?= url('/forecast') ?>" style="display: flex; gap: 0.5rem;">
            <select name="days" class="btn"
                style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); height: 42px;">
                <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 Days</option>
                <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 Days</option>
                <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 Days</option>
            </select>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>

<!-- Warnings -->
<?php if (!empty($f['warnings'])): ?>
    <div class="card glass mb-4" style="border-left: 4px solid var(--danger); background: rgba(239,68,68,0.05);">
        <h3 style="color: var(--danger); margin-top: 0;"><i class="fas fa-exclamation-triangle"></i> Forecast Warnings</h3>
        <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-primary);">
            <?php if (in_array('cash_shortage', $f['warnings'])): ?>
                <li><strong>Cash Shortage Detected:</strong> Your projected balance drops below zero within this period.
                    Consider delaying non-essential expenses.</li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Summary Stats -->
<div class="grid grid-4 mb-4">
    <div class="card glass stat-card">
        <div class="stat-icon" style="background: rgba(59,130,246,0.15); color: var(--accent);"><i
                class="fas fa-wallet"></i></div>
        <div class="stat-info">
            <span class="stat-label">Current Balance</span>
            <h3 class="sensitive-data">
                <?= $sym ?>
                <?= number_format($f['current_balance'], 2) ?>
            </h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success);"><i
                class="fas fa-arrow-down"></i></div>
        <div class="stat-info">
            <span class="stat-label">Expected Income</span>
            <h3 class="sensitive-data">
                <?= $sym ?>
                <?= number_format($f['total_income'], 2) ?>
            </h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger);"><i
                class="fas fa-arrow-up"></i></div>
        <div class="stat-info">
            <span class="stat-label">Expected Expenses</span>
            <h3 class="sensitive-data">
                <?= $sym ?>
                <?= number_format($f['total_expense'], 2) ?>
            </h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon"
            style="background: <?= $f['final_balance'] >= 0 ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)' ?>; color: <?= $f['final_balance'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
            <i class="fas fa-flag-checkered"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Projected Final</span>
            <h3 class="sensitive-data"
                style="color: <?= $f['final_balance'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                <?= $sym ?>
                <?= number_format($f['final_balance'], 2) ?>
            </h3>
        </div>
    </div>
</div>

<!-- Projection Chart -->
<div class="card glass">
    <h3>Projected Balance Trend</h3>
    <div style="position: relative; height: 350px; margin-top: 1rem;">
        <canvas id="forecastChart"></canvas>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#9ca3af' : '#64748b';

        const ctx = document.getElementById('forecastChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= $chartLabels ?>,
                datasets: [{
                    label: 'Projected Balance',
                    data: <?= $chartBalances ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) { return '<?= $sym ?>' + context.raw.toLocaleString(); }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    y: {
                        ticks: { color: textColor, callback: v => '<?= $sym ?>' + v.toLocaleString() },
                        grid: { color: gridColor }
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