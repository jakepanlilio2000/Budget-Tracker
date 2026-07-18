<?php
/**
 * Comprehensive Dummy Data Seeder for User ID 2 (BALANCED)
 * Generates 1 year of realistic, RNG financial data where Income > Expenses.
 */

$resetData = true; // SET TO TRUE TO WIPE AND RESEED

$host = '127.0.0.1';
$db = 'expense_tracker';
$user = 'pma_admin'; // Change to your DB username
$pass = '5255331438';     // Change to your DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected successfully to database.\n";
} catch (\PDOException $e) {
    die("❌ DB Connection Failed: " . $e->getMessage() . "\n");
}

function randomFloat($min, $max, $decimals = 2)
{
    return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
}
function randomDate($start, $end)
{
    $timestamp = mt_rand($start->getTimestamp(), $end->getTimestamp());
    return (new DateTime())->setTimestamp($timestamp)->format('Y-m-d');
}
function randomEnum($enums)
{
    return $enums[array_rand($enums)];
}

$userId = 2;
$currencyId = 270; // PHP
$startDate = new DateTime('-1 year');
$endDate = new DateTime();

echo "Starting seed for User ID: {$userId}\n";
$pdo->beginTransaction();

try {
    if ($resetData) {
        $tablesToClear = [
            'timeline_events',
            'budgets',
            'recurring_incomes',
            'pending_ledger',
            'vault_transactions',
            'savings_vaults',
            'bill_payments',
            'bills',
            'salaries',
            'transactions',
            'daily_logs',
            'categories',
            'accounts',
            'employers',
            'planning_scenarios',
            'tags'
        ];
        foreach ($tablesToClear as $table) {
            $pdo->exec("DELETE FROM `{$table}` WHERE user_id = {$userId}");
        }
        $pdo->exec("UPDATE user_financial_stats SET net_worth=0, total_savings=0, transaction_count=0, account_count=0, vault_count=0, completed_vaults=0, total_xp=0, level=1, wealth_tier='Broke' WHERE user_id = {$userId}");
        $pdo->exec("UPDATE user_fxp_stats SET lifetime_fxp=0, current_level=1, prestige_stars=0, current_title='Beginner Saver' WHERE user_id = {$userId}");
        echo "🔄 Rolled back and cleared existing data.\n";
    }

    // 1. BASE ENTITIES
    $pdo->prepare("INSERT INTO employers (user_id, company_name, contact_email) VALUES (?, ?, ?)")->execute([$userId, 'RNG Tech Corp', 'hr@rngtech.com']);
    $employerId = $pdo->lastInsertId();

    $accounts = [
        ['Cash on Hand', 'cash', 5000.00],
        ['BDO Savings', 'bank', 25000.00],
        ['GCash Wallet', 'ewallet', 3000.00],
    ];
    $accountIds = [];
    $stmtAcc = $pdo->prepare("INSERT INTO accounts (user_id, currency_id, name, type, opening_balance, current_balance, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    foreach ($accounts as $acc) {
        $stmtAcc->execute([$userId, $currencyId, $acc[0], $acc[1], $acc[2], $acc[2]]);
        $accountIds[$acc[0]] = $pdo->lastInsertId();
    }

    $categories = [
        ['Food & Dining', 'expense', '#ef4444', 'fa-utensils'],
        ['Transportation', 'expense', '#f59e0b', 'fa-car'],
        ['Utilities', 'expense', '#3b82f6', 'fa-bolt'],
        ['Shopping', 'expense', '#8b5cf6', 'fa-shopping-bag'],
        ['Entertainment', 'expense', '#ec4899', 'fa-gamepad'],
        ['Salary', 'income', '#10b981', 'fa-briefcase'],
        ['Freelance', 'income', '#14b8a6', 'fa-laptop-code'],
    ];
    $categoryIds = [];
    $stmtCat = $pdo->prepare("INSERT INTO categories (user_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $stmtCat->execute([$userId, $cat[0], $cat[1], $cat[2], $cat[3]]);
        $categoryIds[$cat[0]] = $pdo->lastInsertId();
    }
    $expenseCatNames = ['Food & Dining', 'Transportation', 'Utilities', 'Shopping', 'Entertainment'];

    // 2. SALARIES (Income)
    $stmtSal = $pdo->prepare("INSERT INTO salaries (user_id, employer_id, pay_period_start, pay_period_end, basic_salary, net_pay, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'paid')");
    $salaryDate = clone $startDate;
    for ($i = 0; $i < 12; $i++) {
        $periodStart = (clone $salaryDate)->format('Y-m-01');
        $periodEnd = (clone $salaryDate)->format('Y-m-t');
        $payDate = (clone $salaryDate)->modify('+1 month')->format('Y-m-05');
        $basic = randomFloat(35000, 45000);
        $net = $basic + randomFloat(2000, 5000);

        $stmtSal->execute([$userId, $employerId, $periodStart, $periodEnd, $basic, $net, $payDate]);
        $pdo->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?")->execute([$net, $accountIds['BDO Savings']]);
        $salaryDate->modify('+1 month');
    }
    echo "✅ Generated 12 monthly salaries.\n";

    // 3. BILLS
    $billsData = [
        ['Internet', 1299.00, 'monthly', $categoryIds['Utilities']],
        ['Electricity', 2500.00, 'monthly', $categoryIds['Utilities']],
        ['Gym Membership', 1500.00, 'monthly', $categoryIds['Entertainment']],
    ];
    $billIds = [];
    $stmtBill = $pdo->prepare("INSERT INTO bills (user_id, category_id, name, total_amount, frequency, next_due_date, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmtBillPay = $pdo->prepare("INSERT INTO bill_payments (bill_id, user_id, amount_paid, payment_date, account_id) VALUES (?, ?, ?, ?, ?)");

    foreach ($billsData as $bill) {
        $stmtBill->execute([$userId, $bill[3], $bill[0], $bill[1], $bill[2], $endDate->format('Y-m-d')]);
        $billIds[$bill[0]] = $pdo->lastInsertId();

        $payDate = clone $startDate;
        for ($i = 0; $i < 12; $i++) {
            $stmtBillPay->execute([$billIds[$bill[0]], $userId, $bill[1], $payDate->format('Y-m-15'), $accountIds['BDO Savings']]);
            $pdo->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?")->execute([$bill[1], $accountIds['BDO Savings']]);
            $payDate->modify('+1 month');
        }
    }
    echo "✅ Generated bills and payment history.\n";

    // 4. DAILY TRANSACTIONS (FIXED RNG: Lower amounts to prevent bankruptcy)
    // Average spend is now ~300-600 per day instead of ~2000
    $stmtTxn = $pdo->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, total_amount, currency_id, description, transaction_date, status) VALUES (?, ?, ?, 'expense', ?, ?, ?, ?, 'posted')");
    $currentDate = clone $startDate;
    $txnCount = 0;

    while ($currentDate <= $endDate) {
        $dailyTxns = mt_rand(1, 2); // Reduced from 1-4 to 1-2
        for ($i = 0; $i < $dailyTxns; $i++) {
            $catName = $expenseCatNames[array_rand($expenseCatNames)];
            $amount = randomFloat(50, 400); // Reduced from 50-1500 to 50-400
            $accName = array_rand(['Cash on Hand' => 1, 'GCash Wallet' => 1, 'BDO Savings' => 2]);

            $stmtTxn->execute([$userId, $accountIds[$accName], $categoryIds[$catName], $amount, $currencyId, $catName . ' expense', $currentDate->format('Y-m-d')]);
            $pdo->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?")->execute([$amount, $accountIds[$accName]]);
            $txnCount++;
        }
        $currentDate->modify('+1 day');
    }
    echo "✅ Generated {$txnCount} balanced daily transactions.\n";

    // 5. PENDING LEDGER
    $stmtPending = $pdo->prepare("INSERT INTO pending_ledger (user_id, type, description, amount, currency_id, due_date, priority, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $pendingDescs = ['Refund from Shopee', 'Upcoming Electric Bill', 'Freelance Payout', 'Birthday Gift', 'Car Maintenance'];
    for ($i = 0; $i < 10; $i++) {
        $stmtPending->execute([$userId, randomEnum(['income', 'expense']), $pendingDescs[array_rand($pendingDescs)], randomFloat(500, 5000), $currencyId, randomDate($endDate, (clone $endDate)->modify('+30 days')), randomEnum(['low', 'medium', 'high']), randomEnum(['pending', 'paid']), 'Auto-generated']);
    }
    echo "✅ Generated pending ledger.\n";

    // 6. RECURRING INCOMES
    $stmtRecurring = $pdo->prepare("INSERT INTO recurring_incomes (user_id, name, amount, currency_id, account_id, category_id, frequency, start_date, next_post_date, status, total_posted_count, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)");
    foreach (['Freelance Web Dev', 'Condo Rental'] as $name) {
        $stmtRecurring->execute([$userId, $name, randomFloat(5000, 15000), $currencyId, $accountIds['GCash Wallet'], $categoryIds['Freelance'], randomEnum(['monthly', 'bi-weekly']), randomDate($startDate, $endDate), randomDate($endDate, (clone $endDate)->modify('+15 days')), mt_rand(2, 8), 'Auto-generated']);
    }
    echo "✅ Generated recurring incomes.\n";

    // 7. BUDGETS
    $stmtBudget = $pdo->prepare("INSERT INTO budgets (user_id, category_id, month, amount, period, carry_over, rolled_over_amount) VALUES (?, ?, ?, ?, 'monthly', 0, 0.00)");
    $budgetDate = clone $startDate;
    while ($budgetDate <= $endDate) {
        $pickedCats = array_rand(array_flip($expenseCatNames), 2);
        foreach ($pickedCats as $catName) {
            $stmtBudget->execute([$userId, $categoryIds[$catName], $budgetDate->format('Y-m'), randomFloat(5000, 15000)]);
        }
        $budgetDate->modify('+1 month');
    }
    echo "✅ Generated monthly budgets.\n";

    // 8. SAVINGS VAULTS (FIXED: Now actually deducts from Bank Account)
    $vaults = [
        ['Emergency Fund', 100000.00, 35000.00],
        ['Japan Trip 2027', 50000.00, 12000.00],
    ];
    $stmtVault = $pdo->prepare("INSERT INTO savings_vaults (user_id, name, target_amount, current_amount, status) VALUES (?, ?, ?, ?, 'active')");
    $stmtVaultTxn = $pdo->prepare("INSERT INTO vault_transactions (vault_id, user_id, type, amount, notes) VALUES (?, ?, 'deposit', ?, ?)");

    foreach ($vaults as $v) {
        $stmtVault->execute([$userId, $v[0], $v[1], $v[2]]);
        $vaultId = $pdo->lastInsertId();
        $deposits = mt_rand(3, 6);
        $depositAmount = round($v[2] / $deposits, 2);
        for ($i = 0; $i < $deposits; $i++) {
            $stmtVaultTxn->execute([$vaultId, $userId, $depositAmount, 'Monthly savings']);
            // DEDUCT FROM BANK ACCOUNT TO KEEP NET WORTH ACCURATE
            $pdo->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?")->execute([$depositAmount, $accountIds['BDO Savings']]);
        }
    }
    echo "✅ Generated savings vaults (deducted from bank).\n";

    // 9. DAILY LOGS
    $moods = ['Productive', 'Relaxed', 'Stressed', 'Rewarding', 'Frugal'];
    $stmtLog = $pdo->prepare("INSERT INTO daily_logs (user_id, log_date, total_spent, mood_context, notes) VALUES (?, ?, ?, ?, ?)");
    $usedLogDates = [];
    $logCount = 0;
    while ($logCount < 40) {
        $logDate = randomDate($startDate, $endDate);
        if (!in_array($logDate, $usedLogDates)) {
            $usedLogDates[] = $logDate;
            $stmtLog->execute([$userId, $logDate, randomFloat(200, 1500), $moods[array_rand($moods)], 'Daily tracking']);
            $logCount++;
        }
    }
    echo "✅ Generated unique daily logs.\n";

    // 10. TIMELINE EVENTS
    $stmtTimeline = $pdo->prepare("INSERT INTO timeline_events (user_id, module, action, description, amount, currency_id, icon, color, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $timelineModules = [
        ['transactions', 'expense_recorded', 'fa-arrow-down', '#ef4444'],
        ['bills', 'bill_paid', 'fa-file-invoice', '#f59e0b'],
        ['salaries', 'salary_received', 'fa-briefcase', '#10b981'],
        ['savings_vaults', 'vault_deposited', 'fa-vault', '#3b82f6'],
    ];
    for ($i = 0; $i < 100; $i++) {
        $modData = $timelineModules[array_rand($timelineModules)];
        $eventDate = randomDate($startDate, $endDate) . ' ' . mt_rand(8, 20) . ':' . mt_rand(10, 59) . ':00';
        $stmtTimeline->execute([$userId, $modData[0], $modData[1], ucfirst($modData[0]) . ' event', randomFloat(100, 10000), $currencyId, $modData[2], $modData[3], $eventDate]);
    }
    echo "✅ Generated timeline events.\n";

    // 11. RECALCULATE USER STATS
    $netWorth = $pdo->query("SELECT SUM(current_balance) FROM accounts WHERE user_id = {$userId}")->fetchColumn();
    $totalSavings = $pdo->query("SELECT SUM(current_amount) FROM savings_vaults WHERE user_id = {$userId}")->fetchColumn();
    $txnCountDb = $pdo->query("SELECT COUNT(*) FROM transactions WHERE user_id = {$userId}")->fetchColumn();
    $accCount = $pdo->query("SELECT COUNT(*) FROM accounts WHERE user_id = {$userId}")->fetchColumn();
    $vaultCount = $pdo->query("SELECT COUNT(*) FROM savings_vaults WHERE user_id = {$userId}")->fetchColumn();

    // True Net Worth = Cash in Banks + Cash in Vaults
    $trueNetWorth = $netWorth + $totalSavings;

    $tier = 'Broke';
    if ($trueNetWorth >= 1000000)
        $tier = 'Millionaire';
    elseif ($trueNetWorth >= 100000)
        $tier = 'Prosperous';
    elseif ($trueNetWorth >= 10000)
        $tier = 'Stable';
    elseif ($trueNetWorth >= 1000)
        $tier = 'Starter';

    $pdo->prepare("UPDATE user_financial_stats SET 
        net_worth = ?, total_savings = ?, transaction_count = ?, account_count = ?, vault_count = ?, wealth_tier = ? 
        WHERE user_id = ?")->execute([$trueNetWorth, $totalSavings, $txnCountDb, $accCount, $vaultCount, $tier, $userId]);

    $pdo->commit();
    echo "\n🎉 SUCCESS! Data seeded.\n";
    echo "Bank Balances: " . number_format($netWorth, 2) . " | Vault Savings: " . number_format($totalSavings, 2) . "\n";
    echo "🏆 TRUE NET WORTH: " . number_format($trueNetWorth, 2) . " | Tier: {$tier}\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}