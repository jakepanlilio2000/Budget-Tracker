<?php
declare(strict_types=1);
use App\Core\Auth;
$pageTitle = 'Financial Planning Studio';
ob_start();
$sym = $baseCurrency['symbol'];

$activeScenarioId = $_GET['scenario'] ?? null;
$activeScenario = null;
if ($activeScenarioId) {
    foreach ($scenarios as $s) {
        if ($s['id'] == $activeScenarioId) {
            $activeScenario = $s;
            break;
        }
    }
}
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Financial Planning Studio</h1>
        <p class="text-secondary">Simulate scenarios and plan your financial future risk-free.</p>
    </div>

    <div style="position: relative; display: inline-block;">
        <button class="btn"
            style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary);"
            onclick="const d = document.getElementById('scenarioDropdown'); d.style.display = d.style.display === 'none' ? 'block' : 'none'">
            <i class="fas fa-folder-open"></i>
            <?= $activeScenario ? e($activeScenario['name']) : 'Manage Scenarios' ?>
            <i class="fas fa-chevron-down" style="font-size: 0.8rem; margin-left: 0.5rem;"></i>
        </button>

        <div id="scenarioDropdown" class="glass"
            style="display: none; position: absolute; right: 0; top: 100%; margin-top: 0.5rem; min-width: 280px; z-index: 1000; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
            <div style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                <button class="btn btn-sm btn-primary btn-block"
                    onclick="const f = document.getElementById('newScenarioForm'); f.style.display = f.style.display === 'none' ? 'block' : 'none'">
                    <i class="fas fa-plus"></i> Save Current Workspace
                </button>
            </div>

            <div id="newScenarioForm"
                style="display: none; padding: 0.75rem; background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                <form method="POST" action="<?= url('/sandbox/scenario/store') ?>" class="form-stack">
                    <?= \App\Core\CSRF::field() ?>
                    <input type="hidden" name="gross_income" id="save_gross_income">
                    <input type="hidden" name="tax_rate" id="save_tax_rate">
                    <input type="hidden" name="bucket_needs" id="save_bucket_needs">
                    <input type="hidden" name="bucket_wants" id="save_bucket_wants">
                    <input type="hidden" name="bucket_savings" id="save_bucket_savings">

                    <input type="text" name="name" placeholder="Scenario Name" required class="form-group"
                        style="margin-bottom: 0.5rem;">
                    <textarea name="description" placeholder="Description (optional)" rows="2" class="form-group"
                        style="margin-bottom: 0.5rem;"></textarea>
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Save</button>
                </form>
            </div>

            <div style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($scenarios)): ?>
                    <div style="padding: 1rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">No
                        saved scenarios yet.</div>
                <?php else: ?>
                    <?php foreach ($scenarios as $s): ?>
                        <div
                            style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1; cursor: pointer;"
                                onclick="window.location.href='<?= url('/sandbox/budget?scenario=' . $s['id']) ?>'">
                                <div style="font-weight: 600; font-size: 0.9rem;"><?= e($s['name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                    <?= e(date('M d, Y', strtotime($s['updated_at']))) ?></div>
                            </div>
                            <div style="display: flex; gap: 0.25rem;">
                                <form method="POST" action="<?= url('/sandbox/scenario/duplicate/' . $s['id']) ?>"
                                    style="display:inline;">
                                    <?= \App\Core\CSRF::field() ?>
                                    <button type="submit" class="btn-icon" style="font-size: 0.8rem; padding: 0.25rem;"
                                        title="Duplicate"><i class="fas fa-copy"></i></button>
                                </form>
                                <form method="POST" action="<?= url('/sandbox/scenario/archive/' . $s['id']) ?>"
                                    style="display:inline;">
                                    <?= \App\Core\CSRF::field() ?>
                                    <button type="submit" class="btn-icon" style="font-size: 0.8rem; padding: 0.25rem;"
                                        title="Archive/Unarchive"><i class="fas fa-archive"></i></button>
                                </form>
                                <form method="POST" action="<?= url('/sandbox/scenario/delete/' . $s['id']) ?>"
                                    style="display:inline;" onsubmit="return confirm('Permanently delete this scenario?');">
                                    <?= \App\Core\CSRF::field() ?>
                                    <button type="submit" class="btn-icon"
                                        style="font-size: 0.8rem; padding: 0.25rem; color: var(--danger);" title="Delete"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-2" style="gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="card glass">
        <h3><i class="fas fa-bolt" style="color: #f59e0b;"></i> What-If Simulator</h3>
        <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">Instantly simulate life events.
            Changes apply to your workspace immediately.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <button class="btn btn-sm" style="background: #10b981; color: white;" onclick="applyWhatIf('raise', 10)"><i
                    class="fas fa-arrow-up"></i> Raise +10%</button>
            <button class="btn btn-sm" style="background: #ef4444; color: white;" onclick="applyWhatIf('cut', 15)"><i
                    class="fas fa-arrow-down"></i> Pay Cut -15%</button>
            <button class="btn btn-sm" style="background: #f59e0b; color: white;"
                onclick="applyWhatIf('inflation', 5)"><i class="fas fa-fire"></i> Inflation +5%</button>
            <button class="btn btn-sm" style="background: #3b82f6; color: white;"
                onclick="applyWhatIf('bonus', 5000)"><i class="fas fa-gift"></i> $5k Bonus</button>
            <button class="btn btn-sm" style="background: #8b5cf6; color: white;"
                onclick="applyWhatIf('newbaby', 500)"><i class="fas fa-baby"></i> New Baby (-$500/mo)</button>
            <button class="btn btn-sm" style="background: var(--text-secondary); color: white;"
                onclick="applyWhatIf('reset')"><i class="fas fa-undo"></i> Reset Events</button>
        </div>
    </div>

    <div class="card glass">
        <h3><i class="fas fa-layer-group" style="color: var(--accent);"></i> Budget Templates</h3>
        <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">Apply proven financial frameworks
            instantly.</p>
        <select id="templateSelect" class="form-group"
            style="width: 100%; padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-glass-solid); color: var(--text-primary);"
            onchange="applyTemplate(this.value)">
            <option value="">Apply a Template...</option>
            <?php
            $templates = \App\Models\BudgetTemplate::getAll(Auth::id());
            foreach ($templates as $t):
                $allocs = json_decode($t['allocations'], true);
                ?>
                <option value='<?= htmlspecialchars(json_encode($allocs)) ?>'><?= e($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary btn-block mt-2"
            onclick="document.getElementById('saveTemplateModal').style.display='flex'">
            <i class="fas fa-save"></i> Save Current as Template
        </button>
    </div>
</div>

<div class="grid grid-2" style="gap: 1.5rem; margin-bottom: 1.5rem;">
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card glass">
            <h3><i class="fas fa-money-bill-wave"></i> Monthly Income</h3>
            <div class="grid grid-2 mt-3">
                <div class="form-group">
                    <label>Gross Income (<?= $sym ?>)</label>
                    <input type="number" id="grossIncome"
                        value="<?= $activeScenario ? (json_decode($activeScenario['workspace_data'], true)['gross_income'] ?? 3000) : 3000 ?>"
                        min="0" step="100" oninput="recalculate()">
                </div>
                <div class="form-group">
                    <label>Tax/Deductions (%)</label>
                    <input type="number" id="taxRate"
                        value="<?= $activeScenario ? (json_decode($activeScenario['workspace_data'], true)['tax_rate'] ?? 20) : 20 ?>"
                        min="0" max="100" step="1" oninput="recalculate()">
                </div>
            </div>
            <div class="alert alert-success"
                style="margin: 0; padding: 0.75rem; font-size: 1.1rem; font-weight: bold; text-align: center;">
                Net Available: <span id="netIncomeDisplay" class="sensitive-data"><?= $sym ?>0.00</span>
            </div>
        </div>

        <div class="card glass">
            <h3><i class="fas fa-sliders-h"></i> Allocation Buckets</h3>
            <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">Drag sliders to allocate your net
                income. Total must equal 100%.</p>
            <div id="allocationList" style="display: flex; flex-direction: column; gap: 1rem;"></div>
            <div class="mt-3"
                style="padding: 1rem; background: rgba(0,0,0,0.05); border-radius: 8px; text-align: center;">
                <span class="text-secondary" id="remainingLabel">Unallocated / Remaining:</span>
                <h2 id="remainingDisplay" style="margin: 0; color: var(--accent);"><?= $sym ?>0.00</h2>
            </div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card glass">
            <h3>Distribution</h3>
            <div style="position: relative; height: 250px; margin-top: 1rem;"><canvas id="allocationChart"></canvas>
            </div>
        </div>
        <div class="card glass">
            <h3>12-Month Savings Projection</h3>
            <div style="position: relative; height: 250px; margin-top: 1rem;"><canvas id="projectionChart"></canvas>
            </div>
        </div>

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

<div class="card glass" style="margin-bottom: 1.5rem;">
    <div class="flex-between" style="margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
        <h3><i class="fas fa-file-invoice-dollar" style="color: #ef4444;"></i> Loan & Debt Simulator</h3>
        <button class="btn btn-sm btn-primary"
            onclick="document.getElementById('addLoanForm').style.display = document.getElementById('addLoanForm').style.display === 'none' ? 'block' : 'none'">
            <i class="fas fa-plus"></i> Add Simulated Loan
        </button>
    </div>

    <div id="addLoanForm"
        style="display: none; background: rgba(0,0,0,0.02); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <form id="loanForm" onsubmit="handleAddLoan(event)" class="grid grid-4" style="gap: 1rem; align-items: end;">
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Loan Name</label>
                <input type="text" name="name" required placeholder="e.g., Car Loan">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Principal (<?= $sym ?>)</label>
                <input type="number" step="0.01" name="principal" id="loanPrincipal" required placeholder="10000">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Interest Rate (%)</label>
                <input type="number" step="0.01" name="annual_interest_rate" id="loanRate" required placeholder="5.5">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Term (Months)</label>
                <input type="number" name="term_months" id="loanTerm" required placeholder="60">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Extra Payment (<?= $sym ?>)</label>
                <input type="number" step="0.01" name="extra_monthly_payment" id="loanExtra" value="0" placeholder="0">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Start Date</label>
                <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <input type="hidden" name="scenario_id" value="<?= $activeScenarioId ?>">
            <button type="submit" class="btn btn-sm btn-primary">Add Loan</button>
        </form>
    </div>

    <div id="loanListContainer">
        <p class="text-secondary text-center" style="padding: 2rem;">No simulated loans added yet. Add one above to see
            payoff projections.</p>
    </div>
</div>

<div class="card glass" style="margin-bottom: 1.5rem;">
    <div class="flex-between" style="margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
        <h3><i class="fas fa-chart-line" style="color: #10b981;"></i> Investment Portfolio Simulator</h3>
        <button class="btn btn-sm btn-primary"
            onclick="document.getElementById('addInvestmentForm').style.display = document.getElementById('addInvestmentForm').style.display === 'none' ? 'block' : 'none'">
            <i class="fas fa-plus"></i> Add Simulated Asset
        </button>
    </div>

    <div id="addInvestmentForm"
        style="display: none; background: rgba(0,0,0,0.02); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <form id="investmentForm" onsubmit="handleAddInvestment(event)" class="grid grid-4"
            style="gap: 1rem; align-items: end;">
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Asset Name</label>
                <input type="text" name="name" required placeholder="e.g., S&P 500 ETF">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Asset Type</label>
                <select name="asset_type">
                    <option value="stocks">Stocks</option>
                    <option value="etf">ETF</option>
                    <option value="mutual_fund">Mutual Fund</option>
                    <option value="bonds">Bonds</option>
                    <option value="crypto">Crypto</option>
                    <option value="real_estate">Real Estate</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Initial (<?= $sym ?>)</label>
                <input type="number" step="0.01" name="initial_investment" value="0" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Monthly Contrib (<?= $sym ?>)</label>
                <input type="number" step="0.01" name="monthly_contribution" value="100" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Return Rate (%)</label>
                <input type="number" step="0.01" name="annual_return_rate" value="8.0" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Fee Rate (%)</label>
                <input type="number" step="0.01" name="annual_fee_rate" value="0.1">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size: 0.8rem;">Term (Months)</label>
                <input type="number" name="term_months" value="120" required>
            </div>
            <input type="hidden" name="scenario_id" value="<?= $activeScenarioId ?? '' ?>">
            <button type="submit" class="btn btn-sm btn-primary">Add Asset</button>
        </form>
    </div>

    <div id="investmentListContainer">
        <p class="text-secondary text-center" style="padding: 2rem;">No simulated investments added yet.</p>
    </div>
</div>

<div class="card glass" style="margin-bottom: 1.5rem;">
    <h3><i class="fas fa-water" style="color: #3b82f6;"></i> Unified Cash Flow (Live vs Simulated)</h3>
    <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">How your sandbox changes impact your
        actual cash flow trajectory.</p>
    <div style="position: relative; height: 300px;"><canvas id="unifiedCashFlowChart"></canvas></div>
</div>

<div class="card glass" style="margin-bottom: 1.5rem;">
    <div class="flex-between" style="margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
        <h3><i class="fas fa-heartbeat" style="color: #ef4444;"></i> Financial Health & Smart Recommendations</h3>
        <button class="btn btn-sm" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color);"
            onclick="loadFinancialHealth()">
            <i class="fas fa-sync-alt"></i> Refresh Analysis
        </button>
    </div>

    <div class="grid grid-2" style="gap: 1.5rem; margin-top: 1rem;">
        <div style="text-align: center; padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 12px;">
            <div style="position: relative; width: 150px; height: 150px; margin: 0 auto;">
                <canvas id="healthScoreChart"></canvas>
                <div
                    style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                    <div id="overallHealthScore"
                        style="font-size: 2.5rem; font-weight: 800; color: var(--text-primary);">--</div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);">Health Score</div>
                </div>
            </div>
            <div class="grid grid-3 mt-3" style="font-size: 0.8rem; color: var(--text-secondary);">
                <div>Savings: <strong id="scoreSavings" style="color: var(--text-primary);">--</strong></div>
                <div>Debt: <strong id="scoreDebt" style="color: var(--text-primary);">--</strong></div>
                <div>Emergency: <strong id="scoreEmergency" style="color: var(--text-primary);">--</strong></div>
            </div>
        </div>

        <div id="recommendationsContainer" style="max-height: 300px; overflow-y: auto;">
            <div class="text-center text-secondary" style="padding: 2rem;">
                <i class="fas fa-spinner fa-spin"></i> Analyzing your financial data...
            </div>
        </div>
    </div>
</div>

<div class="card glass" style="margin-bottom: 1.5rem;">
    <h3><i class="fas fa-chart-area" style="color: #8b5cf6;"></i> Probability Simulator (Monte Carlo)</h3>
    <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">Simulate 1,000 market scenarios to
        estimate your chance of reaching your goal.</p>

    <form id="monteCarloForm" onsubmit="runMonteCarlo(event)" class="grid grid-4"
        style="gap: 1rem; align-items: end; margin-bottom: 1.5rem;">
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Initial Balance (<?= $sym ?>)</label>
            <input type="number" step="0.01" name="initial_balance" value="1000" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Monthly Contrib (<?= $sym ?>)</label>
            <input type="number" step="0.01" name="monthly_contribution" value="200" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Expected Return (%)</label>
            <input type="number" step="0.1" name="annual_return" value="8.0" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Volatility/Risk (%)</label>
            <input type="number" step="0.1" name="annual_volatility" value="15.0" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Timeframe (Months)</label>
            <input type="number" name="months" value="120" required>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Target Goal (<?= $sym ?>)</label>
            <input type="number" step="0.01" name="target_goal" value="50000" required>
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Run Simulation</button>
    </form>

    <div id="monteCarloResults" style="display: none;">
        <div class="grid grid-3 mb-3" style="text-align: center;">
            <div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div style="font-size: 0.8rem; color: var(--text-secondary);">Probability of Success</div>
                <div id="mcProbability" style="font-size: 2rem; font-weight: 800; color: var(--accent);">--%</div>
            </div>
            <div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div style="font-size: 0.8rem; color: var(--text-secondary);">Median Outcome (50th)</div>
                <div id="mcMedian" style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">--</div>
            </div>
            <div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div style="font-size: 0.8rem; color: var(--text-secondary);">Best Case (90th)</div>
                <div id="mcBest" style="font-size: 1.5rem; font-weight: 700; color: var(--success);">--</div>
            </div>
        </div>
        <div style="position: relative; height: 250px;"><canvas id="monteCarloChart"></canvas></div>
    </div>
</div>

<div class="card glass" style="margin-bottom: 1.5rem;">
    <h3><i class="fas fa-balance-scale" style="color: #f59e0b;"></i> Scenario Comparison</h3>
    <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">Compare up to 3 saved scenarios
        side-by-side.</p>

    <form id="comparisonForm" onsubmit="runComparison(event)" class="grid grid-3"
        style="gap: 1rem; margin-bottom: 1.5rem;">
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Scenario 1</label>
            <select name="scenario_ids[]" class="comparison-select" required>
                <option value="">Select...</option>
                <?php foreach ($scenarios as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Scenario 2 (Optional)</label>
            <select name="scenario_ids[]" class="comparison-select">
                <option value="">Select...</option>
                <?php foreach ($scenarios as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size: 0.8rem;">Scenario 3 (Optional)</label>
            <select name="scenario_ids[]" class="comparison-select">
                <option value="">Select...</option>
                <?php foreach ($scenarios as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Compare</button>
    </form>

    <div id="comparisonResults" style="display: none;">
        <div class="table-responsive">
            <table class="data-table" id="comparisonTable">
                <thead>
                    <tr>
                        <th>Metric</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div id="saveTemplateModal" class="modal-overlay" style="display: none;"
    onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 400px;">
        <h3>Save as Custom Template</h3>
        <form method="POST" action="<?= url('/sandbox/template/save') ?>" id="saveTemplateForm" class="form-stack mt-3"
            onsubmit="handleSaveTemplate(event)">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="needs" id="tmpl_needs">
            <input type="hidden" name="wants" id="tmpl_wants">
            <input type="hidden" name="savings" id="tmpl_savings">
            <div class="form-group">
                <label>Template Name</label>
                <input type="text" name="name" required placeholder="e.g., My Aggressive Savings Plan">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2"></textarea>
            </div>
            <div class="flex-between mt-3">
                <button type="button" class="btn btn-sm" style="background: var(--text-secondary); color: white;"
                    onclick="document.getElementById('saveTemplateModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Template</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.budgetSym = '<?= $sym ?>';
    window.activeWorkspace = <?= $activeScenario ? json_encode(json_decode($activeScenario['workspace_data'], true)) : 'null' ?>;
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>