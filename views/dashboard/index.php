<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>📊 Financial Cockpit</h1>
        <p style="color: var(--text-secondary); font-size: 13px;">Monitor running ledger paths, split pay periods, and deploy micro entries.</p>
    </div>
    <div class="top-bar-right">
        <button type="button" id="toggle-split-view" class="btn ghost-control-btn">
            🌕 Full Month
        </button>
        
        <div class="custom-select-wrapper">
            <select id="year-selector" data-pid="<?= htmlspecialchars((string)$profile['id']) ?>">
                <?php for($y = date('Y')-1; $y <= date('Y')+2; $y++): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="button" class="btn dashboard-add-btn" onclick="document.getElementById('quick-add-modal').classList.add('active')">
            <span>➕</span> Add Entry
        </button>
    </div>
</header>

<div class="summary-grid" id="summary-cards">
    <div class="card summary-card inflow-card">
        <div class="card-meta">
            <span class="icon-indicator">💰</span>
            <span>Total Inflow</span>
        </div>
        <h3 class="amount inflow">
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '') ?></span>
            <span id="summary-inflow" data-full-val="<?= (float)$summary['total_inflow'] ?>"><?= number_format((float)$summary['total_inflow'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card outflow-card">
        <div class="card-meta">
            <span class="icon-indicator">💸</span>
            <span>Total Outflow</span>
        </div>
        <h3 class="amount outflow">
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '') ?></span>
            <span id="summary-outflow" data-full-val="<?= (float)$summary['total_outflow'] ?>"><?= number_format((float)$summary['total_outflow'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card <?= $summary['net'] >= 0 ? 'positive' : 'negative' ?>">
        <div class="card-meta">
            <span class="icon-indicator">📈</span>
            <span>Net Savings</span>
        </div>
        <h3 class="amount">
            <span id="summary-sign" class="sign-label"><?= $summary['net'] >= 0 ? '+' : '' ?></span><span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '') ?></span>
            <span id="summary-net" data-full-val="<?= (float)$summary['net'] ?>"><?= number_format(abs((float)$summary['net']), 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card cumulative-card">
        <div class="card-meta">
            <span class="icon-indicator">🏦</span>
            <span>Cumulative Total</span>
        </div>
        <h3 class="amount">
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '') ?></span>
            <span id="summary-cum" data-full-val="<?= (float)$summary['cumulative'] ?>"><?= number_format((float)$summary['cumulative'], 2) ?></span>
        </h3>
    </div>
</div>

<div class="dashboard-widgets">
    <div class="card widget-card">
        <div class="widget-header">
            <h3>🧮 Paycheck Planner</h3>
            <p>Enter your <b>per-period</b> expected pay to forecast the month.</p>
        </div>
        <div class="form-group custom-input-group">
            <span class="input-icon-prefix">₱</span>
            <input type="text" id="quick-salary-input" inputmode="decimal" placeholder="Per-period amount (e.g., 8000)">
        </div>
        <div class="planner-results" id="planner-breakdown">
            <span class="empty-state-text">Enter amount to calculate splits...</span>
        </div>
    </div>

    <div class="card widget-card">
        <div class="widget-header">
            <h3>📊 Cashflow Breakdown</h3>
            <p>Dynamic consumption ratio metrics distributed across checked categories.</p>
        </div>
        <div class="breakdown-bars">
            <h4 class="section-label inflow-label">Inflow Streams</h4>
            <?php
            $total_in = $summary['total_inflow'] > 0 ? $summary['total_inflow'] : 1; 
            $has_inflow = false;
            if (!empty($transactions['inflow'])):
                foreach($transactions['inflow'] as $cat):
                    $catTotal = array_sum(array_map(fn($i) => $i['is_checked'] ? $i['amount'] : 0, $cat['items']));
                    $pct = ($catTotal / $total_in) * 100;
                    if ($catTotal > 0): $has_inflow = true;
            ?>
            <div class="breakdown-item">
                <div class="breakdown-label">
                    <span class="breakdown-name"><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="amount inflow"><?= round($pct) ?>%</span>
                </div>
                <div class="progress-bg"><div class="progress-fill" style="width: <?= $pct ?>%; background: var(--accent-green);"></div></div>
            </div>
            <?php endif; endforeach; endif; ?>
            <?php if (!$has_inflow): ?><span class="empty-state-text">No inflows checked in this interval.</span><?php endif; ?>

            <h4 class="section-label outflow-label">Outflow Demographics</h4>
            <?php
            $total_out = $summary['total_outflow'] > 0 ? $summary['total_outflow'] : 1; 
            $has_outflow = false;
            if (!empty($transactions['outflow'])):
                foreach($transactions['outflow'] as $cat):
                    $catTotal = array_sum(array_map(fn($i) => $i['is_checked'] ? $i['amount'] : 0, $cat['items']));
                    $pct = ($catTotal / $total_out) * 100;
                    if ($catTotal > 0): $has_outflow = true;
            ?>
            <div class="breakdown-item">
                <div class="breakdown-label">
                    <span class="breakdown-name"><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="amount outflow"><?= round($pct) ?>%</span>
                </div>
                <div class="progress-bg"><div class="progress-fill" style="width: <?= $pct ?>%; background: var(--accent-red);"></div></div>
            </div>
            <?php endif; endforeach; endif; ?>
            <?php if (!$has_outflow): ?><span class="empty-state-text">No outflows checked in this interval.</span><?php endif; ?>
        </div>
    </div>
</div>

<script>
    window.monthOutflows = <?= json_encode($monthOutflows ?? []) ?>;
    window.currencySym = "<?= htmlspecialchars($profile['currency'] ?? '') ?>";
    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();
</script>

<div class="budget-table-wrapper">
    <div class="budget-table-container" id="budget-table">
        <?php foreach (['inflow', 'outflow'] as $type): ?>
            <?php if (!empty($transactions[$type])): ?>
                <?php foreach ($transactions[$type] as $cat_id => $category): ?>
                    <div class="category-section">
                        <div class="category-header toggle-collapse">
                            <h4><?= htmlspecialchars($category['name']) ?></h4>
                            <span class="collapse-icon">▼</span>
                        </div>
                        <div class="category-rows">
                            <?php 
                            $cat_total = 0;
                            foreach ($category['items'] as $tx): 
                                if ($tx['is_checked']) $cat_total += $tx['amount'];
                            ?>
                            <div class="tx-row <?= $tx['is_checked'] ? '' : 'unchecked' ?>" data-id="<?= $tx['id'] ?>">
                                <label class="checkbox-container">
                                    <input type="checkbox" class="tx-check" <?= $tx['is_checked'] ? 'checked' : '' ?>>
                                    <span class="checkmark"></span>
                                </label>
                                <span class="tx-name" title="<?= htmlspecialchars($tx['name']) ?>"><?= htmlspecialchars($tx['name']) ?></span>
                                <span class="tx-amount <?= $type ?>" data-full-val="<?= $tx['amount'] ?>">
                                    <span class="currency-inline"><?= htmlspecialchars($profile['currency'] ?? '') ?></span> <span class="editable-amount"><?= number_format((float)$tx['amount'], 2) ?></span>
                                </span>
                            </div>
                            <?php endforeach; ?>
                            <div class="category-footer">
                                <span>Subtotal</span>
                                <span class="amount <?= $type ?> cat-subtotal" data-full-val="<?= $cat_total ?>">
                                    <?= htmlspecialchars($profile['currency'] ?? '') ?> <span><?= number_format($cat_total, 2) ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<div id="quick-add-modal" class="modal">
    <div class="modal-content drawer" style="max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">➕ Quick Add Master Entry</h3>
            <button type="button" class="icon-btn ghost close-modal" style="font-size: 18px;">✕</button>
        </div>

        <form action="<?= $basePath ?>/dashboard/<?= htmlspecialchars((string)$profile['id']) ?>/quickAdd" method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            
            <div class="form-group">
                <label>Entry Description</label>
                <input type="text" name="name" required placeholder="e.g., Netflix subscription, Car Loan payoff, Salary cut">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Amount Financed</label>
                    <input type="text" inputmode="decimal" name="amount" required placeholder="0.00" style="font-weight: bold;">
                </div>
                
                <div class="form-group">
                    <label>Flow Vector Direction</label>
                    <select name="type">
                        <option value="outflow">Outflow (Expense Asset)</option>
                        <option value="inflow">Inflow (Income Asset)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>System Category Group Allocation</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars((string)$cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst(htmlspecialchars($cat['type'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Frequency Rule Matrix Interval</label>
                <select name="frequency_type" id="frequency_type" required>
                    <option value="monthly" selected>Monthly Sequence (Standard)</option>
                    <option value="semi_monthly">Semi-Monthly Sequence (15th / 30th Split)</option>
                    <option value="custom_months">Installment Bounds / Temporary Cycle</option>
                    <option value="one_time">One-Time Specific Calendar Event</option>
                </select>
            </div>

            <div id="sm-fields" class="freq-subfield" style="display: none; padding: 16px; background: var(--bg-elevated); border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border);">
                <label style="display: block; margin-bottom: 12px; color: var(--accent-blue); font-weight: bold; font-size: 12px; text-transform: uppercase;">Assign Month Interval Allocation Halves:</label>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label class="checkbox-container" style="display: flex; align-items: center; gap: 8px; width: auto; font-size: 13px;">
                        <input type="checkbox" name="sm_first" value="1" checked>
                        <span class="checkmark" style="position: relative;"></span> First Payment Split (1st - 15th)
                    </label>
                    <label class="checkbox-container" style="display: flex; align-items: center; gap: 8px; width: auto; font-size: 13px;">
                        <input type="checkbox" name="sm_second" value="1" checked>
                        <span class="checkmark" style="position: relative;"></span> Second Payment Split (16th - End)
                    </label>
                </div>
            </div>

            <div id="installment-fields" class="freq-subfield" style="display: none; padding: 16px; background: var(--bg-elevated); border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="color: var(--accent-blue);">Amortization Total Months</label>
                        <input type="number" name="total_months" placeholder="e.g. 12">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="color: var(--accent-blue);">Target Day Date Number</label>
                        <input type="number" name="specific_day" placeholder="1-31 Bounds" min="1" max="31">
                    </div>
                </div>
            </div>
            
            <div id="onetime-fields" class="freq-subfield" style="display: none; padding: 16px; background: var(--bg-elevated); border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border);">
                <label style="color: var(--accent-blue);">Target Date Assignment Bounds</label>
                <input type="date" name="specific_date">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 8px;">
                <div class="form-group">
                    <label>Activation Date (Optional)</label>
                    <input type="date" name="start_date">
                </div>
                <div class="form-group">
                    <label>Expirational Date (Optional)</label>
                    <input type="date" name="end_date">
                </div>
            </div>

            <div class="form-group">
                <label>Ledger Notes Annotation</label>
                <input type="text" name="notes" placeholder="Metadata strings like account routing or transaction references...">
            </div>

            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; padding: 14px; background: rgba(63, 185, 80, 0.04); border: 1px solid var(--border); border-radius: 8px; margin-top: 24px;">
                <div>
                    <label style="margin: 0; display: block; font-weight: bold; color: var(--text-primary);">Operational Status State</label>
                    <span style="font-size: 11px; color: var(--text-secondary);">De-allocating track state pauses execution metrics globally.</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="is_active" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="modal-actions" style="margin-top: 32px; border-top: 1px solid var(--border); padding-top: 16px;">
                <button type="button" class="btn ghost close-modal">Cancel Allocation</button>
                <button type="submit" class="btn primary" style="width: 100%;">Deploy Master Entry</button>
            </div>
        </form>
    </div>
</div>