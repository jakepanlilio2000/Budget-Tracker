<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar" style="margin-bottom: 32px;">
    <div class="top-bar-left">
        <h1 style="font-size: 32px;"><i class="fa-solid fa-globe" style="color: var(--accent-blue); margin-right: 8px;"></i> Global Portfolio</h1>
        <p style="color: var(--text-secondary);">Aggregated metrics across all tracked profiles.</p>
    </div>
</header>

<div class="summary-grid" style="margin-bottom: 32px;">
    <div class="card summary-card">
        <span>Gross Combined Inflow</span>
        <h3 class="inflow" style="display: flex; gap: 4px;">
            <span class="currency-label">₱</span>
            <span class="amount global-anim" data-full-val="<?= (float)$globalInflow ?>"><?= number_format($globalInflow, 2) ?></span>
        </h3>
    </div>
    <div class="card summary-card">
        <span>Gross Combined Outflow</span>
        <h3 class="outflow" style="display: flex; gap: 4px;">
            <span class="currency-label">₱</span>
            <span class="amount global-anim" data-full-val="<?= (float)$globalOutflow ?>"><?= number_format($globalOutflow, 2) ?></span>
        </h3>
    </div>
    <div class="card summary-card <?= $globalNetWorth >= 0 ? 'positive' : 'negative' ?>" style="grid-column: span 2; border: 1px solid <?= $globalNetWorth >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
        <span style="color: <?= $globalNetWorth >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">TOTAL GLOBAL NET WORTH</span>
        <h3 style="display: flex; gap: 4px; font-size: 36px;">
            <?= $globalNetWorth >= 0 ? '+' : '' ?><span class="currency-label">₱</span>
            <span class="amount global-anim" data-full-val="<?= (float)$globalNetWorth ?>"><?= number_format($globalNetWorth, 2) ?></span>
        </h3>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start; margin-bottom: 48px;">
    <div>
        <h3 style="margin-bottom: 16px;">Active Nodes</h3>
        <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
            <?php foreach ($profiles as $profile): ?>
                <a href="<?= $basePath ?>/dashboard/<?= $profile['id'] ?>" class="card" style="display: block; text-decoration: none; border-top: 4px solid <?= htmlspecialchars($profile['color'] ?? 'var(--accent-blue)') ?>; transition: transform 0.2s, box-shadow 0.2s;">
                    <h3 style="margin-bottom: 8px; font-size: 20px; color: var(--text-primary);"><?= htmlspecialchars($profile['name']) ?></h3>
                    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                        <span style="color: var(--text-secondary); font-size: 13px;">Net Worth</span>
                        <span style="display: flex; gap: 4px; font-size: 16px; color: <?= $profile['calculated_net'] >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                            <span class="currency-label"><?= htmlspecialchars($profile['currency']) ?></span> 
                            <span class="amount" data-full-val="<?= (float)$profile['calculated_net'] ?>"><?= number_format($profile['calculated_net'], 2) ?></span>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>

            <a href="<?= $basePath ?>/profile/create" class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100px; text-decoration: none; border: 1px dashed var(--text-muted); background: transparent;">
                <span style="font-size: 24px; color: var(--text-muted);"><i class="fa-solid fa-plus"></i></span>
                <span style="color: var(--text-secondary); margin-top: 8px;">Initialize New Node</span>
            </a>
        </div>
    </div>

    <div class="card" style="padding: 24px;">
        <h3 style="margin-bottom: 24px;"><i class="fa-solid fa-chart-area" style="color: var(--accent-blue); margin-right: 8px;"></i> Cross-Profile Cashflow Comparison</h3>
        <div style="position: relative; height: 350px;">
            <?php if(empty($chartData['labels'])): ?>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: var(--text-muted);">Initialize a profile to view global charts.</div>
            <?php else: ?>
                <canvas id="globalChart" style="width: 100%; height: 100%;"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    <?php if(!empty($chartData['labels'])): ?>
    window.globalPortfolioChartData = {
        labels: <?= json_encode($chartData['labels']) ?>,
        inflow: <?= json_encode($chartData['inflow']) ?>,
        outflow: <?= json_encode($chartData['outflow']) ?>
    };
    <?php else: ?>
    window.globalPortfolioChartData = undefined;
    <?php endif; ?>
    
    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();
</script>