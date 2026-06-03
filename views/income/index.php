<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>💰 Income & Revenue</h1>
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
                <input type="text" name="source_name" required placeholder="e.g., Tech Corp Salary, Web Design Project, Sold Laptop" autofocus>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Platform (Where)</label>
                    <input type="text" name="platform" placeholder="e.g., Upwork, Shopify, Office">
                </div>
                <div class="form-group">
                    <label>Amount (<?= htmlspecialchars($profile['currency']) ?>)</label>
                    <input type="number" name="amount" step="0.01" required placeholder="0.00">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Date Received (When)</label>
                    <input type="date" name="date_received" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Payment Method (How)</label>
                    <select name="payment_method">
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash / Maya</option>
                        <option value="PayPal">PayPal / Stripe</option>
                        <option value="Crypto">Cryptocurrency</option>
                        <option value="Check">Check</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 8px;">
                <label style="color: var(--accent-green);">Business / Income Type</label>
                <select name="business_type" style="border-color: rgba(63, 185, 80, 0.4);">
                    <option value="Fixed Salary">Fixed Salary / Wages</option>
                    <option value="Freelance / Consulting">Freelance / Consulting</option>
                    <option value="E-commerce / Sales">E-commerce / Product Sales</option>
                    <option value="Investments / Dividends">Investments / Dividends</option>
                    <option value="Rental Income">Rental Income</option>
                    <option value="Gifts / Other">Gifts / Other</option>
                </select>
            </div>

            <button type="submit" class="btn primary" style="width: 100%; margin-top: 16px; background: var(--accent-green); border-color: var(--accent-green);">Save Income</button>
        </form>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="border: 1px solid var(--border);">
            <h3 style="margin-bottom: 16px;">This Month's Revenue</h3>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-secondary);">Total Gross Income:</span>
                <span class="amount" style="font-size: 28px; color: var(--accent-green);"><?= $profile['currency'] ?> <?= number_format($totalIncome, 2) ?></span>
            </div>

            <h4 style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">Revenue Streams</h4>

            <?php if (empty($breakdown)): ?>
                <div style="color: var(--text-muted); font-size: 13px;">No income recorded this month.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($breakdown as $type => $amount): 
                        $percentage = ($amount / $totalIncome) * 100;
                    ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                                <span style="color: var(--text-primary);"><?= htmlspecialchars($type) ?></span>
                                <div>
                                    <span class="amount" style="font-weight: bold; margin-right: 8px;"><?= $profile['currency'] ?> <?= number_format($amount, 2) ?></span>
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
        <div style="padding: 32px; text-align: center; color: var(--text-muted);">No revenue logged yet.</div>
    <?php else: ?>
        <?php foreach ($incomes as $inc): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border);">
                
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(63, 185, 80, 0.1); color: var(--accent-green); display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        +
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
                            <?php if ($inc['platform']): ?> • <span style="color: var(--accent-blue);">Via <?= htmlspecialchars($inc['platform']) ?></span><?php endif; ?>
                            • Paid by <?= htmlspecialchars($inc['payment_method']) ?>
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 24px;">
                    <div class="amount" style="font-weight: bold; color: var(--accent-green);">
                        <?= $profile['currency'] ?> <?= number_format($inc['amount'], 2) ?>
                    </div>
                    
                    <form action="<?= $basePath ?>/income/delete/<?= $inc['id'] ?>" method="POST" onsubmit="return confirm('Delete this income record?');" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="icon-btn" style="color: var(--text-muted);">&times;</button>
                    </form>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>