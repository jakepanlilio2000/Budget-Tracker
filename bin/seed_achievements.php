<?php
declare(strict_types=1);

// 1. Define base path
define('BASE_PATH', dirname(__DIR__));

// 2. Load Composer autoloader
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// 3. Fallback: Manually require core files if autoloader fails in CLI
if (!class_exists('App\Core\Database')) {
    require_once BASE_PATH . '/app/Core/Database.php';
}

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $achievements = [];

    // Helper updated to support endless chains
    $add = function ($slug, $name, $desc, $icon, $color, $cat, $rarity, $xp, $rule, $target, $isChain = 0, $multiplier = 1.0) use (&$achievements) {
        $achievements[] = [
            'slug' => $slug,
            'name' => $name,
            'description' => $desc,
            'icon' => $icon,
            'color' => $color,
            'category' => $cat,
            'rarity' => $rarity,
            'xp_value' => $xp,
            'rule_type' => $rule,
            'rule_config' => json_encode(['target' => $target]),
            'is_chain' => $isChain,
            'chain_multiplier' => $multiplier,
            'base_target' => $target
        ];
    };

    // 1. Net Worth Milestones (Static, not chained)
    $add('nw_1k', 'Starter', 'Reach a net worth of 1,000', 'fa-seedling', '#10b981', 'Wealth', 'common', 500, 'net_worth_reach', 1000);
    $add('nw_10k', 'Stable', 'Reach a net worth of 10,000', 'fa-tree', '#3b82f6', 'Wealth', 'rare', 1500, 'net_worth_reach', 10000);
    $add('nw_100k', 'Prosperous', 'Reach a net worth of 100,000', 'fa-building', '#8b5cf6', 'Wealth', 'epic', 5000, 'net_worth_reach', 100000);
    $add('nw_1m', 'Millionaire', 'Reach a net worth of 1,000,000', 'fa-gem', '#f59e0b', 'Wealth', 'legendary', 15000, 'net_worth_reach', 1000000);
    $add('nw_1b', 'Investor Elite', 'Reach a net worth of 1,000,000,000', 'fa-rocket', '#ef4444', 'Wealth', 'legendary', 100000, 'net_worth_reach', 1000000000);

    // 2. Total Savings Milestones (Static)
    $add('sav_1k', 'Penny Saver', 'Save a total of 1,000 in vaults', 'fa-piggy-bank', '#3b82f6', 'Savings', 'common', 500, 'total_savings_reach', 1000);
    $add('sav_10k', 'Emergency Fund', 'Save a total of 10,000 in vaults', 'fa-shield-alt', '#3b82f6', 'Savings', 'rare', 1500, 'total_savings_reach', 10000);
    $add('sav_100k', 'Fortress', 'Save a total of 100,000 in vaults', 'fa-dungeon', '#8b5cf6', 'Savings', 'epic', 5000, 'total_savings_reach', 100000);
    $add('sav_1m', 'Savings Millionaire', 'Save a total of 1,000,000 in vaults', 'fa-crown', '#ef4444', 'Savings', 'legendary', 15000, 'total_savings_reach', 1000000);

    // 3. Transaction Counts (ENDLESS CHAINS: Multiplies by 2.5x each tier)
    $add('txn_10', 'Getting Started', 'Record 10 transactions', 'fa-receipt', '#8b5cf6', 'Activity', 'common', 500, 'transaction_count_reach', 10, 1, 2.5);
    $add('txn_500', 'High Volume', 'Record 500 transactions', 'fa-database', '#8b5cf6', 'Activity', 'epic', 5000, 'transaction_count_reach', 500, 1, 2.5);
    $add('txn_10k', 'The Archive', 'Record 10,000 transactions', 'fa-landmark', '#ef4444', 'Activity', 'legendary', 20000, 'transaction_count_reach', 10000, 1, 2.5);

    // 4. Account Collections (ENDLESS CHAINS: Multiplies by 2.0x)
    $add('acc_1', 'First Account', 'Create your first account', 'fa-university', '#14b8a6', 'Collections', 'common', 500, 'account_count_reach', 1, 1, 2.0);
    $add('acc_5', 'Portfolio', 'Create 5 accounts', 'fa-chart-pie', '#14b8a6', 'Collections', 'epic', 2500, 'account_count_reach', 5, 1, 2.0);

    // 5. Vault Collections (ENDLESS CHAINS)
    $add('vlt_1', 'First Goal', 'Create your first savings vault', 'fa-vault', '#14b8a6', 'Collections', 'common', 500, 'vault_count_reach', 1, 1, 2.0);
    $add('vlt_10', 'Architect', 'Create 10 savings vaults', 'fa-drafting-compass', '#14b8a6', 'Collections', 'legendary', 2500, 'vault_count_reach', 10, 1, 2.0);

    // 6. Completed Vaults (ENDLESS CHAINS)
    $add('cvlt_1', 'Goal Crusher', 'Complete your first savings goal', 'fa-flag-checkered', '#10b981', 'Savings', 'rare', 1000, 'completed_vaults_reach', 1, 1, 2.0);
    $add('cvlt_10', 'Unstoppable', 'Complete 10 savings goals', 'fa-medal', '#10b981', 'Savings', 'legendary', 5000, 'completed_vaults_reach', 10, 1, 2.0);

    // 7. Budgets Created (ENDLESS CHAINS)
    $add('bud_1', 'First Budget', 'Create your first budget', 'fa-piggy-bank', '#8b5cf6', 'Activity', 'common', 500, 'budget_count_reach', 1, 1, 2.0);
    $add('bud_25', 'Master Planner', 'Create 25 budgets', 'fa-chess-board', '#8b5cf6', 'Activity', 'legendary', 2500, 'budget_count_reach', 25, 1, 2.0);

    // 8. Bills Paid (ENDLESS CHAINS)
    $add('bill_1', 'First Payment', 'Pay your first bill on time', 'fa-file-invoice', '#f59e0b', 'Activity', 'common', 500, 'bills_paid_reach', 1, 1, 2.0);
    $add('bill_50', 'Streak', 'Pay 50 bills on time', 'fa-fire', '#f59e0b', 'Activity', 'epic', 2500, 'bills_paid_reach', 50, 1, 2.0);

    // 9. Daily Logs Achievements (ENDLESS CHAINS)
    $add('daily_log_1', 'First Daily Log', 'Log your daily spending for the first time', 'fa-book', '#14b8a6', 'Activity', 'common', 500, 'daily_log_count_reach', 1, 1, 2.0);
    $add('daily_log_30', 'Monthly Logger', 'Log daily spending 30 times', 'fa-calendar-alt', '#14b8a6', 'Activity', 'epic', 2500, 'daily_log_count_reach', 30, 1, 2.0);

    // 10. Pending Ledger Achievements (ENDLESS CHAINS)
    $add('pending_paid_1', 'First Pending Cleared', 'Mark your first pending item as paid', 'fa-check-circle', '#f59e0b', 'Activity', 'common', 500, 'pending_paid_count_reach', 1, 1, 2.0);
    $add('pending_paid_50', 'Proactive Planner', 'Clear 50 pending items', 'fa-clipboard-check', '#f59e0b', 'Activity', 'epic', 2500, 'pending_paid_count_reach', 50, 1, 2.0);

    // 11. Salary Count Achievements (ENDLESS CHAINS)
    $add('salary_1', 'First Paycheck', 'Record your very first payslip', 'fa-file-invoice-dollar', '#10b981', 'Activity', 'common', 500, 'salary_count_reach', 1, 1, 2.0);
    $add('salary_12', 'Annual Earner', 'Record 12 payslips', 'fa-calendar-check', '#10b981', 'Activity', 'rare', 1000, 'salary_count_reach', 12, 1, 2.0);

    // 12. Universal Financial Health Milestones (Static)
    $add('savings_ratio_10', 'Smart Saver', 'Save at least 10% of your total recorded income', 'fa-piggy-bank', '#10b981', 'Savings', 'common', 1000, 'savings_ratio_reach', 10);
    $add('savings_ratio_50', 'Wealth Builder', 'Save at least 50% of your total recorded income', 'fa-gem', '#10b981', 'Savings', 'epic', 5000, 'savings_ratio_reach', 50);

    // Insert all into DB (Updated to include chain columns)
    $stmt = $db->prepare("
        INSERT IGNORE INTO achievement_definitions 
        (slug, name, description, icon, color, category, rarity, xp_value, rule_type, rule_config, is_chain, chain_multiplier, base_target) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $count = 0;
    foreach ($achievements as $a) {
        $stmt->execute([
            $a['slug'],
            $a['name'],
            $a['description'],
            $a['icon'],
            $a['color'],
            $a['category'],
            $a['rarity'],
            $a['xp_value'],
            $a['rule_type'],
            $a['rule_config'],
            $a['is_chain'],
            $a['chain_multiplier'],
            $a['base_target']
        ]);
        $count++;
    }

    echo "✅ Successfully seeded {$count} achievements (including endless chains) into the database.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}