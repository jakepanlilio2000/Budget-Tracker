<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<style>
    .radar-grid {
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 24px; 
        align-items: start;
    }
    .radar-flex-row {
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 12px;
    }
    .radar-text-right {
        text-align: right;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }
    
    @media (max-width: 900px) {
        .radar-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 480px) {
        .radar-flex-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        .radar-text-right {
            text-align: left;
            align-items: flex-start;
        }
    }
</style>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-satellite-dish" style="color: var(--accent-blue); margin-right: 8px;"></i> Radar</h1>
        <p style="color: var(--text-secondary);">Manage your recurring subscriptions and track active debts.</p>
    </div>
</header>

<div class="radar-grid">
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid var(--border); background: var(--bg-elevated); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0;">Recurring Services</h3>
            <span style="font-size: 14px; color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
                Fixed Burn: 
                <strong style="color: var(--accent-red); font-family: 'JetBrains Mono'; display: flex; align-items: center; gap: 4px;">
                    <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span> 
                    <span class="amount" data-full-val="<?= (float)($monthlyFixed ?? 0) ?>"><?= number_format($monthlyFixed ?? 0, 2) ?></span>
                    <span>/ mo</span>
                </strong>
            </span>
        </div>
        
        <div style="display: flex; flex-direction: column;">
            <?php if(empty($subscriptions)): ?>
                <div style="padding: 24px; text-align: center; color: var(--text-muted);">No active monthly or annual subscriptions.</div>
            <?php else: ?>
                <?php foreach($subscriptions as $sub): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 20px; color: var(--text-secondary);">
                            <?php if(empty($sub['category_icon']) || $sub['category_icon'] === '🔄'): ?>
                                <i class="fa-solid fa-rotate"></i>
                            <?php else: ?>
                                <?= htmlspecialchars($sub['category_icon']) ?>
                            <?php endif; ?>
                        </span>
                        <div>
                            <span style="font-weight: bold; display: flex; align-items: center; gap: 8px; font-size: 15px;">
                                <?= htmlspecialchars($sub['name']) ?>
                                <?= $sub['status_badge'] ?>
                            </span>
                            <span style="font-size: 12px; color: var(--text-secondary); text-transform: uppercase;"><?= htmlspecialchars($sub['frequency_type']) ?> • <?= htmlspecialchars($sub['category_name']) ?></span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px; font-weight: bold; color: var(--accent-red); font-size: 16px;">
                        <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                        <span class="amount" data-full-val="<?= (float)$sub['amount'] ?>"><?= number_format($sub['amount'], 2) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

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
                    
                    // Parse the dynamically calculated fractions from the DB (e.g., 2.5 months paid)
                    $monthsPaidRaw = (float)($debt['calculated_months_paid'] ?? 0);
                    $monthsPaid = rtrim(rtrim(number_format($monthsPaidRaw, 2), '0'), '.');
                    if ($monthsPaid === '') $monthsPaid = '0';
                    
                    $pct = $monthsTotal > 0 ? ($monthsPaidRaw / $monthsTotal) * 100 : 0;
                    $isComplete = $monthsPaidRaw >= $monthsTotal;
                ?>
                <div style="background: var(--bg-primary); border: 1px solid var(--border); border-radius: 8px; padding: 16px;">
                    <div class="radar-flex-row">
                        <div>
                            <span style="font-weight: bold; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                                <?= htmlspecialchars($debt['name']) ?>
                                <?= $debt['status_badge'] ?>
                            </span>
                            <span style="display: block; font-size: 12px; color: var(--text-secondary); margin-top: 4px;"><?= htmlspecialchars($debt['category_name']) ?></span>
                        </div>
                        <div class="radar-text-right">
                            <div style="display: flex; align-items: center; gap: 4px; font-weight: bold; color: var(--accent-red); font-size: 16px; margin-bottom: 4px;">
                                <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                                <span class="amount" data-full-val="<?= (float)$debt['amount'] ?>"><?= number_format($debt['amount'], 2) ?></span>
                                <span>/ mo</span>
                            </div>
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