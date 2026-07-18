<?php
declare(strict_types=1);
use App\Core\Auth;
$baseSym = base_currency_symbol();
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
        <button class="btn scenario-btn" onclick="applyScenario('inflation')"
            title="Simulate 5% cost of living increase">
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
            <div class="alert alert-success"
                style="margin: 0; padding: 0.75rem; font-size: 1.1rem; font-weight: bold; text-align: center;">
                Net Available: <span id="netIncomeDisplay" class="sensitive-data"><?= $sym ?>2,400.00</span>
            </div>
        </div>

        <!-- Allocations -->
        <div class="card glass">
            <h3><i class="fas fa-sliders-h"></i> Allocation Buckets</h3>
            <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">Drag sliders to allocate your net
                income. Total must equal 100%.</p>

            <div id="allocationList" style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- JS will inject sliders here -->
            </div>

            <div class="mt-3"
                style="padding: 1rem; background: rgba(0,0,0,0.05); border-radius: 8px; text-align: center;">
                <span class="text-secondary" id="remainingLabel">Unallocated / Remaining:</span>
                <h2 id="remainingDisplay" style="margin: 0; color: var(--accent);"><?= $sym ?>0.00</h2>
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
        <div class="card glass"
            style="border: 2px dashed var(--accent); text-align: center; background: rgba(59,130,246,0.05);">
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
    window.budgetSym = '<?= $sym ?>';
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>