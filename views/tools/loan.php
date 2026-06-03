<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>🚗 Advanced Loan Sandbox</h1>
        <p style="color: var(--text-secondary);">Simulate true costs, hidden fees, monthly rates, and early payoff savings.</p>
    </div>
</header>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; align-items: start; margin-bottom: 24px;">
    
    <div class="card">
        <h3 style="margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">Loan Parameters</h3>
        
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
            
            <div class="form-group">
                <label>Loan Term (Years)</label>
                <input type="number" id="calc-years" value="5" class="tool-input">
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
                <p style="font-size: 10px; color: var(--text-secondary); margin-bottom: 8px;">One-time fee paid at the start.</p>
                <input type="number" id="calc-upfront-fee" value="0" class="tool-input" style="border-color: rgba(210, 153, 34, 0.3);">
            </div>

            <div class="form-group" style="margin-top: 8px; padding-top: 16px; border-top: 1px dashed var(--border);">
                <label style="color: var(--accent-yellow);">Recurring Period Fee</label>
                <p style="font-size: 10px; color: var(--text-secondary); margin-bottom: 8px;">Insurance, admin, etc. per payment.</p>
                <input type="number" id="calc-recurring-fee" value="0" class="tool-input" style="border-color: rgba(210, 153, 34, 0.3);">
            </div>

            <div class="form-group" style="grid-column: span 2; margin-top: 8px; padding-top: 16px; border-top: 1px dashed var(--border);">
                <label style="color: var(--accent-green);">Extra Contribution (Per Period)</label>
                <p style="font-size: 11px; color: var(--text-secondary); margin-bottom: 8px;">How much extra are you adding to each payment to kill the principal faster?</p>
                <input type="number" id="calc-extra" value="0" class="tool-input" style="border-color: rgba(63, 185, 80, 0.3);">
            </div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <div class="card" style="background: var(--bg-elevated); border: 1px solid var(--border);">
            <h3 style="margin-bottom: 16px; color: var(--text-primary);">True Cost Breakdown</h3>
            
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
            <h3 style="margin-bottom: 16px; color: var(--accent-green);">🚀 Early Payoff Impact</h3>
            
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3>Amortization Trajectory</h3>
        <span id="res-payoff-date" style="font-size: 14px; font-weight: bold; color: var(--accent-blue); padding: 4px 12px; background: rgba(88, 166, 255, 0.1); border-radius: 12px;">Payoff Date: --</span>
    </div>
    <div style="position: relative; height: 350px;">
        <canvas id="amortizationChart" style="width: 100%; height: 100%;"></canvas>
    </div>
</div>

<script>
(function initLoanSandbox() {
    const inputs = document.querySelectorAll('.tool-input');
    const style = getComputedStyle(document.body);
    const colorGrid = style.getPropertyValue('--border').trim() || '#30363d';
    const colorText = style.getPropertyValue('--text-secondary').trim() || '#8b949e';
    const colorBlue = style.getPropertyValue('--accent-blue').trim() || '#58a6ff';

    const fmt = (num) => '₱ ' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const calculate = () => {
        let price = parseFloat(document.getElementById('calc-price').value) || 0;
        let down = parseFloat(document.getElementById('calc-down').value) || 0;
        let rateVal = parseFloat(document.getElementById('calc-rate').value) || 0;
        let rateType = document.getElementById('calc-rate-type').value;
        let years = parseFloat(document.getElementById('calc-years').value) || 0;
        let freq = parseInt(document.getElementById('calc-freq').value) || 12;
        let upfrontFee = parseFloat(document.getElementById('calc-upfront-fee').value) || 0;
        let recurringFee = parseFloat(document.getElementById('calc-recurring-fee').value) || 0;
        let extra = parseFloat(document.getElementById('calc-extra').value) || 0;

        let principal = Math.max(0, price - down);
        document.getElementById('display-principal').innerText = fmt(principal);

        let r = 0;
        if (rateType === 'annual') {
            r = (rateVal / 100) / freq;
        } else if (rateType === 'monthly') {
            let annualEquivalent = rateVal * 12;
            r = (annualEquivalent / 100) / freq;
        }

        let n = years * freq;

        let basePayment = 0;
        if (r === 0) {
            basePayment = principal / n;
        } else {
            basePayment = principal * (r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
        }
        if (!isFinite(basePayment)) basePayment = 0;

        let requiredTotalPayment = basePayment + recurringFee;
        let actualPayment = requiredTotalPayment + extra;
        let effectivePIPayment = basePayment + extra; 

        let freqLabel = freq === 52 ? 'Weekly' : freq === 26 ? 'Bi-Weekly' : freq === 12 ? 'Monthly' : 'Yearly';
        document.getElementById('lbl-payment').innerText = `${freqLabel} P&I:`;
        document.getElementById('lbl-total-payment').innerText = `Required ${freqLabel} Payment:`;

        let balance = principal;
        let totalInterest = 0;
        let totalRecurringFeesPaid = 0;
        let periodsTaken = 0;

        let chartLabels = [];
        let chartBalances = [];

        for (let i = 1; i <= n; i++) {
            if (balance <= 0) break;

            let interestForPeriod = balance * r;
            let principalForPeriod = effectivePIPayment - interestForPeriod;

            if (principalForPeriod > balance) {
                principalForPeriod = balance;
                effectivePIPayment = principalForPeriod + interestForPeriod;
                actualPayment = effectivePIPayment + recurringFee;
            }

            balance -= principalForPeriod;
            totalInterest += interestForPeriod;
            totalRecurringFeesPaid += recurringFee;
            periodsTaken++;

            if (i % freq === 0 || balance <= 0 || i === 1) {
                let yearMark = (i / freq).toFixed(1).replace('.0', '');
                chartLabels.push(`Year ${yearMark}`);
                chartBalances.push(Math.max(0, balance));
            }
        }

        let baseTotalInterest = 0;
        if (r === 0) {
            baseTotalInterest = 0;
        } else {
            baseTotalInterest = (basePayment * n) - principal;
        }
        if (!isFinite(baseTotalInterest) || baseTotalInterest < 0) baseTotalInterest = 0;
        
        let baseTotalRecurringFees = recurringFee * n;

        let savedInterestAndFees = Math.max(0, (baseTotalInterest + baseTotalRecurringFees) - (totalInterest + totalRecurringFeesPaid));
        let savedPeriods = Math.max(0, n - periodsTaken);

        let payoffDate = new Date();
        if (freq === 52) payoffDate.setDate(payoffDate.getDate() + (periodsTaken * 7));
        else if (freq === 26) payoffDate.setDate(payoffDate.getDate() + (periodsTaken * 14));
        else if (freq === 12) payoffDate.setMonth(payoffDate.getMonth() + periodsTaken);
        else payoffDate.setFullYear(payoffDate.getFullYear() + periodsTaken);

        document.getElementById('res-base-payment').innerText = fmt(basePayment);
        document.getElementById('res-fee-payment').innerText = '+ ' + fmt(recurringFee);
        document.getElementById('res-total-payment').innerText = fmt(requiredTotalPayment);
        
        const extraRow = document.getElementById('extra-payment-row');
        if (extra > 0) {
            extraRow.style.display = 'flex';
            document.getElementById('res-actual-payment').innerText = fmt(actualPayment);
        } else {
            extraRow.style.display = 'none';
        }

        document.getElementById('res-interest').innerText = fmt(totalInterest);
        document.getElementById('res-total-fees').innerText = fmt(upfrontFee + totalRecurringFeesPaid);
        document.getElementById('res-total').innerText = fmt(principal + totalInterest + upfrontFee + totalRecurringFeesPaid + down);
        document.getElementById('res-payoff-date').innerText = 'Payoff Date: ' + payoffDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });

        const savingsCard = document.getElementById('savings-card');
        if (extra > 0 && savedInterestAndFees > 0) {
            savingsCard.style.display = 'block';
            document.getElementById('res-saved-interest').innerText = fmt(savedInterestAndFees);
            let timeSavedLabel = savedPeriods === 1 ? ' period' : ' periods';
            document.getElementById('res-saved-time').innerText = savedPeriods + timeSavedLabel + ` early`;
        } else {
            savingsCard.style.display = 'none';
        }

        renderChart(chartLabels, chartBalances);
    };

    const renderChart = (labels, data) => {
        const canvas = document.getElementById('amortizationChart');
        if (!canvas) return;
        let existingChart = Chart.getChart(canvas);
        if (existingChart) existingChart.destroy();

        const ctx = canvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Remaining Principal',
                    data: data,
                    borderColor: colorBlue,
                    backgroundColor: colorBlue + '20',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.2,
                    pointRadius: 4,
                    pointBackgroundColor: colorBlue
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: colorGrid }, ticks: { color: colorText } },
                    y: { 
                        beginAtZero: true, 
                        grid: { color: colorGrid }, 
                        ticks: { color: colorText, callback: function(val) { return '₱' + (val/1000) + 'k'; } } 
                    }
                },
                interaction: { mode: 'nearest', axis: 'x', intersect: false }
            }
        });
    };
    inputs.forEach(input => {
        input.removeEventListener('input', calculate); 
        input.addEventListener('input', calculate);
    });
    
    calculate(); 
})();
</script>