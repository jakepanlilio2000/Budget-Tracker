<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>💳 Radar</h1>
        <p style="color: var(--text-secondary);">Manage your recurring subscriptions and track active debts.</p>
    </div>
</header>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    
    <!-- Subscriptions Panel -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid var(--border); background: var(--bg-elevated); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Recurring Services</h3>
            <span style="font-size: 14px; color: var(--text-secondary);">
                Fixed Burn: <strong style="color: var(--accent-red); font-family: 'JetBrains Mono';"><?= $profile['currency'] ?> <?= number_format($monthlyFixed, 2) ?></strong> / mo
            </span>
        </div>
        
        <div style="display: flex; flex-direction: column;">
            <?php if(empty($subscriptions)): ?>
                <div style="padding: 24px; text-align: center; color: var(--text-muted);">No active monthly or annual subscriptions.</div>
            <?php else: ?>
                <?php foreach($subscriptions as $sub): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 20px;"><?= htmlspecialchars($sub['category_icon'] ?? '🔄') ?></span>
                        <div>
                            <span style="font-weight: bold; display: block; font-size: 15px;"><?= htmlspecialchars($sub['name']) ?></span>
                            <span style="font-size: 12px; color: var(--text-secondary); text-transform: uppercase;"><?= $sub['frequency_type'] ?> • <?= htmlspecialchars($sub['category_name']) ?></span>
                        </div>
                    </div>
                    <span class="amount outflow" style="font-size: 16px;"><?= $profile['currency'] ?> <?= number_format($sub['amount'], 2) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Debt & Installment Snowball Panel -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid var(--border); background: var(--bg-elevated);">
            <h3 style="margin: 0;">Debt & Installment Snowball</h3>
        </div>

        <div style="padding: 16px; display: flex; flex-direction: column; gap: 16px;">
            <?php if(empty($debts)): ?>
                <div style="padding: 24px; text-align: center; color: var(--text-muted);">You are entirely debt-free in the tracker. Excellent!</div>
            <?php else: ?>
                <?php foreach($debts as $debt): 
                    $monthsTotal = (int)$debt['total_months'];
                    $monthsPaid = (int)$debt['months_paid'];
                    $pct = $monthsTotal > 0 ? ($monthsPaid / $monthsTotal) * 100 : 0;
                    $isComplete = $monthsPaid >= $monthsTotal;
                ?>
                <div style="background: var(--bg-primary); border: 1px solid var(--border); border-radius: 8px; padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div>
                            <span style="font-weight: bold; font-size: 16px;"><?= htmlspecialchars($debt['name']) ?></span>
                            <span style="display: block; font-size: 12px; color: var(--text-secondary); margin-top: 4px;"><?= htmlspecialchars($debt['category_name']) ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span class="amount outflow" style="display: block; font-size: 16px;"><?= $profile['currency'] ?> <?= number_format($debt['amount'], 2) ?> / mo</span>
                            <span style="font-size: 12px; color: var(--text-secondary); font-weight: bold;"><?= $monthsPaid ?> of <?= $monthsTotal ?> payments</span>
                        </div>
                    </div>
                    
                    <div class="progress-bg" style="height: 8px; border-radius: 4px; background: var(--bg-elevated); overflow: hidden;">
                        <div class="progress-fill" style="height: 100%; width: <?= $pct ?>%; background: <?= $isComplete ? 'var(--accent-green)' : 'var(--accent-blue)' ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 1024px) {
    div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>