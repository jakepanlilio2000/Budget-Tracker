<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <h1>Live Calculator</h1>
    <button id="calc-import-btn" data-pid="<?= $profile_id ?>" class="btn ghost">📥 Import Current Period</button>
</header>

<div class="calc-layout">
    <div class="card" id="calc-items-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0;">Line Items</h3>
            <button id="calc-add-row" class="btn ghost">+ Add Row</button>
        </div>
        <div id="calc-rows" style="display: flex; flex-direction: column; gap: 12px;"></div>
    </div>

    <div class="card calc-result-panel" style="position: sticky; top: 24px;">
        <h3 style="margin-bottom: 24px;">Results</h3>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;">TOTAL INFLOW</span>
                <span id="calc-res-in" class="amount" style="color: var(--accent-green); font-size: 18px;">0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;">TOTAL OUTFLOW</span>
                <span id="calc-res-out" class="amount" style="color: var(--accent-red); font-size: 18px;">0.00</span>
            </div>
            <hr style="border: 0; border-top: 1px dashed var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 24px; font-weight: bold;">
                <span>NET</span>
                <span id="calc-res-net" class="amount">0.00</span>
            </div>
        </div>
        <div style="margin-top: 32px;">
            <button class="btn primary" style="width: 100%; padding: 16px; font-size: 16px;">Save Calculation</button>
        </div>
    </div>
</div>

<script>
    // Safe manual invocation triggers setup checks right after injection
    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();
</script>