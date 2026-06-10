<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-sack-dollar" style="color: var(--accent-green); margin-right: 8px;"></i> Income & Revenue</h1>
        <p style="color: var(--text-secondary);">Track salaries, client payments, business sales, and diverse income streams.</p>
    </div>
</header>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; align-items: start; margin-bottom: 32px;">
    
    <div class="card">
        <h3 style="margin-bottom: 16px;">Log Revenue</h3>
        <form action="<?= $basePath ?>/income/<?= $profile['id'] ?>/store" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label>Source / Client / Item (What/Who)</label>
                <input type="text" name="source_name" required placeholder="e.g., Monthly Corporate Salary, Frontend Consulting Project, Retainer Contract" autofocus>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Platform / Venue (Where)</label>
                    <input type="text" name="platform" placeholder="e.g., Upwork, Remote Bank, Cash Counter">
                </div>
                <div class="form-group">
                    <label>Amount (<?= htmlspecialchars($profile['currency'] ?? '₱') ?>)</label>
                    <input type="number" name="amount" step="0.01" required placeholder="0.00">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Date Received (When)</label>
                    <input type="date" name="date_received" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Payment Destination</label>
                    <select name="payment_method">
                        <option value="Bank Transfer">Bank Transfer / Wire</option>
                        <option value="Cash">Physical Cash</option>
                        <option value="GCash">GCash Balance</option>
                        <option value="PayPal">PayPal / Stripe Endpoint</option>
                        <option value="Crypto">Cryptocurrency Wallet</option>
                        <option value="Check">Printed Check</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 8px;">
                <label style="color: var(--accent-green);">Revenue Classification Type</label>
                <select name="business_type" style="border-color: rgba(63, 185, 80, 0.4);">
                    <option value="Fixed Salary">Fixed Salary / Monthly Wages</option>
                    <option value="Freelance / Consulting">Freelance Contract / Consulting</option>
                    <option value="E-commerce / Sales">E-commerce Store / Product Sales</option>
                    <option value="Investments / Dividends">Investments Yield / Dividends</option>
                    <option value="Rental Income">Real Estate Rental Income</option>
                    <option value="Gifts / Other">Extraneous Gifts / Miscellaneous</option>
                </select>
            </div>

            <button type="submit" class="btn primary" style="width: 100%; margin-top: 16px; background: var(--accent-green); border-color: var(--accent-green);">Save Income Stream</button>
        </form>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="border: 1px solid var(--border);">
            <h3 style="margin-bottom: 16px;">This Month's Revenue</h3>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-secondary);">Total Gross Income:</span>
                <span style="display: flex; gap: 4px; font-size: 28px; color: var(--accent-green); font-weight: bold;">
                    <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                    <span class="amount" data-full-val="<?= (float)$totalIncome ?>"><?= number_format($totalIncome, 2) ?></span>
                </span>
            </div>

            <h4 style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">Revenue Allocation Matrix</h4>

            <?php if (empty($breakdown)): ?>
                <div style="color: var(--text-muted); font-size: 13px;">No income records registered for this active billing interval.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($breakdown as $type => $amount): 
                        $percentage = ($amount / $totalIncome) * 100;
                    ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                                <span style="color: var(--text-primary);"><?= htmlspecialchars($type) ?></span>
                                <div style="display: flex; gap: 4px;">
                                    <span class="currency-label" style="font-weight: bold;"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                                    <span class="amount" data-full-val="<?= (float)$amount ?>" style="font-weight: bold; margin-right: 8px;"><?= number_format($amount, 2) ?></span>
                                    <span style="color: var(--text-secondary);"><?= round($percentage) ?>%</span>
                                </div>
                            </div>
                            <div style="width: 100%; height: 6px; background: var(--bg-primary); border-radius: 3px; overflow: hidden;">
                                <div style="width: <?= $percentage ?>%; height: 100%; background: var(--accent-green); border-radius: 3px;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<h3 style="margin-bottom: 16px;">Income Ledger</h3>
<div class="card" style="padding: 0; overflow: hidden;">
    <?php if (empty($incomes)): ?>
        <div style="padding: 32px; text-align: center; color: var(--text-muted);">No inbound entries logged inside this active ledger array.</div>
    <?php else: ?>
        <?php foreach ($incomes as $inc): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border);">
                
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(63, 185, 80, 0.1); color: var(--accent-green); display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-circle-dollar-to-slot"></i>
                    </div>
                    <div>
                        <div style="font-weight: bold; color: var(--text-primary);">
                            <?= htmlspecialchars($inc['source_name']) ?>
                            <span style="font-size: 11px; padding: 2px 6px; background: var(--bg-primary); border: 1px solid var(--border); border-radius: 12px; margin-left: 8px; font-weight: normal; color: var(--text-secondary);">
                                <?= htmlspecialchars($inc['business_type']) ?>
                            </span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 6px;">
                            <?= date('M d, Y', strtotime($inc['date_received'])) ?> 
                            <?php if ($inc['platform']): ?> • <span style="color: var(--accent-blue);">Origin: <?= htmlspecialchars($inc['platform']) ?></span><?php endif; ?>
                            • Routing: Realized via <?= htmlspecialchars($inc['payment_method']) ?>
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 24px;">
                    <div style="display: flex; gap: 4px; font-weight: bold; color: var(--accent-green);">
                        + <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                        <span class="amount" data-full-val="<?= (float)$inc['amount'] ?>"><?= number_format($inc['amount'], 2) ?></span>
                    </div>
                    <button class="delete-income-btn ghost icon-btn" data-id="<?= $inc['id'] ?>" data-name="<?= htmlspecialchars($inc['source_name']) ?>" style="color: var(--accent-red);"><i class="fa-solid fa-trash-can"></i></button>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>