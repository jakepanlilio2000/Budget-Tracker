<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>🔮 Forecast Sandbox</h1>
        <p style="color: var(--accent-yellow); font-weight: bold;">SIMULATION MODE</p>
        <p style="color: var(--text-secondary); font-size: 13px;">Test financial scenarios (like taking a loan or getting a bonus) without affecting your real data.</p>
    </div>
</header>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
    
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <div class="card" style="border: 1px solid var(--accent-yellow);">
            <h3 style="margin-bottom: 16px;">Add Hypothetical</h3>
            <form action="<?= $basePath ?>/forecast/<?= $profile_id ?>/add" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label>Event Name</label>
                    <input type="text" name="name" required placeholder="e.g. Vacation Trip">
                </div>
                
                <div class="form-group" style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label>Amount</label>
                        <input type="text" inputmode="decimal" name="amount" required placeholder="0.00">
                    </div>
                    <div style="flex: 1;">
                        <label>Flow</label>
                        <select name="type">
                            <option value="outflow">Outflow</option>
                            <option value="inflow">Inflow</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Impact Month</label>
                    <select name="month" required>
                        <?php 
                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        foreach ($months as $i => $m): 
                        ?>
                            <option value="<?= $i+1 ?>" <?= ($i+1 == date('n')) ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn primary" style="width: 100%; margin-top: 8px;">Apply to Simulation</button>
            </form>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px; background: var(--bg-elevated); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0;">Active Scenarios</h4>
                <?php if(!empty($simItems)): ?>
                    <form action="<?= $basePath ?>/forecast/<?= $profile_id ?>/clear" method="POST" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn ghost" style="padding: 4px 8px; font-size: 12px; color: var(--accent-red);">Clear All</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; flex-direction: column;">
                <?php if(empty($simItems)): ?>
                    <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;">No scenarios applied. Graph shows reality.</div>
                <?php else: ?>
                    <?php foreach($simItems as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid var(--border);">
                        <div>
                            <span style="font-weight: bold; font-size: 14px; display: block;"><?= htmlspecialchars($item['name']) ?></span>
                            <span style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase;">
                                <?= $months[$item['month'] - 1] ?> • 
                                <span style="color: <?= $item['type'] === 'inflow' ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                                    <?= $item['type'] ?>
                                </span>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="amount" style="font-size: 14px;"><?= $profile['currency'] ?> <?= number_format($item['amount'], 2) ?></span>
                            <button class="icon-btn ghost remove-sim-btn" data-id="<?= $item['id'] ?>" style="color: var(--text-muted); padding: 4px;">✕</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3>Cumulative Wealth Trajectory</h3>
            <?php 
                $endBase = end($baseCumulative);
                $endSim = end($simCumulative);
                $diff = $endSim - $endBase;
            ?>
            <div style="text-align: right;">
                <span style="display: block; font-size: 12px; color: var(--text-secondary);">End of Year Impact</span>
                <span class="amount" style="font-size: 18px; color: <?= $diff >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                    <?= $diff >= 0 ? '+' : '' ?><?= $profile['currency'] ?> <?= number_format($diff, 2) ?>
                </span>
            </div>
        </div>
        <div style="position: relative; height: 400px;">
            <canvas id="forecastChart" style="width: 100%; height: 100%;"></canvas>
        </div>
    </div>
</div>

<style>
@media (max-width: 1024px) {
    div[style*="grid-template-columns: 1fr 2fr"] { grid-template-columns: 1fr !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Styling Variables
    const style = getComputedStyle(document.body);
    const colorText = style.getPropertyValue('--text-secondary').trim() || '#8b949e';
    const colorGrid = style.getPropertyValue('--border').trim() || '#30363d';
    const colorSim = style.getPropertyValue('--accent-yellow').trim() || '#d29922';
    const colorBase = style.getPropertyValue('--accent-blue').trim() || '#58a6ff';

    // Chart
    const ctx = document.getElementById('forecastChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Reality (Baseline)',
                    data: <?= json_encode($baseCumulative) ?>,
                    borderColor: colorBase,
                    borderWidth: 2,
                    tension: 0.1,
                    pointRadius: 3
                },
                {
                    label: 'Simulation (What-If)',
                    data: <?= json_encode($simCumulative) ?>,
                    borderColor: colorSim,
                    backgroundColor: colorSim + '20', 
                    borderWidth: 3,
                    borderDash: [5, 5], 
                    tension: 0.1,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: colorSim
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { labels: { color: colorText, padding: 20 } },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { grid: { color: colorGrid }, ticks: { color: colorText } },
                y: { grid: { color: colorGrid }, ticks: { color: colorText } }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });

    document.querySelectorAll('.remove-sim-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const id = e.target.dataset.id;
            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);
            formData.append('id', id);

            try {
                const res = await fetch(`<?= $basePath ?>/forecast/<?= $profile_id ?>/remove`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) window.location.reload(); // Reload to refresh chart perfectly
            } catch (err) {
                showToast('Network Error', 'error');
            }
        });
    });
});
</script>