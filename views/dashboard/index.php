<header class="top-bar">
    <div class="top-bar-left">
        <h1>Dashboard</h1>
    </div>
    <div class="top-bar-right">
        <button id="toggle-split-view" class="btn ghost" style="border: 1px solid var(--border); margin-right: 8px; font-weight: bold;">
            🌕 Full Month
        </button>
        
        <select id="year-selector" data-pid="<?= $profile['id'] ?>">
            <?php for($y = date('Y')-1; $y <= date('Y')+2; $y++): ?>
                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button class="btn icon-btn" onclick="document.getElementById('quick-add-modal').classList.add('active')">➕</button>
    </div>
</header>

<div class="period-nav-strip">
    <button class="icon-btn ghost" id="prev-period">◀</button>
   <div class="period-tabs" id="period-tabs">
        <?php 
        $currentMonth = date('Y-m');
        foreach ($periods as $p): 
            $isPast = substr($p, 0, 7) < $currentMonth;
            $tabClass = $p === $selectedPeriod ? 'active' : '';
            if ($isPast) $tabClass .= ' past-month';
        ?>
            <button class="period-tab <?= $tabClass ?>" 
                    data-date="<?= $p ?>" 
                    data-pid="<?= $profile['id'] ?>">
                <?= date('M j', strtotime($p)) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <button class="icon-btn ghost" id="next-period">▶</button>
</div>

<div class="summary-grid" id="summary-cards">
    <div class="card summary-card inflow-card">
        <span>💰 Total Inflow</span>
        <h3 class="amount inflow">
            <?= $profile['currency'] ?>
            <span id="summary-inflow" data-full-val="<?= (float)$summary['total_inflow'] ?>"><?= number_format((float)$summary['total_inflow'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card outflow-card">
        <span>💸 Total Outflow</span>
        <h3 class="amount outflow">
            <?= $profile['currency'] ?>
            <span id="summary-outflow" data-full-val="<?= (float)$summary['total_outflow'] ?>"><?= number_format((float)$summary['total_outflow'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card <?= $summary['net'] >= 0 ? 'positive' : 'negative' ?>">
        <span>📈 Net Savings</span>
        <h3 class="amount">
            <span id="summary-sign"><?= $summary['net'] >= 0 ? '+' : '' ?></span><?= $profile['currency'] ?>
            <span id="summary-net" data-full-val="<?= (float)$summary['net'] ?>"><?= number_format(abs((float)$summary['net']), 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card cumulative-card">
        <span>🏦 Cumulative</span>
        <h3 class="amount">
            <?= $profile['currency'] ?>
            <span id="summary-cum" data-full-val="<?= (float)$summary['cumulative'] ?>"><?= number_format((float)$summary['cumulative'], 2) ?></span>
        </h3>
    </div>
</div>
<div class="dashboard-widgets">
    
    <div class="card widget-card">
        <h3>🧮 Paycheck Planner</h3>
        <p>Enter your <b>per-period</b> expected pay to forecast the month.</p>
        <div class="form-group">
            <input type="text" id="quick-salary-input" inputmode="decimal" placeholder="Per-period amount (e.g., 8000)" style="font-size: 16px; font-weight: bold;">
        </div>
        <div class="planner-results" id="planner-breakdown">
            <span style="color: var(--text-muted); font-size: 13px;">Enter amount to calculate splits...</span>
        </div>
    </div>

    <div class="card widget-card">
        <h3>📊 Cashflow Breakdown</h3>
        <div class="breakdown-bars">
            
            <h4 style="font-size: 11px; color: var(--text-secondary); margin-top: 8px;">INFLOWS</h4>
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
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="amount inflow"><?= round($pct) ?>%</span>
                </div>
                <div class="progress-bg"><div class="progress-fill" style="width: <?= $pct ?>%; background: var(--accent-green);"></div></div>
            </div>
            <?php endif; endforeach; endif; ?>
            <?php if (!$has_inflow): ?><span style="color:var(--text-muted);font-size:12px;">No inflows checked.</span><?php endif; ?>

            <h4 style="font-size: 11px; color: var(--text-secondary); margin-top: 16px;">OUTFLOWS</h4>
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
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="amount outflow"><?= round($pct) ?>%</span>
                </div>
                <div class="progress-bg"><div class="progress-fill" style="width: <?= $pct ?>%; background: var(--accent-red);"></div></div>
            </div>
            <?php endif; endforeach; endif; ?>
            <?php if (!$has_outflow): ?><span style="color:var(--text-muted);font-size:12px;">No outflows checked.</span><?php endif; ?>

        </div>
    </div>
</div>

<script>
    window.monthOutflows = <?= json_encode($monthOutflows ?? []) ?>;
    window.currencySym = "<?= htmlspecialchars($profile['currency']) ?>";
</script>
<div class="budget-table-wrapper">
<div class="budget-table-container" id="budget-table">
    <?php foreach (['inflow', 'outflow'] as $type): ?>
        <?php if (!empty($transactions[$type])): ?>
            <?php foreach ($transactions[$type] as $cat_id => $category): ?>
                <div class="category-section">
                    <div class="category-header toggle-collapse">
                        <h4><?= htmlspecialchars($category['name']) ?></h4>
                        <i>▼</i>
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
                            <span class="tx-name"><?= htmlspecialchars($tx['name']) ?></span>
                            <span class="tx-amount <?= $type ?>" data-full-val="<?= $tx['amount'] ?>">
                                <?= $profile['currency'] ?> <span class="editable-amount"><?= number_format((float)$tx['amount'], 2) ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <div class="category-footer">
                            <span>Subtotal</span>
                            <span class="amount <?= $type ?> cat-subtotal" data-full-val="<?= $cat_total ?>">
                                <?= $profile['currency'] ?> <span><?= number_format($cat_total, 2) ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
<?php endif; ?>
    <?php endforeach; ?>
<div id="quick-add-modal" class="modal">
    <div class="modal-content drawer" style="max-height: 90vh; overflow-y: auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0;">Quick Add Master Entry</h3>
            <button type="button" class="icon-btn ghost close-modal" style="font-size: 20px;">&times;</button>
        </div>

        <form action="<?= $basePath ?>/entries/<?= $profile['id'] ?>/store" method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Entry Name</label>
                    <input type="text" name="name" required placeholder="e.g., Netflix, Car Loan, Salary">
                </div>
                
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" inputmode="decimal" name="amount" required placeholder="0.00" style="font-size: 16px; font-weight: bold;">
                </div>
                
                <div class="form-group">
                    <label>Flow Type</label>
                    <select name="type">
                        <option value="outflow">Outflow (Expense)</option>
                        <option value="inflow">Inflow (Income)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Frequency (How often?)</label>
                <select name="frequency_type" id="frequency_type" required>
                    <option value="monthly" selected>Monthly (Once a month)</option>
                    <option value="semi_monthly">Semi-Monthly (15th & 30th)</option>
                    <option value="custom_months">Installment / Custom Months</option>
                    <option value="one_time">One-Time Specific Date</option>
                </select>
            </div>

            <div id="sm-fields" class="freq-subfield" style="display: none; padding: 16px; background: var(--bg-elevated); border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border);">
                <label style="display: block; margin-bottom: 12px; color: var(--accent-blue);">Select which half of the month:</label>
                <div style="display: flex; gap: 24px;">
                    <label class="checkbox-container" style="display: flex; align-items: center; gap: 8px; width: auto;">
                        <input type="checkbox" name="sm_first" value="1" checked>
                        <span class="checkmark" style="position: relative;"></span> First Half (1st-15th)
                    </label>
                    <label class="checkbox-container" style="display: flex; align-items: center; gap: 8px; width: auto;">
                        <input type="checkbox" name="sm_second" value="1" checked>
                        <span class="checkmark" style="position: relative;"></span> Second Half (16th-End)
                    </label>
                </div>
            </div>

            <div id="installment-fields" class="freq-subfield" style="display: none; padding: 16px; background: var(--bg-elevated); border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="color: var(--accent-blue);">Total Months</label>
                        <input type="number" name="total_months" placeholder="e.g. 12">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="color: var(--accent-blue);">Day of Month</label>
                        <input type="number" name="specific_day" placeholder="1-31" min="1" max="31">
                    </div>
                </div>
            </div>
            
            <div id="onetime-fields" class="freq-subfield" style="display: none; padding: 16px; background: var(--bg-elevated); border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border);">
                <label style="color: var(--accent-blue);">Specific Date</label>
                <input type="date" name="specific_date">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 8px;">
                <div class="form-group">
                    <label>Start Date (Optional)</label>
                    <input type="date" name="start_date">
                </div>
                <div class="form-group">
                    <label>End Date (Optional)</label>
                    <input type="date" name="end_date">
                </div>
            </div>

            <div class="form-group">
                <label>Notes (Optional)</label>
                <input type="text" name="notes" placeholder="Add any details or account numbers here...">
            </div>

            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: rgba(63, 185, 80, 0.05); border: 1px solid var(--border); border-radius: 8px;">
                <div>
                    <label style="margin: 0; display: block; font-weight: bold; color: var(--text-primary);">Active Status</label>
                    <span style="font-size: 11px; color: var(--text-secondary);">Uncheck to pause this entry globally.</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="is_active" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="modal-actions" style="margin-top: 32px;">
                <button type="button" class="btn ghost close-modal">Cancel</button>
                <button type="submit" class="btn primary" style="width: 100%; max-width: 200px;">Save Master Entry</button>
            </div>
        </form>
    </div>
</div>
