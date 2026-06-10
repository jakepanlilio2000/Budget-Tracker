<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-car" style="color: var(--accent-blue); margin-right: 8px;"></i> Advanced Loan Sandbox</h1>
        <p style="color: var(--text-secondary);">Simulate true costs, hidden fees, monthly rates, and early payoff savings.</p>
    </div>
</header>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; align-items: start; margin-bottom: 24px;">
    <div class="card">
        <h3 style="margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px;"><i class="fa-solid fa-sliders" style="color: var(--text-muted); margin-right: 8px;"></i> Loan Parameters</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group" style="grid-column: span 2;">
                <label>Purchase Price / Total Loan (₱)</label>
                <input type="number" id="calc-price" value="500000" class="tool-input" style="font-size: 18px; font-weight: bold;">
            </div>
            <div class="form-group">
                <label>Down Payment (₱)</label>
                <input type="number" id="calc-down" value="50000" class="tool-input">
            </div>
            <div class="form-group">
                <label>Principal Financed</label>
                <div id="display-principal" style="padding: 10px; background: var(--bg-primary); border-radius: 6px; color: var(--text-secondary); font-family: 'JetBrains Mono', monospace;">₱ 450,000.00</div>
            </div>
            <div class="form-group" style="grid-column: span 2; display: flex; gap: 12px;">
                <div style="flex: 1;">
                    <label>Interest Rate (%)</label>
                    <input type="number" id="calc-rate" value="6.5" step="0.1" class="tool-input">
                </div>
                <div style="flex: 1;">
                    <label>Rate Type</label>
                    <select id="calc-rate-type" class="tool-input">
                        <option value="annual" selected>Annual Rate (APR)</option>
                        <option value="monthly">Monthly Rate</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Loan Term / Financing Duration *</label>
                <div style="display: flex; gap: 12px; margin-top: 6px;">
                    <div style="flex: 2; margin: 0;">
                        <input type="number" id="calc-term-value" value="5" class="tool-input" style="margin: 0;" min="1">
                    </div>
                    <div style="flex: 3; margin: 0;">
                        <div class="custom-select-wrapper" style="width: 100%;">
                            <select id="calc-term-type" class="tool-input" style="margin: 0; width: 100%;">
                                <option value="years" selected>Calendar Years</option>
                                <option value="months">Calendar Months</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Payment Frequency</label>
                <select id="calc-freq" class="tool-input">
                    <option value="52">Weekly</option>
                    <option value="26">Bi-Weekly</option>
                    <option value="12" selected>Monthly</option>
                    <option value="1">Yearly</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 8px; padding-top: 16px; border-top: 1px dashed var(--border);">
                <label style="color: var(--accent-yellow);">Upfront / Processing Fee</label>
                <input type="number" id="calc-upfront-fee" value="0" class="tool-input" style="border-color: rgba(210, 153, 34, 0.3);">
            </div>
            <div class="form-group" style="margin-top: 8px; padding-top: 16px; border-top: 1px dashed var(--border);">
                <label style="color: var(--accent-yellow);">Recurring Period Fee</label>
                <input type="number" id="calc-recurring-fee" value="0" class="tool-input" style="border-color: rgba(210, 153, 34, 0.3);">
            </div>
            <div class="form-group" style="grid-column: span 2; margin-top: 8px; padding-top: 16px; border-top: 1px dashed var(--border);">
                <label style="color: var(--accent-green);">Extra Contribution (Per Period)</label>
                <input type="number" id="calc-extra" value="0" class="tool-input" style="border-color: rgba(63, 185, 80, 0.3);">
            </div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="background: var(--bg-elevated); border: 1px solid var(--border);">
            <h3 style="margin-bottom: 16px; color: var(--text-primary);"><i class="fa-solid fa-receipt" style="color: var(--text-muted); margin-right: 8px;"></i> True Cost Breakdown</h3>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-weight: bold; font-size: 14px; color: var(--text-secondary);" id="lbl-payment">P&I Payment:</span>
                <span id="res-base-payment" class="amount" style="font-size: 16px; color: var(--text-secondary);">₱ 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-weight: bold; font-size: 14px; color: var(--accent-yellow);">Recurring Fees:</span>
                <span id="res-fee-payment" class="amount" style="font-size: 16px; color: var(--accent-yellow);">+ ₱ 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <span style="font-weight: bold; font-size: 16px; color: var(--accent-red);" id="lbl-total-payment">Required Payment:</span>
                <span id="res-total-payment" class="amount outflow" style="font-size: 24px;">₱ 0.00</span>
            </div>
            <div id="extra-payment-row" style="display: none; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 12px; background: rgba(63, 185, 80, 0.1); border-radius: 8px;">
                <span style="font-weight: bold; font-size: 14px; color: var(--accent-green);">Actual Payment (w/ Extra):</span>
                <span id="res-actual-payment" class="amount" style="font-size: 20px; color: var(--accent-green);">₱ 0.00</span>
            </div>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 16px 0;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span style="color: var(--text-secondary);">Total Interest Paid:</span>
                <span id="res-interest" class="amount outflow" style="font-size: 16px;">₱ 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span style="color: var(--text-secondary);">Total Fees Paid (Upfront + Recurring):</span>
                <span id="res-total-fees" class="amount outflow" style="font-size: 16px;">₱ 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--border);">
                <span style="font-weight: bold;">True Total Cost:</span>
                <span id="res-total" class="amount" style="font-size: 18px;">₱ 0.00</span>
            </div>
        </div>

        <div class="card" id="savings-card" style="border: 1px solid var(--accent-green); background: rgba(63, 185, 80, 0.05); display: none;">
            <h3 style="margin-bottom: 16px; color: var(--accent-green);"><i class="fa-solid fa-rocket" style="margin-right: 8px;"></i> Early Payoff Impact</h3>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span style="color: var(--text-secondary);">Interest & Fees Saved:</span>
                <span id="res-saved-interest" class="amount" style="font-size: 16px; color: var(--accent-green); font-weight: bold;">₱ 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-secondary);">Time Saved:</span>
                <span id="res-saved-time" style="font-size: 16px; color: var(--text-primary); font-weight: bold;">0 periods</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <h3><i class="fa-solid fa-chart-area" style="color: var(--accent-blue); margin-right: 8px;"></i> Amortization Trajectory</h3>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="custom-select-wrapper" style="margin: 0;">
                <select id="chart-resolution-selector" class="tool-input" style="margin: 0; padding: 6px 28px 6px 12px; font-size: 12px;">
                    <option value="auto" selected>Auto Intervals</option>
                    <option value="weekly">Show Weekly Points</option>
                    <option value="semi_monthly">Show Semi-Monthly Points</option>
                    <option value="monthly">Show Monthly Points</option>
                    <option value="quarterly">Show Quarterly Points</option>
                    <option value="annually">Show Annual Points</option>
                </select>
            </div>
            <span id="res-payoff-date" style="font-size: 12px; font-weight: bold; color: var(--accent-blue); padding: 6px 12px; background: rgba(88, 166, 255, 0.1); border-radius: 8px; white-space: nowrap;">Payoff: --</span>
        </div>
    </div>
    <div style="position: relative; height: 350px;">
        <canvas id="amortizationChart" style="width: 100%; height: 100%;"></canvas>
    </div>
</div>

<script>
    document.querySelectorAll('input, select').forEach(el => el.classList.add('tool-input'));
    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();
</script>