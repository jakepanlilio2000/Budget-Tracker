<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<style>
    /* Mobile-First Layout Rules */
    .forecast-grid {
        display: flex; 
        flex-direction: column; 
        gap: 24px; 
        align-items: stretch;
        width: 100%;
        box-sizing: border-box;
    }
    
    .forecast-sidebar {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
    }
    
    .forecast-form-row {
        display: flex; 
        flex-direction: column; 
        gap: 16px;
    }
    
    .chart-header {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 24px;
    }
    
    .chart-header-right {
        text-align: left;
    }
    
    .chart-wrapper {
        position: relative; 
        height: 300px; /* Compact height for mobile */
        width: 100%;
    }
    
    .scenario-item {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 16px;
        border-bottom: 1px solid var(--border);
        gap: 12px;
    }
    
    .scenario-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        background: rgba(0,0,0,0.02);
        padding: 8px;
        border-radius: 6px;
    }

    /* Desktop Layout Rules (Triggers on screens wider than 900px) */
    @media (min-width: 900px) {
        .forecast-grid {
            display: grid;
            grid-template-columns: 360px 1fr; /* Fixed width sidebar, fluid chart */
            align-items: start;
        }
        .forecast-form-row {
            flex-direction: row; /* Side-by-side inputs on desktop */
        }
        .chart-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
        .chart-header-right {
            text-align: right;
        }
        .chart-wrapper {
            height: 450px; /* Expanded detailed chart for desktop */
        }
        .scenario-item {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            gap: 16px;
        }
        .scenario-actions {
            width: auto;
            justify-content: flex-end;
            background: transparent;
            padding: 0;
            gap: 16px;
        }
    }
</style>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-wand-magic-sparkles" style="color: var(--accent-blue); margin-right: 8px;"></i> Forecast Sandbox</h1>
        <p style="color: var(--accent-yellow); font-weight: bold; margin: 4px 0;">SIMULATION MODE</p>
        <p style="color: var(--text-secondary); font-size: 13px;">Test financial scenarios (like taking a loan or getting a bonus) without affecting your real data.</p>
    </div>
</header>

<div class="forecast-grid">
    
    <div class="forecast-sidebar">
        <div class="card" style="border: 1px solid var(--accent-yellow);">
            <h3 style="margin-top: 0; margin-bottom: 16px;"><i class="fa-solid fa-flask" style="color: var(--text-muted); margin-right: 8px;"></i> Add Hypothetical</h3>
            <form action="<?= $basePath ?>/forecast/<?= htmlspecialchars((string)$profile_id) ?>/add" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                
                <div class="form-group">
                    <label>Event Name</label>
                    <input type="text" name="name" required placeholder="e.g. Vacation Trip">
                </div>
                
                <div class="form-group forecast-form-row">
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
                            <option value="<?= $i+1 ?>" <?= ($i+1 == date('n')) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn primary" style="width: 100%; margin-top: 8px;">
                    <i class="fa-solid fa-play" style="margin-right: 6px;"></i> Apply to Simulation
                </button>
            </form>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px; background: var(--bg-elevated); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0;"><i class="fa-solid fa-list-ol" style="color: var(--text-muted); margin-right: 6px;"></i> Active Scenarios</h4>
                <?php if(!empty($simItems)): ?>
                    <form action="<?= $basePath ?>/forecast/<?= htmlspecialchars((string)$profile_id) ?>/clear" method="POST" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn ghost" style="padding: 4px 8px; font-size: 12px; color: var(--accent-red); border: 1px solid rgba(248, 81, 73, 0.3);">
                            <i class="fa-solid fa-trash-can" style="margin-right: 4px;"></i> Clear All
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; flex-direction: column;">
                <?php if(empty($simItems)): ?>
                    <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;">No scenarios applied. Graph shows reality.</div>
                <?php else: ?>
                    <?php foreach($simItems as $item): ?>
                    <div class="scenario-item">
                        <div style="width: 100%;">
                            <span style="font-weight: bold; font-size: 14px; display: block; word-break: break-word;"><?= htmlspecialchars($item['name']) ?></span>
                            <span style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase;">
                                <?= htmlspecialchars($months[$item['month'] - 1]) ?> • 
                                <span style="color: <?= $item['type'] === 'inflow' ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                                    <?= htmlspecialchars($item['type']) ?>
                                </span>
                            </span>
                        </div>
                        <div class="scenario-actions">
                            <span class="amount" style="font-size: 15px; font-weight: bold;"><?= htmlspecialchars($profile['currency'] ?? '') ?> <?= number_format($item['amount'], 2) ?></span>
                            <button class="icon-btn ghost remove-sim-btn" data-id="<?= htmlspecialchars((string)$item['id']) ?>" style="color: var(--text-muted); font-size: 16px; padding: 4px 8px;" title="Remove Scenario"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 24px; display: flex; flex-direction: column;">
        <div class="chart-header">
            <h3 style="margin: 0;"><i class="fa-solid fa-chart-area" style="color: var(--accent-blue); margin-right: 8px;"></i> Cumulative Trajectory</h3>
            <?php 
                $endBase = !empty($baseCumulative) ? end($baseCumulative) : 0;
                $endSim = !empty($simCumulative) ? end($simCumulative) : 0;
                $diff = $endSim - $endBase;
            ?>
            <div class="chart-header-right">
                <span style="display: block; font-size: 12px; color: var(--text-secondary);">End of Year Impact</span>
                <span id="live-diff-amount" class="amount" style="font-size: 18px; font-weight: bold; color: <?= $diff >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                    <?= $diff >= 0 ? '+' : '' ?><?= htmlspecialchars($profile['currency'] ?? '') ?> <?= number_format($diff, 2) ?>
                </span>
            </div>
        </div>
        <div class="chart-wrapper">
            <canvas id="forecastChart" style="width: 100%; height: 100%; display: block;"></canvas>
        </div>
    </div>

</div>

<script>
    window.forecastChartData = {
        base: <?= json_encode($baseCumulative ?? []) ?>,
        sim: <?= json_encode($simCumulative ?? []) ?>,
        currencySymbol: "<?= htmlspecialchars($profile['currency'] ?? '₱') ?>"
    };
    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();
</script>