<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Investment Simulator';
ob_start();
$sym = $baseCurrency['symbol'];
?>

<div class="page-header">
    <h1>Investment Simulator</h1>
    <p class="text-secondary">Model your wealth growth and compare scenarios risk-free.</p>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    
    <!-- LEFT: Inputs -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Scenario A -->
        <div class="card glass" style="border-top: 3px solid var(--accent);">
            <h3><i class="fas fa-chart-line"></i> Scenario A</h3>
            <div class="grid grid-2 mt-3">
                <div class="form-group">
                    <label>Initial Investment (<?= $sym ?>)</label>
                    <input type="number" id="a_initial" value="1000" min="0" step="100" oninput="runSimulation()">
                </div>
                <div class="form-group">
                    <label>Monthly Contribution (<?= $sym ?>)</label>
                    <input type="number" id="a_monthly" value="200" min="0" step="50" oninput="runSimulation()">
                </div>
                <div class="form-group">
                    <label>Annual Return (%)</label>
                    <input type="number" id="a_rate" value="7" min="0" max="50" step="0.1" oninput="runSimulation()">
                </div>
                <div class="form-group">
                    <label>Time Period (Years)</label>
                    <input type="number" id="a_years" value="10" min="1" max="50" step="1" oninput="runSimulation()">
                </div>
                <div class="form-group">
                    <label>Compound Frequency</label>
                    <select id="a_freq" onchange="runSimulation()">
                        <option value="1">Annually</option>
                        <option value="4">Quarterly</option>
                        <option value="12" selected>Monthly</option>
                        <option value="365">Daily</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Inflation Rate (%)</label>
                    <input type="number" id="a_inflation" value="3" min="0" max="20" step="0.1" oninput="runSimulation()">
                </div>
            </div>
        </div>

        <!-- Scenario B (Toggleable) -->
        <div class="card glass" id="scenarioB_wrap" style="border-top: 3px solid #8b5cf6; display: none;">
            <div class="flex-between">
                <h3 style="color: #8b5cf6;"><i class="fas fa-chart-line"></i> Scenario B (Compare)</h3>
                <button class="btn btn-sm" onclick="toggleScenarioB()" style="background:transparent; color:var(--danger);">Remove</button>
            </div>
            <div class="grid grid-2 mt-3">
                <div class="form-group">
                    <label>Initial (<?= $sym ?>)</label>
                    <input type="number" id="b_initial" value="1000" min="0" step="100" oninput="runSimulation()">
                </div>
                <div class="form-group">
                    <label>Monthly (<?= $sym ?>)</label>
                    <input type="number" id="b_monthly" value="400" min="0" step="50" oninput="runSimulation()">
                </div>
                <div class="form-group">
                    <label>Return (%)</label>
                    <input type="number" id="b_rate" value="5" min="0" max="50" step="0.1" oninput="runSimulation()">
                </div>
                <div class="form-group">
                    <label>Years</label>
                    <input type="number" id="b_years" value="10" min="1" max="50" step="1" oninput="runSimulation()">
                </div>
            </div>
        </div>

        <button class="btn" onclick="toggleScenarioB()" id="addScenarioBtn" style="background: rgba(139,92,246,0.1); color: #8b5cf6; border: 1px dashed #8b5cf6;">
            <i class="fas fa-plus"></i> Add Comparison Scenario
        </button>

    </div>

    <!-- RIGHT: Results & Charts -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Summary Stats -->
        <div class="grid grid-2">
            <div class="card glass stat-card">
                <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success);"><i class="fas fa-piggy-bank"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Future Value (Nominal)</span>
                    <h3 class="sensitive-data" id="res_nominal"><?= $sym ?>0</h3>
                </div>
            </div>
            <div class="card glass stat-card">
                <div class="stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger);"><i class="fas fa-fire"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Real Purchasing Power</span>
                    <h3 class="sensitive-data" id="res_real"><?= $sym ?>0</h3>
                </div>
            </div>
            <div class="card glass stat-card">
                <div class="stat-icon" style="background: rgba(59,130,246,0.15); color: var(--accent);"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Contributed</span>
                    <h3 class="sensitive-data" id="res_contributed"><?= $sym ?>0</h3>
                </div>
            </div>
            <div class="card glass stat-card">
                <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Interest Earned</span>
                    <h3 class="sensitive-data" id="res_interest"><?= $sym ?>0</h3>
                </div>
            </div>
        </div>

        <!-- Growth Chart -->
        <div class="card glass">
            <h3>Wealth Growth Over Time</h3>
            <div style="position: relative; height: 350px; margin-top: 1rem;">
                <canvas id="growthChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
    const sym = '<?= $sym ?>';
    let growthChart;
    let scenarioBEnabled = false;

    function toggleScenarioB() {
        scenarioBEnabled = !scenarioBEnabled;
        document.getElementById('scenarioB_wrap').style.display = scenarioBEnabled ? 'block' : 'none';
        document.getElementById('addScenarioBtn').style.display = scenarioBEnabled ? 'none' : 'block';
        runSimulation();
    }

    function simulate(initial, monthly, annualRate, years, freq, inflation) {
        let nominal = [];
        let real = [];
        let labels = [];
        
        let balance = initial;
        let totalContributed = initial;
        
        const r = annualRate / 100;
        const i = inflation / 100;
        const n = freq;
        
        // Effective monthly rate based on compounding frequency
        const monthlyRate = n > 0 ? Math.pow(1 + r/n, n/12) - 1 : 0;
        
        for (let m = 0; m <= years * 12; m++) {
            if (m % 12 === 0) {
                labels.push('Year ' + (m / 12));
                nominal.push(balance);
                real.push(balance / Math.pow(1 + i, m / 12));
            }
            
            if (m < years * 12) {
                balance *= (1 + monthlyRate);
                balance += monthly;
                totalContributed += monthly;
            }
        }
        
        return { 
            nominal, real, labels, 
            totalContributed, 
            finalNominal: balance, 
            finalReal: balance / Math.pow(1 + i, years) 
        };
    }

    function runSimulation() {
        // Get Scenario A inputs
        const a_initial = parseFloat(document.getElementById('a_initial').value) || 0;
        const a_monthly = parseFloat(document.getElementById('a_monthly').value) || 0;
        const a_rate = parseFloat(document.getElementById('a_rate').value) || 0;
        const a_years = parseInt(document.getElementById('a_years').value) || 1;
        const a_freq = parseInt(document.getElementById('a_freq').value) || 12;
        const a_inflation = parseFloat(document.getElementById('a_inflation').value) || 0;

        const resA = simulate(a_initial, a_monthly, a_rate, a_years, a_freq, a_inflation);

        // Update Summary Stats (Always based on Scenario A)
        document.getElementById('res_nominal').textContent = sym + resA.finalNominal.toFixed(2);
        document.getElementById('res_real').textContent = sym + resA.finalReal.toFixed(2);
        document.getElementById('res_contributed').textContent = sym + resA.totalContributed.toFixed(2);
        document.getElementById('res_interest').textContent = sym + (resA.finalNominal - resA.totalContributed).toFixed(2);

        // Prepare Chart Data
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#9ca3af' : '#64748b';

        const datasets = [
            { label: 'A: Nominal Growth', data: resA.nominal, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 },
            { label: 'A: Real Purchasing Power', data: resA.real, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.3, borderDash: [5, 5] }
        ];

        if (scenarioBEnabled) {
            const b_initial = parseFloat(document.getElementById('b_initial').value) || 0;
            const b_monthly = parseFloat(document.getElementById('b_monthly').value) || 0;
            const b_rate = parseFloat(document.getElementById('b_rate').value) || 0;
            const b_years = parseInt(document.getElementById('b_years').value) || 1;
            
            // Use Scenario A's frequency and inflation for B to keep comparison fair, or add separate inputs if needed. 
            // For simplicity, we'll use A's freq/inflation for B.
            const resB = simulate(b_initial, b_monthly, b_rate, b_years, a_freq, a_inflation);
            
            datasets.push(
                { label: 'B: Nominal Growth', data: resB.nominal, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', fill: false, tension: 0.3, borderDash: [2, 2] }
            );
        }

        const ctx = document.getElementById('growthChart').getContext('2d');
        if (growthChart) growthChart.destroy();

        growthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: resA.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { labels: { color: textColor } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + sym + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    y: { 
                        ticks: { 
                            color: textColor,
                            callback: function(value) { return sym + value.toLocaleString(); }
                        }, 
                        grid: { color: gridColor } 
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        runSimulation();
    });
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>