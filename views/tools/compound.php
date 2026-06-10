<style>
    .forecaster-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        align-items: start;
    }

    /* Mobile Responsiveness */
    @media (max-width: 900px) {
        .forecaster-grid {
            /* Stack the parameters and results vertically */
            grid-template-columns: 1fr; 
        }
    }
</style>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-arrow-trend-up" style="color: var(--accent-blue); margin-right: 8px;"></i> Compound Forecaster</h1>
        <p style="color: var(--text-secondary);">Calculate the exponential growth of your investments over time.</p>
    </div>
</header>

<div class="forecaster-grid">
    <div class="card">
        <h3 style="margin-bottom: 16px;"><i class="fa-solid fa-sliders" style="color: var(--text-muted); margin-right: 8px;"></i> Parameters</h3>
        <div class="form-group">
            <label>Initial Principal (₱)</label>
            <input type="number" id="calc-principal" value="10000" class="tool-input">
        </div>
        <div class="form-group">
            <label>Monthly Contribution (₱)</label>
            <input type="number" id="calc-monthly" value="5000" class="tool-input">
        </div>
        <div class="form-group">
            <label>Annual Interest Rate (%)</label>
            <input type="number" id="calc-rate" value="8" step="0.1" class="tool-input">
        </div>
        <div class="form-group">
            <label>Years to Grow</label>
            <input type="number" id="calc-years" value="10" class="tool-input">
        </div>
    </div>

    <div class="card" style="background: var(--bg-elevated); border: 1px solid var(--accent-blue);">
        <h3 style="margin-bottom: 24px; color: var(--accent-blue);"><i class="fa-solid fa-chart-pie" style="margin-right: 8px;"></i> Projection Results</h3>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <span style="color: var(--text-secondary);">Total Principal Contributed:</span>
            <span id="res-principal" class="amount" style="font-size: 16px;">₱ 0.00</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <span style="color: var(--text-secondary);">Total Interest Earned:</span>
            <span id="res-interest" class="amount" style="font-size: 16px; color: var(--accent-green);">₱ 0.00</span>
        </div>
        
        <hr style="border: 0; border-top: 1px dashed var(--border); margin: 16px 0;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <span style="font-weight: bold; font-size: 18px;">Future Wealth:</span>
            <span id="res-total" class="amount" style="font-size: 28px; color: var(--accent-blue);">₱ 0.00</span>
        </div>
    </div>
</div>