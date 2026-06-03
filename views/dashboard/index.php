<header class="top-bar">
    <div class="top-bar-left">
        <h1>Dashboard</h1>
    </div>
    <div class="top-bar-right">
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
            <span id="summary-inflow"><?= number_format((float)$summary['total_inflow'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card outflow-card">
        <span>💸 Total Outflow</span>
        <h3 class="amount outflow">
            <?= $profile['currency'] ?>
            <span id="summary-outflow"><?= number_format((float)$summary['total_outflow'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card <?= $summary['net'] >= 0 ? 'positive' : 'negative' ?>">
        <span>📈 Net Savings</span>
        <h3 class="amount">
            <?= $summary['net'] >= 0 ? '+' : '' ?>
            <?= $profile['currency'] ?>
            <span id="summary-net"><?= number_format((float)$summary['net'], 2) ?></span>
        </h3>
    </div>

    <div class="card summary-card cumulative-card">
        <span>🏦 Cumulative</span>
        <h3 class="amount">
            <?= $profile['currency'] ?>
            <span id="summary-cum"><?= number_format((float)$summary['cumulative'], 2) ?></span>
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
                            <span class="tx-amount <?= $type ?>" data-val="<?= $tx['amount'] ?>">
                                <?= $profile['currency'] ?> <span class="editable-amount"><?= number_format((float)$tx['amount'], 2) ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <div class="category-footer">
                            <span>Subtotal</span>
                            <span class="amount <?= $type ?>"><?= $profile['currency'] ?> <?= number_format($cat_total, 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
<?php endif; ?>
    <?php endforeach; ?>
</div> </div> <div id="quick-add-modal" class="modal">
    <div class="modal-content drawer">
        <h3>Quick Add Transaction</h3>
       <form action="<?= $basePath ?>/dashboard/<?= $profile['id'] ?>/quick" method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="period_date" value="<?= $selectedPeriod ?>">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Amount</label>
                <input type="text" inputmode="decimal" name="amount" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="outflow">Outflow</option>
                    <option value="inflow">Inflow</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn ghost close-modal">Cancel</button>
                <button type="submit" class="btn primary">Save</button>
            </div>
        </form>
    </div>
</div>
