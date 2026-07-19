<?php
$pageTitle = 'Analytics & Insights';
ob_start();
?>
<div class="page-header">
    <h1>Analytics & AI Insights</h1>
    <p class="text-secondary">Comprehensive financial health, spending trends, and automated radar detections.</p>
</div>

<!-- Financial Health Score -->
<div class="card glass mb-4" style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
    <div
        style="position: relative; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center;">
        <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="54" fill="none" stroke="var(--border-color)" stroke-width="8" />
            <circle cx="60" cy="60" r="54" fill="none"
                stroke="<?= $health['score'] >= 70 ? 'var(--success)' : ($health['score'] >= 40 ? 'var(--accent)' : 'var(--danger)') ?>"
                stroke-width="8" stroke-dasharray="339.292"
                stroke-dashoffset="<?= 339.292 - (339.292 * $health['score'] / 100) ?>" transform="rotate(-90 60 60)"
                style="transition: stroke-dashoffset 1s ease;" />
        </svg>
        <div style="position: absolute; text-align: center;">
            <span style="font-size: 2rem; font-weight: bold; color: var(--text-primary);"><?= $health['score'] ?></span>
            <span style="display: block; font-size: 0.8rem; color: var(--text-secondary);">Grade
                <?= $health['grade'] ?></span>
        </div>
    </div>
    <div style="flex: 1; min-width: 250px;">
        <h3 style="margin-top: 0;">Financial Health Score</h3>
        <p class="text-secondary">Based on your savings rate (<?= $health['savings_rate'] ?>%), emergency fund coverage
            (<?= $health['emergency_months'] ?> months), and tracking consistency.</p>
        <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
            <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--success);">Savings:
                <?= $health['savings_rate'] ?>%</span>
            <span class="badge" style="background: rgba(59,130,246,0.15); color: var(--accent);">Emergency:
                <?= $health['emergency_months'] ?> mo</span>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <!-- AI Recommendations -->
    <div class="card glass">
        <h3><i class="fas fa-lightbulb" style="color: #fbbf24;"></i> Personalized Insights</h3>
        <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($recommendations as $rec): ?>
                <div
                    style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px; border-left: 3px solid <?= $rec['color'] ?>; display: flex; gap: 1rem; align-items: start;">
                    <i class="<?= $rec['icon'] ?>" style="color: <?= $rec['color'] ?>; margin-top: 0.2rem;"></i>
                    <div>
                        <strong style="display: block; margin-bottom: 0.25rem;"><?= e($rec['title']) ?></strong>
                        <span class="text-secondary" style="font-size: 0.9rem;"><?= e($rec['text']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Radar: Subscriptions -->
    <div class="card glass">
        <h3><i class="fas fa-radar" style="color: var(--accent);"></i> Radar: Detected Subscriptions</h3>
        <?php if (empty($subscriptions)): ?>
            <p class="text-secondary mt-3">No recurring subscription patterns detected in the last 6 months.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; margin-top: 1rem;">
                <?php foreach ($subscriptions as $sub): ?>
                    <li class="flex-between" style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                        <div>
                            <strong><?= e($sub['description'] ?: 'Unknown Service') ?></strong>
                            <div class="text-secondary" style="font-size: 0.8rem;">Occurs
                                ~<?= (int) (180 / $sub['frequency']) ?>
                                days • Last: <?= e(date('M d', strtotime($sub['last_date']))) ?></div>
                        </div>
                        <span
                            style="font-weight: bold; color: var(--danger);">-<?= number_format($sub['total_amount'], 2) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Active Alerts -->
<?php if (!empty($alerts)): ?>
    <div class="card glass mt-4" style="border-left: 4px solid #f59e0b;">
        <h3><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Active Radar Alerts</h3>
        <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <?php foreach ($alerts as $alert): ?>
                <div
                    style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: rgba(245, 158, 11, 0.05); border-radius: 8px;">
                    <div>
                        <span class="badge badge-<?= $alert['severity'] ?>"
                            style="margin-right: 0.5rem;"><?= strtoupper(e($alert['severity'])) ?></span>
                        <strong><?= e($alert['title']) ?></strong>
                        <p class="text-secondary" style="margin: 0.25rem 0 0; font-size: 0.85rem;">
                            <?= e($alert['description']) ?>
                        </p>
                    </div>
                    <form onsubmit="resolveAlert(event, <?= $alert['id'] ?>)">
                        <?= \App\Core\CSRF::field() ?>
                        <button type="submit" class="btn btn-sm"
                            style="background: var(--text-secondary); color: white;">Dismiss</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ========================================== -->
<!-- ADVANCED BUSINESS INTELLIGENCE DASHBOARD   -->
<!-- ========================================== -->

<!-- Date Range Filter -->
<div class="card glass mt-4 mb-4">
    <form method="GET" action="<?= url('/analytics') ?>" class="flex-between"
        style="flex-wrap: wrap; gap: 1rem; align-items: end;">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="margin: 0;">
                <label style="font-size: 0.8rem;">From</label>
                <input type="date" name="from" value="<?= e($from) ?>" style="height: 38px;">
            </div>
            <div class="form-group" style="margin: 0;">
                <label style="font-size: 0.8rem;">To</label>
                <input type="date" name="to" value="<?= e($to) ?>" style="height: 38px;">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 38px;">Update Analysis</button>
    </form>
</div>

<!-- 1. Financial Performance -->
<h3 class="mt-4 mb-3"
    style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
    <i class="fas fa-chart-line"></i> Financial Performance
</h3>
<div class="grid grid-2 mb-4">
    <div class="card glass">
        <h4>Income vs Expenses</h4>
        <div style="position: relative; height: 250px; margin-top: 1rem;">
            <canvas id="chartIncomeExpense"></canvas>
        </div>
    </div>
    <div class="card glass">
        <h4>Net Cash Flow</h4>
        <div style="position: relative; height: 250px; margin-top: 1rem;">
            <canvas id="chartNetFlow"></canvas>
        </div>
    </div>
</div>

<!-- 2. Behavioral Analysis -->
<h3 class="mt-4 mb-3"
    style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
    <i class="fas fa-brain"></i> Behavioral Analysis
</h3>
<div class="grid grid-2 mb-4">
    <div class="card glass">
        <h4>Spending by Day of Week</h4>
        <div style="position: relative; height: 250px; margin-top: 1rem;">
            <canvas id="chartDayOfWeek"></canvas>
        </div>
    </div>
    <div class="card glass">
        <h4>Spending by Hour of Day</h4>
        <div style="position: relative; height: 250px; margin-top: 1rem;">
            <canvas id="chartHourOfDay"></canvas>
        </div>
    </div>
</div>

<!-- 3. Category Intelligence -->
<h3 class="mt-4 mb-3"
    style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
    <i class="fas fa-tags"></i> Category Intelligence
</h3>
<div class="grid grid-2 mb-4">
    <div class="card glass">
        <h4>Top Spending Categories</h4>
        <div style="position: relative; height: 250px; margin-top: 1rem;">
            <canvas id="chartTopCategories"></canvas>
        </div>
    </div>
    <div class="card glass">
        <h4>Category Trends (Stacked)</h4>
        <div style="position: relative; height: 250px; margin-top: 1rem;">
            <canvas id="chartCategoryTrends"></canvas>
        </div>
    </div>
</div>

<!-- 4. Account Analysis -->
<h3 class="mt-4 mb-3"
    style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
    <i class="fas fa-university"></i> Account Analysis
</h3>
<div class="card glass mb-4">
    <h4>Account Balances</h4>
    <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
        <?php
        $maxBal = 1;
        foreach ($accounts as $acc) {
            if ((float) $acc['current_balance'] > $maxBal)
                $maxBal = (float) $acc['current_balance'];
        }
        foreach ($accounts as $acc):
            $bal = (float) $acc['current_balance'];
            $pct = $maxBal > 0 ? ($bal / $maxBal) * 100 : 0;
            $color = $bal >= 0 ? 'var(--success)' : 'var(--danger)';
            ?>
            <div>
                <div class="flex-between" style="margin-bottom: 0.25rem;">
                    <span style="font-weight: 500;">
                        <?= e($acc['name']) ?> <small class="text-secondary">(
                            <?= ucfirst(str_replace('_', ' ', e($acc['type']))) ?>)
                        </small>
                    </span>
                    <span class="sensitive-data" style="font-weight: 700; color: <?= $color ?>;">
                        <?= $baseCurrency['symbol'] ?>
                        <?= number_format($bal, 2) ?>
                    </span>
                </div>
                <div style="background: var(--border-color); border-radius: 99px; height: 8px; overflow: hidden;">
                    <div style="width: <?= $pct ?>%; height: 100%; background: <?= $color ?>; transition: width 0.5s;">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Chart.js Initialization for Advanced Analytics -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#9ca3af' : '#64748b';
        const sym = '<?= $baseCurrency['symbol'] ?>';

        const defaultOpts = {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: textColor } } },
            scales: {
                x: { ticks: { color: textColor }, grid: { color: gridColor } },
                y: { ticks: { color: textColor, callback: v => sym + v.toLocaleString() }, grid: { color: gridColor } }
            }
        };

        new Chart(document.getElementById('chartIncomeExpense'), {
            type: 'line',
            data: {
                labels: <?= json_encode($financial['labels']) ?>,
                datasets: [
                    { label: 'Income', data: <?= json_encode($financial['income']) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 },
                    { label: 'Expense', data: <?= json_encode($financial['expense']) ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.4 }
                ]
            },
            options: defaultOpts
        });

        const netData = <?= json_encode($financial['net']) ?>;
        const netColors = netData.map(v => v >= 0 ? '#10b981' : '#ef4444');
        new Chart(document.getElementById('chartNetFlow'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($financial['labels']) ?>,
                datasets: [{ label: 'Net Cash Flow', data: netData, backgroundColor: netColors, borderRadius: 6 }]
            },
            options: defaultOpts
        });

        new Chart(document.getElementById('chartDayOfWeek'), {
            type: 'radar',
            data: {
                labels: <?= json_encode($behavioral['day_of_week']['labels']) ?>,
                datasets: [{
                    label: 'Spending',
                    data: <?= json_encode($behavioral['day_of_week']['data']) ?>,
                    backgroundColor: 'rgba(139,92,246,0.2)',
                    borderColor: '#8b5cf6',
                    pointBackgroundColor: '#8b5cf6'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: textColor } } },
                scales: { r: { ticks: { color: textColor, backdropColor: 'transparent' }, grid: { color: gridColor }, pointLabels: { color: textColor } } }
            }
        });

        new Chart(document.getElementById('chartHourOfDay'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($behavioral['hour_of_day']['labels']) ?>,
                datasets: [{ label: 'Spending', data: <?= json_encode($behavioral['hour_of_day']['data']) ?>, backgroundColor: '#3b82f6', borderRadius: 4 }]
            },
            options: defaultOpts
        });

        const topCats = <?= json_encode($category['top_categories']) ?>;
        new Chart(document.getElementById('chartTopCategories'), {
            type: 'doughnut',
            data: {
                labels: topCats.map(c => c.name),
                datasets: [{ data: topCats.map(c => parseFloat(c.total)), backgroundColor: topCats.map(c => c.color), borderWidth: 0 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: textColor, padding: 15 } } }
            }
        });

        new Chart(document.getElementById('chartCategoryTrends'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($category['trends']['labels']) ?>,
                datasets: <?= json_encode($category['trends']['datasets']) ?>
            },
            options: {
                ...defaultOpts,
                scales: {
                    ...defaultOpts.scales,
                    x: { ...defaultOpts.scales.x, stacked: true },
                    y: { ...defaultOpts.scales.y, stacked: true }
                }
            }
        });
    });
</script>

<script>
    async function resolveAlert(e, id) {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const res = await fetch('<?= url('/analytics/resolve-alert/') ?>' + id, {
                method: 'POST',
                body: formData
            });
            if (res.ok) {
                e.target.closest('.card').remove();

            }
        } catch (err) {
            console.error('Failed to resolve alert', err);
        }
    }
</script>

<style>
    .badge-high {
        background: rgba(239, 68, 68, 0.15);
        color: var(--danger);
    }

    .badge-medium {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
    }

    .badge-low {
        background: rgba(59, 130, 246, 0.15);
        color: var(--accent);
    }
</style>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>