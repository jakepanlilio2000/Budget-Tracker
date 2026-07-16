<?php
declare(strict_types=1);
$pageTitle = 'Achievements';
ob_start();

$rarityColors = [
    'common' => '#94a3b8',
    'rare' => '#3b82f6',
    'epic' => '#8b5cf6',
    'legendary' => '#f59e0b',
    'hidden' => '#ef4444'
];
?>

<!-- RPG Header: Level, XP, Title -->
<div class="card glass mb-4"
    style="background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(139,92,246,0.1)); border: 1px solid var(--border-color);">
    <div class="flex-between" style="flex-wrap: wrap; gap: 1.5rem; align-items: center;">
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <div
                style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; font-weight: bold; box-shadow: 0 0 20px rgba(59,130,246,0.4);">
                <?= $stats['current_level'] ?>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.5rem;">Level <?= $stats['current_level'] ?> <span
                        style="font-size: 0.9rem; color: var(--text-secondary); font-weight: normal;">•
                        <?= number_format($stats['lifetime_fxp']) ?> FXP</span></h2>
                <div
                    style="width: 300px; max-width: 100%; height: 10px; background: var(--border-color); border-radius: 99px; margin-top: 0.5rem; overflow: hidden;">
                    <div
                        style="width: <?= $xpPercent ?>%; height: 100%; background: linear-gradient(90deg, var(--accent), #8b5cf6); transition: width 0.5s;">
                    </div>
                </div>
                <small class="text-secondary"><?= number_format($xpProgress) ?> / <?= number_format($xpNeeded) ?> FXP to
                    Level <?= $stats['current_level'] + 1 ?></small>
            </div>
        </div>
        <div style="text-align: right;">
            <small class="text-secondary" style="text-transform: uppercase; letter-spacing: 0.1em;">Financial
                Title</small>
            <h3 style="margin: 0.25rem 0 0; color: var(--accent); font-size: 1.5rem;"><?= e($stats['current_title']) ?>
            </h3>
            <?php if ($stats['prestige_stars'] > 0): ?>
                <small style="color: #f59e0b;"><i class="fas fa-star"></i> <?= $stats['prestige_stars'] ?> Prestige
                    Stars</small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mastery Tracks (New Section) -->
<div class="card glass mb-4">
    <h3 style="margin-bottom: 1rem;"><i class="fas fa-chart-line" style="color: var(--accent);"></i> Mastery Tracks</h3>
    <div class="grid grid-4" style="gap: 1rem;">
        <?php foreach (['expense' => 'Expense', 'income' => 'Income', 'savings' => 'Savings', 'budget' => 'Budget', 'consistency' => 'Consistency', 'planning' => 'Planning'] as $key => $label): ?>
            <?php $m = $masteries[$key] ?? ['level' => 1, 'xp' => 0, 'required' => 500, 'percent' => 0]; ?>
            <div
                style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px solid var(--border-color);">
                <div class="flex-between" style="margin-bottom: 0.5rem;">
                    <span style="font-weight: 700; font-size: 1.1rem;">Lv. <?= $m['level'] ?></span>
                    <span class="text-secondary" style="font-size: 0.75rem;"><?= $label ?></span>
                </div>
                <div style="background: var(--border-color); height: 6px; border-radius: 99px; overflow: hidden;">
                    <div
                        style="width: <?= $m['percent'] ?>%; height: 100%; background: var(--accent); transition: width 0.5s;">
                    </div>
                </div>
                <small class="text-secondary" style="font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                    <?= number_format($m['xp']) ?> / <?= number_format($m['required']) ?> XP
                </small>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- Streaks & Prestige Section -->
<div class="grid grid-2" style="gap: 1.5rem; margin-top: 1rem;">
    <!-- Streaks Card -->
    <div class="card glass">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-fire" style="color: #ef4444;"></i> Consistency Streaks</h3>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php
            $streakIcons = [
                'daily_login' => ['icon' => 'fa-sign-in-alt', 'label' => 'Daily Login'],
                'daily_transaction' => ['icon' => 'fa-receipt', 'label' => 'Daily Transaction'],
                'bill_payment' => ['icon' => 'fa-file-invoice', 'label' => 'On-Time Bills']
            ];
            foreach ($streakIcons as $type => $info):
                $s = $masteries['streaks'][$type] ?? ['current' => 0, 'best' => 0]; // Note: adjust key if streaks are passed separately
                $current = $fxpStats['streaks'][$type]['current'] ?? 0;
                $best = $fxpStats['streaks'][$type]['best'] ?? 0;
                ?>
                <div class="flex-between" style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas <?= $info['icon'] ?>" style="color: var(--text-secondary); width: 20px;"></i>
                        <span style="font-weight: 500;"><?= $info['label'] ?></span>
                    </div>
                    <div style="text-align: right;">
                        <span
                            style="font-weight: 800; color: <?= $current > 0 ? '#ef4444' : 'var(--text-secondary)' ?>; font-size: 1.1rem;">
                            <?= $current ?> 🔥
                        </span>
                        <small class="text-secondary" style="display: block;">Best: <?= $best ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Prestige Card -->
    <div class="card glass"
        style="<?= $stats['can_prestige'] ? 'border: 1px solid #f59e0b; background: rgba(245,158,11,0.05);' : '' ?>">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-star" style="color: #f59e0b;"></i> Prestige System</h3>

        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 3rem; color: #f59e0b; margin-bottom: 0.5rem;">
                <?= $stats['prestige_stars'] ?> <i class="fas fa-star"></i>
            </div>
            <p class="text-secondary" style="font-size: 0.9rem; margin-bottom: 1rem;">
                You have prestiged <strong><?= (int) $stats['prestige_stars'] ?></strong> time(s).<br>
                Current XP Multiplier: <strong
                    style="color: var(--success);"><?= number_format($stats['xp_multiplier'], 2) ?>x</strong>
            </p>

            <?php if ($stats['can_prestige']): ?>
                <form method="POST" action="<?= url('/achievements/prestige') ?>"
                    onsubmit="return confirm('⚠️ PRESTIGE WARNING ⚠️\n\nThis will reset your Level to 1 and Mastery Levels to 1.\n\nYou WILL keep:\n- Lifetime FXP\n- All Unlocked Achievements\n- All Streaks\n\nYou WILL gain:\n- +1 Prestige Star\n- +0.10x permanent XP Multiplier\n- Exclusive Prestige Title\n\nAre you sure?');">
                    <?= \App\Core\CSRF::field() ?>
                    <button type="submit" class="btn btn-primary"
                        style="width: 100%; background: linear-gradient(135deg, #f59e0b, #ef4444); font-weight: 700;">
                        <i class="fas fa-sync-alt"></i> Prestige Now (Requires Lv. 50)
                    </button>
                </form>
            <?php else: ?>
                <div class="btn"
                    style="width: 100%; background: var(--border-color); color: var(--text-secondary); cursor: not-allowed;">
                    <i class="fas fa-lock"></i> Unlock at Level 50
                </div>
                <small class="text-secondary" style="display: block; margin-top: 0.5rem;">
                    Reach Level 50 to reset and gain permanent bonuses.
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filters & Search -->
<div class="card glass mb-4">
    <form method="GET" action="<?= url('/achievements') ?>" class="flex-between" style="flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <?php foreach (['All', 'Wealth', 'Savings', 'Activity', 'Collections'] as $cat): ?>
                <a href="?cat=<?= $cat ?>" class="btn btn-sm"
                    style="background: <?= $filter === $cat ? 'var(--accent)' : 'transparent' ?>; color: <?= $filter === $cat ? 'white' : 'var(--text-primary)' ?>; border: 1px solid var(--border-color);">
                    <?= $cat ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div style="position: relative;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search achievements..."
                style="padding-left: 2.5rem; height: 38px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-glass-solid);">
            <i class="fas fa-search"
                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
        </div>
    </form>
</div>

<!-- Achievement Grid -->
<?php foreach ($grouped as $category => $items): ?>
    <h3 class="mt-4 mb-3"
        style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <?= e($category) ?> <span
            style="font-size: 0.8rem; font-weight: normal;">(<?= count(array_filter($items, fn($i) => $i['unlocked_at'])) ?>/<?= count($items) ?>)</span>
    </h3>
    <div class="grid grid-2">
        <?php foreach ($items as $a):
            $isUnlocked = !empty($a['unlocked_at']);
            $isHidden = $a['rarity'] === 'hidden' && !$isUnlocked;
            $progressPct = $a['target'] > 0 ? min(100, ($a['progress'] / $a['target']) * 100) : 0;
            $rColor = $rarityColors[$a['rarity']] ?? '#94a3b8';
            ?>
            <div class="card glass"
                style="border-left: 4px solid <?= $isUnlocked ? $rColor : 'var(--border-color)' ?>; opacity: <?= $isUnlocked ? '1' : '0.6' ?>;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div
                        style="width: 48px; height: 48px; border-radius: 12px; background: <?= $isUnlocked ? $rColor . '20' : 'rgba(0,0,0,0.05)' ?>; color: <?= $isUnlocked ? $rColor : 'var(--text-secondary)' ?>; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                        <i class="fas <?= $isHidden ? 'fa-question' : e($a['icon']) ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="flex-between">
                            <strong style="font-size: 0.95rem;"><?= $isHidden ? '???' : e($a['name']) ?></strong>
                            <span class="badge"
                                style="background: <?= $rColor ?>20; color: <?= $rColor ?>; font-size: 0.7rem; text-transform: uppercase;"><?= ucfirst(e($a['rarity'])) ?>
                                • <?= $a['xp_value'] ?> XP</span>
                        </div>
                        <small class="text-secondary" style="display: block; margin: 0.25rem 0 0.5rem;">
                            <?= $isHidden ? 'Keep playing to discover this secret...' : e($a['description']) ?>
                        </small>

                        <?php if (!$isUnlocked): ?>
                            <div style="background: var(--border-color); border-radius: 99px; height: 6px; overflow: hidden;">
                                <div
                                    style="width: <?= $progressPct ?>%; height: 100%; background: <?= $rColor ?>; transition: width 0.5s;">
                                </div>
                            </div>
                            <small class="text-secondary" style="font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                                <?= number_format((float) $a['progress']) ?> / <?= number_format((float) $a['target']) ?>
                            </small>
                        <?php else: ?>
                            <small style="color: <?= $rColor ?>; font-weight: 600; font-size: 0.8rem;">
                                <i class="fas fa-check-circle"></i> Unlocked <?= e(date('M d, Y', strtotime($a['unlocked_at']))) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<!-- Lifetime Statistics & Next Milestones -->
<div class="grid grid-2" style="gap: 1.5rem; margin-top: 1.5rem;">

    <!-- Lifetime Statistics -->
    <div class="card glass">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-chart-bar" style="color: var(--accent);"></i> Lifetime
            Statistics</h3>
        <div class="grid grid-2" style="gap: 1rem;">
            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">Total Transactions
                </div>
                <div style="font-size: 1.25rem; font-weight: 700;">
                    <?= number_format($lifetimeStats['total_transactions']) ?></div>
            </div>
            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">Total Income</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--success);">
                    <?= $baseCurrency['symbol'] ?? '$' ?><?= number_format($lifetimeStats['total_income']) ?></div>
            </div>
            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">Total Savings</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--accent);">
                    <?= $baseCurrency['symbol'] ?? '$' ?><?= number_format($lifetimeStats['total_savings']) ?></div>
            </div>
            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">Goals Completed</div>
                <div style="font-size: 1.25rem; font-weight: 700;">
                    <?= number_format($lifetimeStats['goals_completed']) ?></div>
            </div>
            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">Bills Paid</div>
                <div style="font-size: 1.25rem; font-weight: 700;"><?= number_format($lifetimeStats['bills_paid']) ?>
                </div>
            </div>
            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">Longest Streak</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #ef4444;">
                    <?= number_format($lifetimeStats['longest_streak']) ?> Days</div>
            </div>
            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">Highest Level</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #f59e0b;">
                    <?= number_format($lifetimeStats['highest_level']) ?></div>
            </div>
            <div style="padding: 0.75rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">Lifetime FXP</div>
                <div style="font-size: 1.25rem; font-weight: 700;"><?= number_format($lifetimeStats['lifetime_fxp']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Milestones (Dynamic Preview) -->
    <div class="card glass">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-flag-checkered" style="color: var(--success);"></i> Next
            Milestones</h3>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php
            // Find the first 3 locked chain achievements to show as "Next Milestones"
            $nextMilestones = [];
            foreach ($grouped as $cat => $items) {
                foreach ($items as $item) {
                    if (empty($item['unlocked_at']) && $item['is_chain']) {
                        $nextMilestones[] = $item;
                        if (count($nextMilestones) >= 3)
                            break 2;
                    }
                }
            }
            ?>

            <?php if (empty($nextMilestones)): ?>
                <p class="text-secondary" style="text-align: center; padding: 2rem;">You are a true Financial Legend! All
                    current milestones achieved.</p>
            <?php else: ?>
                <?php foreach ($nextMilestones as $milestone): ?>
                    <?php $pct = $milestone['target'] > 0 ? min(100, ($milestone['progress'] / $milestone['target']) * 100) : 0; ?>
                    <div>
                        <div class="flex-between" style="margin-bottom: 0.25rem;">
                            <span style="font-size: 0.9rem; font-weight: 600;"><i class="fas <?= e($milestone['icon']) ?>"
                                    style="color: <?= e($milestone['color']) ?>; margin-right: 0.5rem;"></i>
                                <?= e($milestone['name']) ?></span>
                            <span class="text-secondary"
                                style="font-size: 0.8rem;"><?= number_format((float) $milestone['progress']) ?> /
                                <?= number_format((float) $milestone['target']) ?></span>
                        </div>
                        <div style="background: var(--border-color); height: 6px; border-radius: 99px; overflow: hidden;">
                            <div
                                style="width: <?= $pct ?>%; height: 100%; background: <?= e($milestone['color']) ?>; transition: width 0.5s;">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>