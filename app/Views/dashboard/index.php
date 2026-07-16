<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Dashboard';
ob_start();

// Apply Dashboard Builder Config (Hide widgets if configured)
if (!empty($dashboardConfig['widgets'])) {
    echo '<style>';
    foreach ($dashboardConfig['widgets'] as $w) {
        if (isset($w['visible']) && !$w['visible']) {
            echo ".dashboard-widget[data-id='{$w['id']}'] { display: none !important; }";
        }
    }
    echo '</style>';
}
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Dashboard</h1>
        <p class="text-secondary">Welcome back, <?= e($user['full_name'] ?? 'User') ?>.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button id="customizeDashboardBtn" class="btn"
            style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary);">
            <i class="fas fa-edit"></i> Customize
        </button>
        <div class="dashboard-controls">
            <button id="saveDashboardBtn" class="btn btn-primary" style="display: none;">
                <i class="fas fa-check"></i> Done
            </button>
            <button id="resetDashboardBtn" class="btn"
                style="background: var(--text-secondary); color: white; display: none;">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>
    </div>
</div>

<div class="dashboard-grid" id="dashboardGrid">

    <!-- Widget: monthly_stats -->
    <div class="dashboard-widget" data-id="monthly_stats" data-visible="1" data-size="normal">
        <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
        <div class="grid grid-3 mb-4">
            <div class="card glass stat-card">
                <div class="stat-icon income"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Monthly Income</span>
                    <h3 id="stat-income" class="sensitive-data stat-value">
                        <?= $baseCurrency['symbol'] ?>0.00
                        <i class="fas fa-eye widget-eye-toggle" data-target="#stat-income" title="Click to reveal"></i>
                    </h3>
                </div>
            </div>
            <div class="card glass stat-card">
                <div class="stat-icon expense"><i class="fas fa-arrow-down"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Monthly Expenses</span>
                    <h3 id="stat-expense" class="sensitive-data stat-value">
                        <?= $baseCurrency['symbol'] ?>0.00
                        <i class="fas fa-eye widget-eye-toggle" data-target="#stat-expense" title="Click to reveal"></i>
                    </h3>
                </div>
            </div>
            <div class="card glass stat-card">
                <div class="stat-icon balance"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Net Cash Flow</span>
                    <h3 id="stat-flow" class="sensitive-data stat-value">
                        <?= $baseCurrency['symbol'] ?>0.00
                        <i class="fas fa-eye widget-eye-toggle" data-target="#stat-flow" title="Click to reveal"></i>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget: rpg_stats -->
    <div class="dashboard-widget" data-id="rpg_stats" data-visible="1" data-size="normal">
        <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
        <div class="card glass mb-4"
            style="background: linear-gradient(135deg, rgba(59,130,246,0.05), rgba(139,92,246,0.05));">
            <div class="flex-between" style="align-items: center; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div
                        style="width: 50px; height: 50px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; font-weight: bold;">
                        <?= $rpgStats['level'] ?>
                    </div>
                    <div>
                        <small class="text-secondary">Level
                            <?= $rpgStats['level'] ?> •
                            <?= number_format($rpgStats['total_xp']) ?> XP
                        </small>
                        <h4 style="margin: 0; color: var(--accent);">
                            <?= e($rpgStats['wealth_tier']) ?>
                        </h4>
                    </div>
                </div>
                <a href="<?= url('/achievements') ?>" class="btn btn-sm"
                    style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary);">
                    <i class="fas fa-trophy"></i> View All
                </a>
            </div>
        </div>
    </div>
    <!-- Widget: net_income_pending -->
    <div class="dashboard-widget" data-id="net_income_pending" data-visible="1" data-size="normal">
        <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
        <div class="grid grid-2 mb-4">
            <div class="card glass">
                <h3>Monthly Net Income</h3>
                <?php
                $income = (float) ($netIncome['total_income'] ?? 0);
                $expense = (float) ($netIncome['total_expense'] ?? 0);
                $net = $income - $expense;
                ?>
                <div style="display: flex; align-items: baseline; gap: 1rem; margin-top: 1rem;">
                    <h1 class="sensitive-data"
                        style="color: <?= $net >= 0 ? 'var(--success)' : 'var(--danger)' ?>; font-size: 2rem; margin: 0;">
                        <?= e($baseCurrency['symbol']) ?><?= number_format((float) $net, 2) ?>
                    </h1>
                </div>
                <div class="flex-between" style="margin-top: 1rem; font-size: 0.9rem; flex-wrap: wrap; gap: 0.5rem;">
                    <span class="sensitive-data" style="color: var(--success);">
                        <i class="fas fa-arrow-up"></i> In:
                        <?= e($baseCurrency['symbol']) ?><?= number_format((float) $income, 2) ?>
                    </span>
                    <span class="sensitive-data" style="color: var(--danger);">
                        <i class="fas fa-arrow-down"></i> Out:
                        <?= e($baseCurrency['symbol']) ?><?= number_format((float) $expense, 2) ?>
                    </span>
                </div>
            </div>

            <div class="card glass">
                <div class="flex-between">
                    <h3>Upcoming Pending</h3>
                    <a href="<?= url('/pending-ledger') ?>" class="link" style="font-size: 0.85rem;">View All</a>
                </div>
                <?php if (empty($pendingItems)): ?>
                    <p class="text-secondary" style="margin-top: 1rem;">No upcoming pending items.</p>
                <?php else: ?>
                    <ul style="list-style: none; margin-top: 1rem; padding: 0;">
                        <?php foreach ($pendingItems as $item): ?>
                            <li class="flex-between"
                                style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 0.5rem;">
                                <span style="font-size: 0.9rem;">
                                    <?= e($item['description']) ?>
                                    <small class="text-secondary">(<?= e(date('M d', strtotime($item['due_date']))) ?>)</small>
                                </span>
                                <span
                                    style="color: <?= $item['type'] === 'income' ? 'var(--success)' : 'var(--danger)' ?>; font-weight: 600;">
                                    <?= $item['type'] === 'income' ? '+' : '-' ?>         <?= e($item['symbol']) ?>
                                    <?= number_format((float) $item['amount'], 2) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Widget: savings_vaults -->
    <?php if (!empty($topVaults)): ?>
        <div class="dashboard-widget" data-id="savings_vaults" data-visible="1" data-size="normal">
            <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
            <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
            <div class="card glass mb-4">
                <div class="flex-between">
                    <h3><i class="fas fa-piggy-bank" style="color: var(--accent);"></i> Savings Goals</h3>
                    <a href="<?= url('/vaults') ?>" class="link" style="font-size: 0.85rem;">View All</a>
                </div>
                <div class="grid grid-3 mt-3" style="gap: 1rem;">
                    <?php foreach ($topVaults as $v): ?>
                        <!-- FIX: Changed hardcoded rgba(0,0,0,0.02) to var(--bg-glass) -->
                        <div
                            style="padding: 1rem; background: var(--bg-glass); border-radius: 8px; border: 1px solid var(--border-color);">
                            <h4 style="margin: 0 0 0.5rem; font-size: 0.95rem;"><?= e($v['name']) ?></h4>
                            <div
                                style="background: var(--border-color); border-radius: 99px; height: 6px; overflow: hidden; margin-bottom: 0.5rem;">
                                <div
                                    style="width: <?= $v['metrics']['percentage'] ?>%; height: 100%; background: var(--accent);">
                                </div>
                            </div>
                            <div class="flex-between" style="font-size: 0.8rem; color: var(--text-secondary);">
                                <span
                                    class="sensitive-data"><?= $baseCurrency['symbol'] ?><?= number_format((float) $v['current_amount'], 2) ?></span>
                                <span><?= $v['metrics']['percentage'] ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Widget: fxp_mastery -->
    <div class="dashboard-widget" data-id="fxp_mastery" data-visible="1" data-size="normal">
        <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
        <div class="card glass mb-4">
            <div class="flex-between" style="margin-bottom: 1rem;">
                <h3><i class="fas fa-bolt" style="color: #f59e0b;"></i> Financial Mastery</h3>
                <span class="badge" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                    <?= e($fxpStats['global']['current_title']) ?>
                </span>
            </div>

            <div style="margin-bottom: 1rem;">
                <div class="flex-between" style="font-size: 0.85rem; margin-bottom: 0.25rem;">
                    <span>Level
                        <?= $fxpStats['global']['current_level'] ?>
                    </span>
                    <span class="text-secondary">
                        <?= number_format($fxpStats['global']['xp_progress']) ?> /
                        <?= number_format($fxpStats['global']['xp_needed']) ?> FXP
                    </span>
                </div>
                <div style="background: var(--border-color); border-radius: 99px; height: 8px; overflow: hidden;">
                    <div
                        style="width: <?= $fxpStats['global']['progress_percent'] ?>%; height: 100%; background: linear-gradient(90deg, #f59e0b, #ef4444); transition: width 0.5s;">
                    </div>
                </div>
            </div>

            <div class="grid grid-3" style="gap: 0.5rem; font-size: 0.8rem;">
                <?php foreach (['expense', 'savings', 'income'] as $track): ?>
                    <?php $m = $fxpStats['masteries'][$track] ?? ['level' => 1, 'percent' => 0]; ?>
                    <div style="text-align: center; padding: 0.5rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <div style="font-weight: 700; color: var(--text-primary);">Lv.
                            <?= $m['level'] ?>
                        </div>
                        <div class="text-secondary" style="font-size: 0.7rem; text-transform: uppercase;">
                            <?= $track ?>
                        </div>
                        <div
                            style="background: var(--border-color); height: 4px; border-radius: 99px; margin-top: 0.25rem; overflow: hidden;">
                            <div style="width: <?= $m['percent'] ?>%; height: 100%; background: var(--accent);"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>


    <div
        style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color); display: flex; justify-content: center; gap: 2rem;">
        <div style="text-align: center;">
            <div style="font-size: 1.25rem; font-weight: 800; color: #ef4444;"><i class="fas fa-fire"></i>
                <?= $lifetimeStats['longest_streak'] ?? 0 ?>
            </div>
            <div class="text-secondary" style="font-size: 0.7rem; text-transform: uppercase;">Best Streak</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 1.25rem; font-weight: 800; color: #f59e0b;"><i class="fas fa-star"></i>
                <?= $fxpStats['global']['prestige_stars'] ?>
            </div>
            <div class="text-secondary" style="font-size: 0.7rem; text-transform: uppercase;">Prestige</div>
        </div>
    </div>
    <!-- Widget: achievements -->
    <?php if (!empty($recentAchievements)): ?>
        <div class="dashboard-widget" data-id="achievements" data-visible="1" data-size="normal">
            <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
            <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
            <div class="card glass mb-4" style="border-left: 4px solid #f59e0b;">
                <div class="flex-between">
                    <h3><i class="fas fa-trophy" style="color: #f59e0b;"></i> Recent Achievements</h3>
                    <a href="<?= url('/achievements') ?>" class="link" style="font-size: 0.85rem;">View All</a>
                </div>
                <div class="grid grid-3 mt-3" style="gap: 1rem;">
                    <?php foreach ($recentAchievements as $ach): ?>
                        <div
                            style="text-align: center; padding: 0.75rem; background: rgba(245,158,11,0.05); border-radius: 8px;">
                            <i class="fas <?= e($ach['icon']) ?>"
                                style="color: <?= e($ach['color']) ?>; font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                            <div style="font-size: 0.85rem; font-weight: 600;"><?= e($ach['name']) ?></div>
                            <small class="text-secondary"><?= e(date('M d', strtotime($ach['unlocked_at']))) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Widget: recent_timeline -->
    <?php if (!empty($recentTimeline)): ?>
        <div class="dashboard-widget" data-id="recent_timeline" data-visible="1" data-size="normal">
            <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
            <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
            <div class="card glass mb-4">
                <div class="flex-between">
                    <h3><i class="fas fa-history" style="color: var(--accent);"></i> Recent Activity</h3>
                    <a href="<?= url('/timeline') ?>" class="link" style="font-size: 0.85rem;">View All</a>
                </div>
                <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($recentTimeline as $evt): ?>
                        <!-- FIX: Changed hardcoded rgba(0,0,0,0.02) to var(--bg-glass) -->
                        <div
                            style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; background: var(--bg-glass); border-radius: 8px;">
                            <div
                                style="width: 32px; height: 32px; border-radius: 50%; background: <?= e($evt['color']) ?>20; color: <?= e($evt['color']) ?>; display: flex; align-items: center; justify-content: center;">
                                <i class="fas <?= e($evt['icon']) ?>"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 0.9rem; font-weight: 500;"><?= e($evt['description']) ?></div>
                                <small
                                    class="text-secondary"><?= e(date('M d, h:i A', strtotime($evt['created_at']))) ?></small>
                            </div>
                            <?php if ($evt['amount'] > 0): ?>
                                <div class="sensitive-data"
                                    style="font-weight: bold; font-size: 0.9rem; color: <?= $evt['action'] === 'deposit' || $evt['action'] === 'income' ? 'var(--success)' : 'var(--danger)' ?>">
                                    <?= $evt['action'] === 'deposit' || $evt['action'] === 'income' ? '+' : '-' ?>
                                    <?= $baseCurrency['symbol'] ?>             <?= number_format((float) $evt['amount'], 2) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Widget: cash_flow_forecast -->
    <div class="dashboard-widget" data-id="cash_flow_forecast" data-visible="1" data-size="normal">
        <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
        <div class="card glass mb-4"
            style="<?= !empty($cashFlowForecast['warnings']) ? 'border-left: 4px solid var(--danger);' : '' ?>">
            <div class="flex-between">
                <h3><i class="fas fa-chart-line" style="color: var(--accent);"></i> 7-Day Cash Flow</h3>
                <a href="<?= url('/forecast?days=7') ?>" class="link" style="font-size: 0.85rem;">Full Forecast</a>
            </div>
            <div class="grid grid-3 mt-3" style="gap: 1rem; text-align: center;">
                <div>
                    <small class="text-secondary">Current</small>
                    <h4 class="sensitive-data" style="margin: 0.25rem 0 0;">
                        <?= $baseCurrency['symbol'] ?><?= number_format((float) $cashFlowForecast['current_balance'], 2) ?>
                    </h4>
                </div>
                <div>
                    <small class="text-secondary">Net Flow</small>
                    <h4 class="sensitive-data"
                        style="margin: 0.25rem 0 0; color: <?= $cashFlowForecast['net_flow'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                        <?= $cashFlowForecast['net_flow'] >= 0 ? '+' : '' ?><?= $baseCurrency['symbol'] ?><?= number_format((float) $cashFlowForecast['net_flow'], 2) ?>
                    </h4>
                </div>
                <div>
                    <small class="text-secondary">Projected</small>
                    <h4 class="sensitive-data"
                        style="margin: 0.25rem 0 0; color: <?= $cashFlowForecast['final_balance'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                        <?= $baseCurrency['symbol'] ?><?= number_format((float) $cashFlowForecast['final_balance'], 2) ?>
                    </h4>
                </div>
            </div>
            <?php if (in_array('cash_shortage', $cashFlowForecast['warnings'])): ?>
                <div class="alert alert-danger mt-3" style="margin-bottom: 0; padding: 0.5rem; font-size: 0.85rem;">
                    <i class="fas fa-exclamation-triangle"></i> Warning: Projected cash shortage in the next 7 days.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Widget: upcoming_events -->
    <?php if (!empty($upcomingEvents)): ?>
        <div class="dashboard-widget" data-id="upcoming_events" data-visible="1" data-size="normal">
            <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
            <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
            <div class="card glass mb-4">
                <div class="flex-between">
                    <h3><i class="fas fa-calendar-alt" style="color: var(--accent);"></i> Upcoming Events</h3>
                    <a href="<?= url('/calendar') ?>" class="link" style="font-size: 0.85rem;">Open Calendar</a>
                </div>
                <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($upcomingEvents as $evt): ?>
                        <!-- FIX: Changed hardcoded rgba(0,0,0,0.02) to var(--bg-glass) -->
                        <div
                            style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; background: var(--bg-glass); border-radius: 8px; border-left: 3px solid <?= e($evt['color']) ?>;">
                            <i class="fas <?= e($evt['icon']) ?>"
                                style="color: <?= e($evt['color']) ?>; width: 20px; text-align: center;"></i>
                            <div style="flex: 1; font-size: 0.9rem;">
                                <div style="font-weight: 500;"><?= e($evt['title']) ?></div>
                                <small class="text-secondary"><?= e(date('M d', strtotime($evt['start']))) ?></small>
                            </div>
                            <div class="sensitive-data"
                                style="font-weight: bold; font-size: 0.9rem; color: <?= e($evt['color']) ?>">
                                <?= e($evt['currency_symbol']) ?>         <?= number_format($evt['amount'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Widget: bills_salary -->
    <div class="dashboard-widget" data-id="bills_salary" data-visible="1" data-size="normal">
        <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
        <div class="grid grid-2 mb-4">
            <div class="card glass">
                <div class="flex-between">
                    <h3>Upcoming Bills</h3>
                    <a href="<?= url('/bills') ?>" class="link" style="font-size: 0.85rem;">Manage</a>
                </div>
                <?php if (empty($upcomingBills)): ?>
                    <p class="text-secondary mt-3">No upcoming bills.</p>
                <?php else: ?>
                    <ul style="list-style: none; margin-top: 1rem; padding: 0;">
                        <?php foreach ($upcomingBills as $bill): ?>
                            <li class="flex-between" style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-size: 0.9rem;"><?= e($bill['name']) ?> <small
                                        class="text-secondary">(<?= e(date('M d', strtotime($bill['next_due_date']))) ?>)</small></span>
                                <span class="sensitive-data"
                                    style="font-weight: 600;"><?= $baseCurrency['symbol'] ?><?= number_format((float) $bill['total_amount'], 2) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card glass">
                <div class="flex-between">
                    <h3>Latest Payslip</h3>
                    <a href="<?= url('/salaries') ?>" class="link" style="font-size: 0.85rem;">View All</a>
                </div>
                <?php if (!$latestSalary): ?>
                    <p class="text-secondary mt-3">No payslips recorded.</p>
                <?php else: ?>
                    <div
                        style="margin-top: 1rem; padding: 1rem; background: rgba(16, 185, 129, 0.05); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <div class="flex-between">
                            <span class="text-secondary"><?= e($latestSalary['company_name']) ?></span>
                            <span
                                class="text-secondary"><?= e(date('M d, Y', strtotime($latestSalary['payment_date']))) ?></span>
                        </div>
                        <h2 class="sensitive-data" style="color: var(--success); margin: 0.5rem 0 0;">
                            <?= $baseCurrency['symbol'] ?>     <?= number_format((float) $latestSalary['net_pay'], 2) ?>
                        </h2>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Widget: charts -->
    <div class="dashboard-widget" data-id="charts" data-visible="1" data-size="normal">
        <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
        <div class="grid grid-2">
            <div class="card glass">
                <h3>Spending by Category</h3>
                <div id="categorySkeleton" class="skeleton" style="height: 250px; margin-top: 1rem;"></div>
                <canvas id="categoryChart" style="display: none;"></canvas>
            </div>
            <div class="card glass">
                <h3>6-Month Trend</h3>
                <div id="trendSkeleton" class="skeleton" style="height: 250px; margin-top: 1rem;"></div>
                <canvas id="trendChart" style="display: none;"></canvas>
            </div>
        </div>
    </div>

    <!-- Widget: accounts -->
    <div class="dashboard-widget" data-id="accounts" data-visible="1" data-size="normal">
        <div class="widget-drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <button class="widget-hide-btn" title="Hide Widget"><i class="fas fa-times"></i></button>
        <div class="card glass mt-4">
            <div class="flex-between">
                <h3>Your Accounts</h3>

                <button type="button" class="btn btn-primary btn-sm"
                    onclick="window.location.href='<?= url('/accounts/create') ?>'">
                    <i class="fas fa-plus"></i> Add Account
                </button>
            </div>
            <div class="table-responsive mt-3">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Institution</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-secondary">No accounts found. <a
                                        href="<?= url('/accounts/create') ?>">Create one</a></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $acc): ?>
                                <tr>
                                    <td><strong><?= e($acc['name']) ?></strong></td>
                                    <td><span class="badge badge-<?= e($acc['type']) ?>"><?= ucfirst(e($acc['type'])) ?></span>
                                    </td>
                                    <td><?= e($acc['institution'] ?: 'N/A') ?></td>
                                    <td><strong
                                            class="sensitive-data"><?= e($acc['currency_symbol']) ?><?= number_format((float) $acc['current_balance'], 2) ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div> <!-- Close .dashboard-grid -->

<script>
    // 1. AJAX Data Fetching for Dashboard Charts
    document.addEventListener('DOMContentLoaded', () => {
        // FIX: Added Dark Mode detection for Chart.js
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#9ca3af' : '#64748b';

        const chartOptions = {
            responsive: true,
            plugins: { legend: { labels: { color: textColor } } },
            scales: {
                x: { ticks: { color: textColor }, grid: { color: gridColor } },
                y: { ticks: { color: textColor }, grid: { color: gridColor } }
            }
        };

        fetch('<?= url('/api/dashboard/stats') ?>')
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    const data = res.data;
                    const sym = '<?= $baseCurrency['symbol'] ?>';

                    // Update Stats
                    document.getElementById('stat-income').innerHTML = sym + data.monthly_flow.income.toFixed(2) + ' <i class="fas fa-eye widget-eye-toggle" data-target="#stat-income" title="Click to reveal"></i>';
                    document.getElementById('stat-expense').innerHTML = sym + data.monthly_flow.expense.toFixed(2) + ' <i class="fas fa-eye widget-eye-toggle" data-target="#stat-expense" title="Click to reveal"></i>';
                    document.getElementById('stat-flow').innerHTML = sym + (data.monthly_flow.income - data.monthly_flow.expense).toFixed(2) + ' <i class="fas fa-eye widget-eye-toggle" data-target="#stat-flow" title="Click to reveal"></i>';

                    document.getElementById('categorySkeleton').style.display = 'none';
                    document.getElementById('categoryChart').style.display = 'block';
                    document.getElementById('trendSkeleton').style.display = 'none';
                    document.getElementById('trendChart').style.display = 'block';

                    // Render Category Chart
                    new Chart(document.getElementById('categoryChart'), {
                        type: 'doughnut',
                        data: {
                            labels: data.categories.map(c => c.name),
                            datasets: [{
                                data: data.categories.map(c => c.total),
                                backgroundColor: data.categories.map(c => c.color),
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'bottom', labels: { color: textColor } } }
                        }
                    });

                    // Render Trend Chart
                    new Chart(document.getElementById('trendChart'), {
                        type: 'line',
                        data: {
                            labels: data.trend.map(t => t.month),
                            datasets: [
                                { label: 'Income', data: data.trend.map(t => t.income), borderColor: '#10b981', tension: 0.4 },
                                { label: 'Expense', data: data.trend.map(t => t.expense), borderColor: '#ef4444', tension: 0.4 }
                            ]
                        },
                        options: chartOptions // FIX: Applied dark mode aware options
                    });
                }
            })
            .catch(err => console.error('Dashboard stats fetch failed:', err));
    });
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>