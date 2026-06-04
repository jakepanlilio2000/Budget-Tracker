<header class="top-bar">
    <div class="top-bar-left">
        <h1>📈 Compound Forecaster</h1>
        <p style="color: var(--text-secondary);">Calculate the exponential growth of your investments over time.</p>
    </div>
</header>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    <div class="card">
        <h3 style="margin-bottom: 16px;">Parameters</h3>
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
        <h3 style="margin-bottom: 24px; color: var(--accent-blue);">Projection Results</h3>
        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
            <span style="color: var(--text-secondary);">Total Principal Contributed:</span>
            <span id="res-principal" class="amount" style="font-size: 16px;">₱ 0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
            <span style="color: var(--text-secondary);">Total Interest Earned:</span>
            <span id="res-interest" class="amount" style="font-size: 16px; color: var(--accent-green);">₱ 0.00</span>
        </div>
        <hr style="border: 0; border-top: 1px dashed var(--border); margin: 16px 0;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: bold; font-size: 18px;">Future Wealth:</span>
            <span id="res-total" class="amount" style="font-size: 28px; color: var(--accent-blue);">₱ 0.00</span>
        </div>
    </div>
</div>