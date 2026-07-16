<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Loan Sandbox';
ob_start();
$sym = $baseCurrency['symbol'];
?>

<div class="page-header">
    <h1>Loan Sandbox</h1>
    <p class="text-secondary">Model your debt payoff and see how extra payments save you thousands.</p>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    
    <!-- LEFT: Inputs -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <div class="card glass">
            <h3><i class="fas fa-file-invoice-dollar"></i> Loan Details</h3>
            <div class="grid grid-2 mt-3">
                <div class="form-group">
                    <label>Loan Amount (<?= $sym ?>)</label>
                    <input type="number" id="loan_amount" value="250000" min="0" step="1000" oninput="runLoanSim()">
                </div>
                <div class="form-group">
                    <label>Annual Interest Rate (%)</label>
                    <input type="number" id="interest_rate" value="6.5" min="0" max="30" step="0.1" oninput="runLoanSim()">
                </div>
                <div class="form-group">
                    <label>Loan Term (Years)</label>
                    <input type="number" id="term_years" value="30" min="1" max="50" step="1" oninput="runLoanSim()">
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="month" id="start_date" value="<?= date('Y-m') ?>" onchange="runLoanSim()">
                </div>
            </div>
        </div>

        <div class="card glass" style="border-top: 3px solid var(--success);">
            <h3><i class="fas fa-rocket"></i> Accelerate Payoff</h3>
            <p class="text-secondary" style="font-size: 0.85rem;">Add extra payments to see how much time and interest you can save.</p>
            <div class="grid grid-2 mt-3">
                <div class="form-group">
                    <label>Extra Monthly Payment (<?= $sym ?>)</label>
                    <input type="number" id="extra_monthly" value="0" min="0" step="50" oninput="runLoanSim()">
                </div>
                <div class="form-group">
                    <label>One-Time Extra Payment (<?= $sym ?>)</label>
                    <input type="number" id="extra_onetime" value="0" min="0" step="500" oninput="runLoanSim()">
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT: Results & Charts -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Summary Stats -->
        <div class="grid grid-2">
            <div class="card glass stat-card">
                <div class="stat-icon" style="background: rgba(59,130,246,0.15); color: var(--accent);"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Base Monthly Payment</span>
                    <h3 class="sensitive-data" id="res_base_payment"><?= $sym ?>0</h3>
                </div>
            </div>
            <div class="card glass stat-card">
                <div class="stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger);"><i class="fas fa-percentage"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Interest (Original)</span>
                    <h3 class="sensitive-data" id="res_orig_interest"><?= $sym ?>0</h3>
                </div>
            </div>
            <div class="card glass stat-card">
                <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success);"><i class="fas fa-piggy-bank"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Interest Saved</span>
                    <h3 class="sensitive-data" id="res_interest_saved" style="color: var(--success);"><?= $sym ?>0</h3>
                </div>
            </div>
            <div class="card glass stat-card">
                <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Time Saved</span>
                    <h3 id="res_time_saved" style="color: #f59e0b;">0 mos</h3>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="card glass">
            <h3>Balance Payoff Curve</h3>
            <div style="position: relative; height: 250px; margin-top: 1rem;">
                <canvas id="balanceChart"></canvas>
            </div>
        </div>

        <div class="card glass">
            <h3>Cumulative Principal vs Interest</h3>
            <div style="position: relative; height: 250px; margin-top: 1rem;">
                <canvas id="cumulativeChart"></canvas>
            </div>
        </div>

    </div>
</div>

<!-- Amortization Schedule -->
<div class="card glass mt-4">
    <div class="flex-between">
        <h3><i class="fas fa-table"></i> Amortization Schedule</h3>
        <button class="btn btn-sm" id="toggleScheduleBtn" onclick="toggleSchedule()" style="background: var(--text-secondary); color: white;">
            Show Schedule
        </button>
    </div>
    <div id="scheduleWrap" style="display: none; margin-top: 1rem; max-height: 500px; overflow-y: auto;">
        <table class="data-table" id="scheduleTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Payment</th>
                    <th>Principal</th>
                    <th>Interest</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody class="sensitive-data"></tbody>
        </table>
    </div>
</div>

<script>
    const sym = '<?= $sym ?>';
    let balanceChart, cumulativeChart;

    function calculateAmortization(principal, annualRate, termMonths, extraMonthly, extraOneTime) {
        const r = annualRate / 100 / 12;
        let balance = principal;
        let schedule = [];
        let totalInterest = 0;
        
        // Standard monthly payment formula
        let basePayment = 0;
        if (r === 0) {
            basePayment = principal / termMonths;
        } else {
            basePayment = principal * (r * Math.pow(1 + r, termMonths)) / (Math.pow(1 + r, termMonths) - 1);
        }
        
        let oneTimeApplied = false;
        
        // Safety limit: max 2x the original term to prevent infinite loops if math breaks
        for (let month = 1; balance > 0.01 && month <= termMonths * 2; month++) {
            let interest = balance * r;
            let principalPart = basePayment - interest;
            let extra = extraMonthly;
            
            if (!oneTimeApplied && extraOneTime > 0) {
                extra += extraOneTime;
                oneTimeApplied = true;
            }
            
            let totalPrincipalPart = principalPart + extra;
            
            // If the payment is larger than the remaining balance
            if (totalPrincipalPart > balance) {
                totalPrincipalPart = balance;
                principalPart = Math.max(0, balance - extra);
            }
            
            let payment = interest + totalPrincipalPart;
            
            balance -= totalPrincipalPart;
            totalInterest += interest;
            
            schedule.push({
                month: month,
                payment: payment,
                principal: totalPrincipalPart,
                interest: interest,
                balance: Math.max(0, balance)
            });
        }
        
        return {
            basePayment: basePayment,
            schedule: schedule,
            totalInterest: totalInterest,
            monthsPaid: schedule.length
        };
    }

    function runLoanSim() {
        const principal = parseFloat(document.getElementById('loan_amount').value) || 0;
        const annualRate = parseFloat(document.getElementById('interest_rate').value) || 0;
        const years = parseFloat(document.getElementById('term_years').value) || 0;
        const extraMonthly = parseFloat(document.getElementById('extra_monthly').value) || 0;
        const extraOneTime = parseFloat(document.getElementById('extra_onetime').value) || 0;
        const startDate = document.getElementById('start_date').value;

        const termMonths = Math.round(years * 12);
        if (termMonths <= 0 || principal <= 0) return;

        // 1. Original Scenario (No extras)
        const orig = calculateAmortization(principal, annualRate, termMonths, 0, 0);
        // 2. Accelerated Scenario (With extras)
        const acc = calculateAmortization(principal, annualRate, termMonths, extraMonthly, extraOneTime);

        // Update Summary Stats
        document.getElementById('res_base_payment').textContent = sym + acc.basePayment.toFixed(2);
        document.getElementById('res_orig_interest').textContent = sym + orig.totalInterest.toFixed(2);
        
        const interestSaved = Math.max(0, orig.totalInterest - acc.totalInterest);
        document.getElementById('res_interest_saved').textContent = sym + interestSaved.toFixed(2);
        
        const timeSaved = Math.max(0, orig.monthsPaid - acc.monthsPaid);
        const yearsSaved = Math.floor(timeSaved / 12);
        const monthsRemainder = timeSaved % 12;
        document.getElementById('res_time_saved').textContent = `${yearsSaved}y ${monthsRemainder}m`;

        // Prepare Chart Data
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#9ca3af' : '#64748b';

        // Balance Payoff Line Chart
        const balanceLabels = acc.schedule.map((_, i) => i + 1);
        const origBalance = orig.schedule.map(s => s.balance);
        const accBalance = acc.schedule.map(s => s.balance);

        const ctx1 = document.getElementById('balanceChart').getContext('2d');
        if (balanceChart) balanceChart.destroy();

        balanceChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: balanceLabels,
                datasets: [
                    { label: 'Original Balance', data: origBalance, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.2, borderDash: [5, 5] },
                    { label: 'Accelerated Balance', data: accBalance, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.2 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: textColor } } },
                scales: {
                    x: { title: { display: true, text: 'Month', color: textColor }, ticks: { color: textColor }, grid: { color: gridColor } },
                    y: { title: { display: true, text: 'Balance', color: textColor }, ticks: { color: textColor, callback: v => sym + v.toLocaleString() }, grid: { color: gridColor } }
                }
            }
        });

        // Cumulative Principal vs Interest (Stacked Bar)
        // Group by year for cleaner visualization
        const yearlyData = {};
        acc.schedule.forEach(s => {
            const year = Math.ceil(s.month / 12);
            if (!yearlyData[year]) yearlyData[year] = { principal: 0, interest: 0 };
            yearlyData[year].principal += s.principal;
            yearlyData[year].interest += s.interest;
        });

        const ctx2 = document.getElementById('cumulativeChart').getContext('2d');
        if (cumulativeChart) cumulativeChart.destroy();

        cumulativeChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: Object.keys(yearlyData).map(y => 'Year ' + y),
                datasets: [
                    { label: 'Principal', data: Object.values(yearlyData).map(d => d.principal), backgroundColor: '#3b82f6' },
                    { label: 'Interest', data: Object.values(yearlyData).map(d => d.interest), backgroundColor: '#ef4444' }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: textColor } } },
                scales: {
                    x: { stacked: true, ticks: { color: textColor }, grid: { color: gridColor } },
                    y: { stacked: true, ticks: { color: textColor, callback: v => sym + v.toLocaleString() }, grid: { color: gridColor } }
                }
            }
        });

        // Update Amortization Table
        const tbody = document.querySelector('#scheduleTable tbody');
        tbody.innerHTML = '';
        const startDt = new Date(startDate + '-01');
        
        acc.schedule.forEach(s => {
            const dt = new Date(startDt);
            dt.setMonth(dt.getMonth() + s.month - 1);
            const dateStr = dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${dateStr}</td>
                <td>${sym}${s.payment.toFixed(2)}</td>
                <td style="color: var(--success);">${sym}${s.principal.toFixed(2)}</td>
                <td style="color: var(--danger);">${sym}${s.interest.toFixed(2)}</td>
                <td><strong>${sym}${s.balance.toFixed(2)}</strong></td>
            `;
            tbody.appendChild(tr);
        });
    }

    function toggleSchedule() {
        const wrap = document.getElementById('scheduleWrap');
        const btn = document.getElementById('toggleScheduleBtn');
        if (wrap.style.display === 'none') {
            wrap.style.display = 'block';
            btn.textContent = 'Hide Schedule';
        } else {
            wrap.style.display = 'none';
            btn.textContent = 'Show Schedule';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        runLoanSim();
    });
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>