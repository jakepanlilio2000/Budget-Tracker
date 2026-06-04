<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>🛍️ Daily Spends & Shopping</h1>
        <p style="color: var(--text-secondary);">Log personal materials, physical receipts, and on-the-fly cash purchases.</p>
    </div>
</header>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; align-items: start; margin-bottom: 32px;">
    
    <!-- Quick Entry Form -->
    <div class="card">
        <h3 style="margin-bottom: 16px;">Log New Purchase</h3>
        <form action="<?= $basePath ?>/shopping/<?= $profile['id'] ?>/store" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label>Item / Material Description</label>
                <input type="text" name="item_name" required placeholder="e.g., Weekly Groceries, PC Components, Skincare Restock" autofocus>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Amount (<?= htmlspecialchars($profile['currency']) ?>)</label>
                    <input type="number" name="amount" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Store / Vendor</label>
                    <input type="text" name="store_name" placeholder="Optional (e.g. Amazon, SM Mall)">
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="Cash">💵 Cash</option>
                        <option value="Cash On Delivery">📦 Cash On Delivery (COD)</option>
                        <option value="GCash">📱 GCash</option>
                        <option value="Maya">💳 Maya</option>
                        <option value="ShopeePay">🦊 ShopeePay</option>
                        <option value="Credit/Debit Card">🪪 Credit/Debit Card</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: rgba(88, 166, 255, 0.05); border: 1px solid var(--border); border-radius: 8px; margin-top: 8px;">
                <div>
                    <label style="margin: 0; display: block; font-weight: bold; color: var(--text-primary);">Is this a Necessity?</label>
                    <span style="font-size: 11px; color: var(--text-secondary);">Uncheck if this was an impulse buy or a "Want".</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="is_need" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <button type="submit" class="btn primary" style="width: 100%; margin-top: 16px;">Log Purchase</button>
        </form>
    </div>

    <!-- Monthly Summary / Guilt Tracker -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="border: 1px solid var(--border);">
            <h3 style="margin-bottom: 16px;">This Month's Breakdown</h3>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <span style="color: var(--text-secondary);">Total Shopping/Spends:</span>
                <span class="amount outflow" style="font-size: 24px;"><?= $profile['currency'] ?> <?= number_format($totalSpent, 2) ?></span>
            </div>

            <?php 
            $needPct = $totalSpent > 0 ? ($totalNeeds / $totalSpent) * 100 : 0;
            $wantPct = $totalSpent > 0 ? ($totalWants / $totalSpent) * 100 : 0;
            ?>

            <!-- Progress Bar -->
            <div style="width: 100%; height: 8px; background: var(--bg-primary); border-radius: 4px; overflow: hidden; display: flex; margin-bottom: 16px;">
                <div style="width: <?= $needPct ?>%; background: var(--accent-green);"></div>
                <div style="width: <?= $wantPct ?>%; background: var(--accent-red);"></div>
            </div>

            <div style="display: flex; justify-content: space-between;">
                <div>
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--accent-green); margin-right: 4px;"></span>
                    <span style="color: var(--text-secondary); font-size: 13px;">Needs</span>
                    <div class="amount" style="font-weight: bold; margin-top: 4px;"><?= $profile['currency'] ?> <?= number_format($totalNeeds, 2) ?></div>
                </div>
                <div style="text-align: right;">
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--accent-red); margin-right: 4px;"></span>
                    <span style="color: var(--text-secondary); font-size: 13px;">Wants (Impulse)</span>
                    <div class="amount" style="font-weight: bold; margin-top: 4px;"><?= $profile['currency'] ?> <?= number_format($totalWants, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Log -->
<h3 style="margin-bottom: 16px;">Recent Expenditures</h3>
<div class="card" style="padding: 0; overflow: hidden;">
    <?php if (empty($purchases)): ?>
        <div style="padding: 32px; text-align: center; color: var(--text-muted);">No purchases logged yet.</div>
    <?php else: ?>
        <?php foreach ($purchases as $p): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border);">
                
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: <?= $p['is_need'] ? 'rgba(63, 185, 80, 0.1)' : 'rgba(248, 81, 73, 0.1)' ?>; color: <?= $p['is_need'] ? 'var(--accent-green)' : 'var(--accent-red)' ?>; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <?= $p['is_need'] ? '😇' : '😈' ?>
                    </div>
                    <div>
                        <div style="font-weight: bold; color: var(--text-primary);"><?= htmlspecialchars($p['item_name']) ?></div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                            <?= date('M d, Y', strtotime($p['purchase_date'])) ?> 
                            <?php if ($p['store_name']): ?> • <?= htmlspecialchars($p['store_name']) ?><?php endif; ?>
                            • Paid via <span style="color: var(--accent-blue);"><?= htmlspecialchars($p['payment_method']) ?></span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 24px;">
                    <div class="amount" style="font-weight: bold;">
                        <?= $profile['currency'] ?> <?= number_format($p['amount'], 2) ?>
                    </div>
                    <button class="delete-spend-btn" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['item_name']) ?>">🗑️</button>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>