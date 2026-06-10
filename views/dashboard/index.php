<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-chart-line" style="color: var(--accent-blue); margin-right: 8px;"></i> Financial Cockpit</h1>
        <p style="color: var(--text-secondary); font-size: 13px;">Monitor running ledger paths, split pay periods, and deploy micro entries.</p>
    </div>
    <div class="top-bar-right">
        <button type="button" id="toggle-split-view" class="btn ghost-control-btn">
            <i class="fa-solid fa-calendar-days"></i> Full Month
        </button>
        
        <div class="custom-select-wrapper">
            <select id="year-selector" data-pid="<?= htmlspecialchars((string)$profile['id']) ?>">
                <?php for($y = date('Y')-1; $y <= date('Y')+2; $y++): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="button" class="btn dashboard-add-btn" onclick="const m = document.getElementById('quick-add-modal'); if(m) m.classList.add('active');">
            <i class="fa-solid fa-plus"></i> Add Entry
        </button>
    </div>
</header>

<div class="summary-grid" id="summary-cards">
    <div class="card summary-card inflow-card" title="Total Expected Gross Inflow for the month">
        <div class="card-meta">
            <span class="icon-indicator"><i class="fa-solid fa-sack-dollar"></i></span>
            <span>Total Expected Inflow</span>
        </div>
        <h3 class="inflow" style="display: flex; gap: 4px; align-items: baseline;">
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount" id="summary-inflow" data-full-val="<?= (float)$summary['total_inflow'] ?>"><?= number_format((float)$summary['total_inflow'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card outflow-card" title="Total Expected Gross Outflow for the month">
        <div class="card-meta">
            <span class="icon-indicator"><i class="fa-solid fa-money-bill-wave"></i></span>
            <span>Total Expected Outflow</span>
        </div>
        <h3 class="outflow" style="display: flex; gap: 4px; align-items: baseline;">
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount" id="summary-outflow" data-full-val="<?= (float)$summary['total_outflow'] ?>"><?= number_format((float)$summary['total_outflow'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card <?= $summary['net'] >= 0 ? 'positive' : 'negative' ?>">
        <div class="card-meta">
            <span class="icon-indicator"><i class="fa-solid fa-arrow-trend-up"></i></span>
            <span>Expected Net Savings</span>
        </div>
        <h3 style="display: flex; gap: 4px; align-items: baseline;">
            <span id="summary-sign" class="sign-label"><?= $summary['net'] >= 0 ? '+' : '' ?></span>
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount" id="summary-net" data-full-val="<?= (float)$summary['net'] ?>"><?= number_format(abs((float)$summary['net']), 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card cumulative-card">
        <div class="card-meta">
            <span class="icon-indicator"><i class="fa-solid fa-building-columns"></i></span>
            <span>YTD Cleared Net</span>
        </div>
        <h3 style="display: flex; gap: 4px; align-items: baseline;">
            <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount" id="summary-cum" data-full-val="<?= (float)$summary['cumulative'] ?>"><?= number_format((float)$summary['cumulative'], 2) ?></span>
        </h3>
    </div>
</div>

<?php 
$pending_outflow = 0;
$pending_inflow = 0;
foreach ($transactions as $type => $cats) {
    foreach ($cats as $cat) {
        foreach ($cat['items'] as $tx) {
            if (!$tx['is_checked']) {
                if ($type === 'outflow') $pending_outflow += (float)$tx['amount'];
                if ($type === 'inflow') $pending_inflow += (float)$tx['amount'];
            }
        }
    }
}
?>

<h3 style="margin-top: 32px; margin-bottom: 16px;"><i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-blue); margin-right: 8px;"></i> Pending Ledger Status</h3>
<div class="summary-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 32px;">
    <div class="card" style="border-top: 4px solid var(--accent-red);">
        <div style="color: var(--text-secondary); font-size: 13px; margin-bottom: 8px;">Unpaid Bills & Expenses</div>
        <div style="display: flex; gap: 4px; align-items: baseline;">
            <span class="currency-label" style="font-size: 16px; color: var(--accent-red);"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount outflow" style="font-size: 28px; font-weight: bold; color: var(--accent-red);" data-full-val="<?= $pending_outflow ?>"><?= number_format($pending_outflow, 2) ?></span>
        </div>
    </div>
    
    <div class="card" style="border-top: 4px solid var(--accent-green);">
        <div style="color: var(--text-secondary); font-size: 13px; margin-bottom: 8px;">Unreceived Income & Savings</div>
        <div style="display: flex; gap: 4px; align-items: baseline;">
            <span class="currency-label" style="font-size: 16px; color: var(--accent-green);"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <span class="amount inflow" style="font-size: 28px; font-weight: bold; color: var(--accent-green);" data-full-val="<?= $pending_inflow ?>"><?= number_format($pending_inflow, 2) ?></span>
        </div>
    </div>
</div>

<h3 style="margin-top: 32px; margin-bottom: 16px;"><i class="fa-solid fa-bars-progress" style="color: var(--accent-blue); margin-right: 8px;"></i> Execution Progress</h3>
<div class="summary-grid" id="execution-cards" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 32px;">
    <div class="card" style="border-top: 4px solid var(--accent-green);">
        <div style="color: var(--text-secondary); font-size: 13px; margin-bottom: 8px;">Inflows Received</div>
        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
            <span style="font-size: 24px; font-weight: bold; color: var(--accent-green); display: flex; gap: 4px; align-items: baseline;">
                <span style="font-size: 16px;"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span> 
                <span class="amount" data-full-val="<?= (float)$summary['actual_in'] ?>"><?= $summary['actual_in'] ?></span>
            </span>
            <span style="font-size: 13px; color: var(--text-muted); font-weight: bold;">/ <?= $summary['total_inflow'] ?></span>
        </div>
        <?php $inPct = $summary['total_inflow'] > 0 ? ($summary['actual_in'] / $summary['total_inflow']) * 100 : 0; ?>
        <div class="progress-bg" style="height: 6px; margin-top: 12px; border-radius: 3px; background: var(--bg-primary);">
            <div class="progress-fill" style="width: <?= min(100, $inPct) ?>%; background: var(--accent-green);"></div>
        </div>
    </div>

    <div class="card" style="border-top: 4px solid var(--accent-red);">
        <div style="color: var(--text-secondary); font-size: 13px; margin-bottom: 8px;">Expenses Cleared</div>
        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
            <span style="font-size: 24px; font-weight: bold; color: var(--accent-red); display: flex; gap: 4px; align-items: baseline;">
                <span style="font-size: 16px;"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span> 
                <span class="amount" data-full-val="<?= (float)$summary['actual_out'] ?>"><?= $summary['actual_out'] ?></span>
            </span>
            <?php $plannedExp = (float)$summary['total_outflow'] - (float)$summary['planned_save']; ?>
            <span style="font-size: 13px; color: var(--text-muted); font-weight: bold;">/ <?= number_format($plannedExp, 2) ?></span>
        </div>
        <?php $outPct = $plannedExp > 0 ? ($summary['actual_out'] / $plannedExp) * 100 : 0; ?>
        <div class="progress-bg" style="height: 6px; margin-top: 12px; border-radius: 3px; background: var(--bg-primary);">
            <div class="progress-fill" style="width: <?= min(100, $outPct) ?>%; background: var(--accent-red);"></div>
        </div>
    </div>

    <div class="card" style="border-top: 4px solid var(--accent-blue);">
        <div style="color: var(--text-secondary); font-size: 13px; margin-bottom: 8px;">Savings Funded</div>
        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
            <span style="font-size: 24px; font-weight: bold; color: var(--accent-blue); display: flex; gap: 4px; align-items: baseline;">
                <span style="font-size: 16px;"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span> 
                <span class="amount" data-full-val="<?= (float)$summary['actual_save'] ?>"><?= $summary['actual_save'] ?></span>
            </span>
            <span style="font-size: 13px; color: var(--text-muted); font-weight: bold;">/ <?= $summary['planned_save'] ?></span>
        </div>
        <?php $savePct = $summary['planned_save'] > 0 ? ($summary['actual_save'] / $summary['planned_save']) * 100 : 0; ?>
        <div class="progress-bg" style="height: 6px; margin-top: 12px; border-radius: 3px; background: var(--bg-primary);">
            <div class="progress-fill" style="width: <?= min(100, $savePct) ?>%; background: var(--accent-blue);"></div>
        </div>
    </div>
</div>

<div class="dashboard-widgets">
    <div class="card widget-card">
        <div class="widget-header">
            <h3><i class="fa-solid fa-calculator" style="color: var(--accent-blue); margin-right: 8px;"></i> Paycheck Planner</h3>
            <p>Enter your <b>per-period</b> expected pay to forecast the month.</p>
        </div>
        <div class="form-group custom-input-group">
            <span class="input-icon-prefix"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
            <input type="text" id="quick-salary-input" inputmode="decimal" placeholder="Per-period amount (e.g., 8000)">
        </div>
        <div class="planner-results" id="planner-breakdown">
            <span class="empty-state-text">Enter amount to calculate splits...</span>
        </div>
    </div>

    <div class="card widget-card">
        <div class="widget-header">
            <h3><i class="fa-solid fa-chart-pie" style="color: var(--accent-blue); margin-right: 8px;"></i> Cleared Breakdown</h3>
            <p>Dynamic consumption ratio metrics distributed across actual execution.</p>
        </div>
        <div class="breakdown-bars">
            <h4 class="section-label inflow-label">Inflow Streams (Actual)</h4>
            <?php
            $total_actual_in = (float)$summary['actual_in'] > 0 ? (float)$summary['actual_in'] : 1; 
            $has_inflow = false;
            if (!empty($transactions['inflow'])):
                foreach($transactions['inflow'] as $cat):
                    $catActual = array_sum(array_map(function($i) {
                        $totality = max((float)$i['master_amount'], (float)$i['amount']);
                        if ($i['is_checked']) return $totality;
                        if ($i['amount'] < $totality) return $totality - $i['amount'];
                        return 0;
                    }, $cat['items']));
                    $pct = ($catActual / $total_actual_in) * 100;
                    if ($catActual > 0): $has_inflow = true;
            ?>
            <div class="breakdown-item">
                <div class="breakdown-label">
                    <span class="breakdown-name"><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="inflow" style="font-weight: bold;"><?= round($pct) ?>%</span>
                </div>
                <div class="progress-bg"><div class="progress-fill" style="width: <?= $pct ?>%; background: var(--accent-green);"></div></div>
            </div>
            <?php endif; endforeach; endif; ?>
            <?php if (!$has_inflow): ?><span class="empty-state-text">No inflows verified this month.</span><?php endif; ?>

            <h4 class="section-label outflow-label">Outflow Demographics (Actual)</h4>
            <?php
            $total_actual_out = (float)$summary['actual_out'] > 0 ? (float)$summary['actual_out'] : 1; 
            $has_outflow = false;
            if (!empty($transactions['outflow'])):
                foreach($transactions['outflow'] as $cat):
                    $catActual = array_sum(array_map(function($i) {
                        $totality = max((float)$i['master_amount'], (float)$i['amount']);
                        if ($i['is_checked']) return $totality;
                        if ($i['amount'] < $totality) return $totality - $i['amount'];
                        return 0;
                    }, $cat['items']));
                    $pct = ($catActual / $total_actual_out) * 100;
                    if ($catActual > 0): $has_outflow = true;
            ?>
            <div class="breakdown-item">
                <div class="breakdown-label">
                    <span class="breakdown-name"><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="outflow" style="font-weight: bold;"><?= round($pct) ?>%</span>
                </div>
                <div class="progress-bg"><div class="progress-fill" style="width: <?= $pct ?>%; background: var(--accent-red);"></div></div>
            </div>
            <?php endif; endforeach; endif; ?>
            <?php if (!$has_outflow): ?><span class="empty-state-text">No outflows verified this month.</span><?php endif; ?>
        </div>
    </div>
</div>

<script>
    window.monthOutflows = <?= json_encode($monthOutflows ?? []) ?>;
    window.currencySym = "<?= htmlspecialchars($profile['currency'] ?? '₱') ?>";
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
                            <span class="collapse-icon"><i class="fa-solid fa-chevron-down"></i></span>
                        </div>
                        <div class="category-rows">
                            <?php 
                            $cat_total = 0;
                            foreach ($category['items'] as $tx): 
                                $totality = max((float)$tx['master_amount'], (float)$tx['amount']);
                                
                                if (!$tx['is_checked']) {
                                    $cat_total += (float)$tx['amount'];
                                }
                                
                                $paid = $tx['is_checked'] ? $totality : ($tx['amount'] < $totality ? $totality - $tx['amount'] : 0);
                                $displayAmount = (float)$tx['amount'];
                            ?>
                            <div class="tx-row <?= $tx['is_checked'] ? '' : 'unchecked' ?>" data-id="<?= $tx['id'] ?>">
                                <label class="checkbox-container">
                                    <input type="checkbox" class="tx-check" <?= $tx['is_checked'] ? 'checked' : '' ?>>
                                    <span class="checkmark"></span>
                                </label>
                                
                                <div style="display: flex; flex-direction: column; flex: 1; min-width: 0; padding-right: 12px;">
                                    <span class="tx-name" title="<?= htmlspecialchars($tx['name']) ?>">
                                        <?= htmlspecialchars($tx['name']) ?>
                                    </span>
                                    <?php if(!empty($tx['notes'])): ?>
                                        <span style="font-size: 11px; color: var(--accent-blue); margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($tx['notes']) ?>">
                                            <i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($tx['notes']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <?php if (!$tx['is_checked']): ?>
                                        <button type="button" class="icon-btn ghost partial-pay-btn" data-id="<?= $tx['id'] ?>" data-name="<?= htmlspecialchars($tx['name']) ?>" title="Log Partial Action" style="color: var(--accent-blue); padding: 4px;">
                                            <i class="fa-solid fa-hand-holding-dollar"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                        <span class="<?= $type ?>" style="display: flex; gap: 4px; font-weight: bold; <?= $tx['is_checked'] ? 'opacity: 0.3; text-decoration: line-through;' : '' ?>">
                                            <span class="currency-inline"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span> 
                                            <span class="amount editable-amount" data-full-val="<?= $displayAmount ?>"><?= number_format($displayAmount, 2) ?></span>
                                        </span>
                                        <?php if (!$tx['is_checked'] && $paid > 0 && $paid < $totality): ?>
                                            <span style="font-size: 11px; color: var(--text-secondary); margin-top: 2px; font-weight: bold;">
                                                Rem: <span class="amount" data-full-val="<?= (float)$tx['amount'] ?>"><?= number_format((float)$tx['amount'], 2) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="category-footer">
                                <span>Remaining Balance</span>
                                <span class="<?= $type ?> cat-subtotal" style="display: flex; gap: 4px; font-weight: bold;">
                                    <?= htmlspecialchars($profile['currency'] ?? '₱') ?> 
                                    <span class="amount" data-full-val="<?= (float)$cat_total ?>"><?= number_format($cat_total, 2) ?></span>
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
            <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-file-circle-plus" style="color: var(--accent-blue);"></i> Quick Add Master Entry</h3>
            <button type="button" class="icon-btn ghost close-modal" style="font-size: 18px;" onclick="const m = document.getElementById('quick-add-modal'); if(m) m.classList.remove('active');"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="<?= $basePath ?>/dashboard/<?= htmlspecialchars((string)$profile['id']) ?>/quickAdd" method="POST" class="form">
            <input type="hidden" name="csrf_token" id="global-csrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            
            <div class="form-group">
                <label>Entry Description</label>
                <input type="text" name="name" required placeholder="e.g., Netflix subscription, Car Loan payoff, Salary cut">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Amount</label>
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
                <button type="button" class="btn ghost close-modal" onclick="const m = document.getElementById('quick-add-modal'); if(m) m.classList.remove('active');">Cancel Allocation</button>
                <button type="submit" class="btn primary" style="width: 100%;">Deploy Master Entry</button>
            </div>
        </form>
    </div>
</div>

<div id="partial-pay-modal" class="modal">
    <div class="modal-content drawer" style="max-width: 400px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-hand-holding-dollar" style="color: var(--accent-blue);"></i> Log Partial Action</h3>
            <button type="button" class="icon-btn ghost close-modal" style="font-size: 18px;" onclick="const m = document.getElementById('partial-pay-modal'); if(m) m.classList.remove('active');"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <p style="color: var(--text-secondary); font-size: 13px; line-height: 1.5;" id="partial-pay-desc"></p>
        
        <div class="form-group" style="margin-top: 20px;">
            <label>Amount Applied Now</label>
            <div class="custom-input-group">
                <span class="input-icon-prefix"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                <input type="text" id="partial-pay-input" inputmode="decimal" placeholder="0.00" style="font-weight: bold; font-size: 18px;">
            </div>
        </div>
        <input type="hidden" id="partial-pay-id">
        
        <div class="modal-actions" style="margin-top: 24px; border-top: 1px solid var(--border); padding-top: 16px;">
            <button type="button" class="btn ghost close-modal" onclick="const m = document.getElementById('partial-pay-modal'); if(m) m.classList.remove('active');">Cancel</button>
            <button type="button" class="btn primary" id="confirm-partial-pay" style="width: 100%;">Apply</button>
        </div>
    </div>
</div>

<script>
    // SPA SAFE WRAPPER: IIFE safely scopes these variables so they don't collide on page re-renders
    (function() {
        document.querySelectorAll('.partial-pay-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const modal = document.getElementById('partial-pay-modal');
                if (modal) {
                    document.getElementById('partial-pay-id').value = btn.dataset.id;
                    document.getElementById('partial-pay-desc').innerHTML = 'Log a partial action for <strong>' + btn.dataset.name + '</strong>. The remaining balance will automatically update and deduct this amount.';
                    document.getElementById('partial-pay-input').value = '';
                    modal.classList.add('active');
                }
            });
        });

        const confirmBtn = document.getElementById('confirm-partial-pay');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', async function() {
                const id = document.getElementById('partial-pay-id').value;
                const amount = document.getElementById('partial-pay-input').value;
                if (!amount || isNaN(amount.replace(/[^0-9.]/g, ''))) return alert('Enter a valid numerical amount.');
                
                this.disabled = true;
                this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
                
                const formData = new FormData();
                formData.append('amount', amount);
                formData.append('csrf_token', document.getElementById('global-csrf').value);

                try {
                    const res = await fetch(`<?= $basePath ?>/dashboard/tx/${id}/partialPay`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        window.location.reload(); 
                    } else {
                        alert(data.error);
                        this.disabled = false;
                        this.innerHTML = 'Apply';
                    }
                } catch(e) {
                    console.error(e);
                    alert('A network error occurred.');
                    this.disabled = false;
                    this.innerHTML = 'Apply';
                }
            });
        }
    })();
</script>