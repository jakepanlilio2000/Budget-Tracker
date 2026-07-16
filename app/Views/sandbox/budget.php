<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Budget Sandbox';
ob_start();
$sym = $baseCurrency['symbol'];
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Budget Sandbox</h1>
        <p class="text-secondary">Simulate scenarios and plan your allocations risk-free.</p>
    </div>
    <div class="flex-between" style="gap: 0.5rem;">
        <button class="btn scenario-btn" onclick="applyScenario('inflation')" title="Simulate 5% cost of living increase">
            <i class="fas fa-fire"></i> Inflation +5%
        </button>
        <button class="btn scenario-btn" onclick="applyScenario('raise')" title="Simulate 10% income increase">
            <i class="fas fa-chart-line"></i> Raise +10%
        </button>
        <button class="btn scenario-btn" onclick="applyScenario('cut')" title="Simulate 15% income loss">
            <i class="fas fa-exclamation-triangle"></i> Pay Cut -15%
        </button>
        <button class="btn" onclick="resetSandbox()" style="background: var(--text-secondary); color: white;">
            <i class="fas fa-undo"></i> Reset
        </button>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    
    <!-- LEFT: Inputs & Allocations -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Income & Tax -->
        <div class="card glass">
            <h3><i class="fas fa-money-bill-wave"></i> Monthly Income</h3>
            <div class="grid grid-2 mt-3">
                <div class="form-group">
                    <label>Gross Income (<?= $sym ?>)</label>
                    <input type="number" id="grossIncome" value="3000" min="0" step="100" oninput="recalculate()">
                </div>
                <div class="form-group">
                    <label>Tax/Deductions (%)</label>
                    <input type="number" id="taxRate" value="20" min="0" max="100" step="1" oninput="recalculate()">
                </div>
            </div>
            <div class="alert alert-success" style="margin: 0; padding: 0.75rem; font-size: 1.1rem; font-weight: bold; text-align: center;">
                Net Available: <span id="netIncomeDisplay" class="sensitive-data"><?= $sym ?>2,400.00</span>
            </div>
        </div>

        <!-- Allocations -->
        <div class="card glass">
            <h3><i class="fas fa-sliders-h"></i> Allocation Buckets</h3>
            <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">Drag sliders to allocate your net income. Total must equal 100%.</p>
            
            <div id="allocationList" style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- JS will inject sliders here -->
            </div>

            <div class="mt-3" style="padding: 1rem; background: rgba(0,0,0,0.05); border-radius: 8px; text-align: center;">
                <span class="text-secondary">Unallocated / Remaining:</span>
                <h2 id="remainingDisplay" style="margin: 0; color: var(--danger);"><?= $sym ?>0.00</h2>
            </div>
        </div>

    </div>

    <!-- RIGHT: Visualizations -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Distribution Chart -->
        <div class="card glass">
            <h3>Distribution</h3>
            <div style="position: relative; height: 250px; margin-top: 1rem;">
                <canvas id="allocationChart"></canvas>
            </div>
        </div>

        <!-- Projection Chart -->
        <div class="card glass">
            <h3>12-Month Savings Projection</h3>
            <div style="position: relative; height: 250px; margin-top: 1rem;">
                <canvas id="projectionChart"></canvas>
            </div>
        </div>

        <!-- Apply Action -->
        <div class="card glass" style="border: 2px dashed var(--accent); text-align: center; background: rgba(59,130,246,0.05);">
            <h3>Turn this plan into a real goal?</h3>
            <p class="text-secondary">We will create a Savings Vault with your projected annual target.</p>
            <form method="POST" action="<?= url('/sandbox/apply') ?>">
                <?= \App\Core\CSRF::field() ?>
                <input type="hidden" name="projected_savings" id="hiddenTarget">
                <input type="hidden" name="monthly_savings" id="hiddenMonthly">
                <button type="submit" class="btn btn-primary btn-block mt-3" id="applyBtn" disabled>
                    <i class="fas fa-rocket"></i> Create Savings Goal (<span id="projectedTotal"><?= $sym ?>0</span>)
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    const sym = '<?= $sym ?>';
    let allocationChart, projectionChart;

    // Default Buckets
    const buckets = [
        { id: 'needs', name: 'Needs (Rent, Food, Utils)', percent: 50, color: '#3b82f6' },
        { id: 'wants', name: 'Wants (Entertainment, Dining)', percent: 30, color: '#8b5cf6' },
        { id: 'savings', name: 'Savings & Debt', percent: 20, color: '#10b981' }
    ];

    function initSliders() {
        const container = document.getElementById('allocationList');
        container.innerHTML = '';
        buckets.forEach(b => {
            const div = document.createElement('div');
            div.innerHTML = `
                <div class="flex-between" style="margin-bottom: 0.25rem;">
                    <label style="margin:0; font-weight:600; color: ${b.color}">${b.name}</label>
                    <span class="sensitive-data" id="val_${b.id}" style="font-weight:bold;">0.00</span>
                </div>
                <input type="range" min="0" max="100" value="${b.percent}" id="slider_${b.id}" 
                       style="width:100%; accent-color: ${b.color};" oninput="updateBucket('${b.id}', this.value)">
            `;
            container.appendChild(div);
        });
    }

    function updateBucket(id, val) {
        const b = buckets.find(x => x.id === id);
        b.percent = parseInt(val);
        recalculate();
    }

    function recalculate() {
        const gross = parseFloat(document.getElementById('grossIncome').value) || 0;
        const tax = parseFloat(document.getElementById('taxRate').value) || 0;
        const net = gross * (1 - (tax / 100));
        
        document.getElementById('netIncomeDisplay').textContent = sym + net.toFixed(2);

        let allocatedPct = 0;
        let savingsAmount = 0;

        buckets.forEach(b => {
            const amount = net * (b.percent / 100);
            document.getElementById('val_' + b.id).textContent = sym + amount.toFixed(2);
            allocatedPct += b.percent;
            if (b.id === 'savings') savingsAmount = amount;
        });

        const remainingPct = 100 - allocatedPct;
        const remainingAmt = net * (remainingPct / 100);
        const remDisplay = document.getElementById('remainingDisplay');
        
        remDisplay.textContent = sym + remainingAmt.toFixed(2) + ' (' + remainingPct + '%)';
        remDisplay.style.color = remainingPct === 0 ? 'var(--success)' : 'var(--danger)';

        updateCharts(net, savingsAmount);
        updateApplyButton(savingsAmount);
    }

    function updateCharts(net, savings) {
        // 1. Allocation Doughnut
        const ctx1 = document.getElementById('allocationChart').getContext('2d');
        if (allocationChart) allocationChart.destroy();
        
        allocationChart = new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: buckets.map(b => b.name),
                datasets: [{
                    data: buckets.map(b => b.percent),
                    backgroundColor: buckets.map(b => b.color),
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        // 2. Projection Bar (12 Months)
        const ctx2 = document.getElementById('projectionChart').getContext('2d');
        if (projectionChart) projectionChart.destroy();

        const labels = [];
        const data = [];
        let cumulative = 0;
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        for (let i = 0; i < 12; i++) {
            cumulative += savings;
            labels.push(months[i]);
            data.push(cumulative);
        }

        projectionChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Cumulative Savings',
                    data: data,
                    backgroundColor: '#10b981',
                    borderRadius: 4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function updateApplyButton(monthlySavings) {
        const annual = monthlySavings * 12;
        document.getElementById('projectedTotal').textContent = sym + annual.toFixed(2);
        document.getElementById('hiddenTarget').value = annual;
        document.getElementById('hiddenMonthly').value = monthlySavings;
        
        const btn = document.getElementById('applyBtn');
        btn.disabled = (annual <= 0);
    }

    // Scenario Logic
    function applyScenario(type) {
        const incomeInput = document.getElementById('grossIncome');
        let current = parseFloat(incomeInput.value) || 0;
        
        if (type === 'raise') incomeInput.value = current * 1.10;
        if (type === 'cut') incomeInput.value = current * 0.85;
        if (type === 'inflation') {
            // Increase "Needs" slider by 5% absolute (e.g. 50% -> 55%)
            const needsSlider = document.getElementById('slider_needs');
            needsSlider.value = Math.min(100, parseInt(needsSlider.value) + 5);
            updateBucket('needs', needsSlider.value);
        }
        recalculate();
    }

    function resetSandbox() {
        document.getElementById('grossIncome').value = 3000;
        document.getElementById('taxRate').value = 20;
        buckets[0].percent = 50;
        buckets[1].percent = 30;
        buckets[2].percent = 20;
        initSliders();
        recalculate();
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        initSliders();
        recalculate();
    });
</script>

<style>
    .scenario-btn {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }
    .scenario-btn:hover {
        background: rgba(245, 158, 11, 0.2);
    }
    input[type="range"] {
        height: 6px;
        cursor: pointer;
    }
</style>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>