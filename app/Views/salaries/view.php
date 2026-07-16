<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Models\CurrencyService;

$pageTitle = 'Payslip Details';
ob_start();
$baseSym = CurrencyService::getUserBaseCurrency(Auth::id())['symbol'] ?? '$';

// 1. Safely decode JSON strings into arrays
$allowances = is_string($salary['allowances']) ? json_decode($salary['allowances'], true) : ($salary['allowances'] ?? []);
$deductions = is_string($salary['deductions']) ? json_decode($salary['deductions'], true) : ($salary['deductions'] ?? []);

// 2. Ensure they are strictly arrays
if (!is_array($allowances))
    $allowances = [];
if (!is_array($deductions))
    $deductions = [];

// 3. Cast numeric fields to float safely
$salary['basic_salary'] = (float) ($salary['basic_salary'] ?? 0);
$salary['bonus'] = (float) ($salary['bonus'] ?? 0);
$salary['overtime_pay'] = (float) ($salary['overtime_pay'] ?? 0);
$salary['thirteenth_month'] = (float) ($salary['thirteenth_month'] ?? 0);
$salary['net_pay'] = (float) ($salary['net_pay'] ?? 0);

// 4. Calculate totals safely
$gross = $salary['basic_salary'] + $salary['bonus'] + $salary['overtime_pay'] + $salary['thirteenth_month'] + array_sum(array_column($allowances, 'amount'));
$totalDed = array_sum(array_column($deductions, 'amount'));
?>
<div class="page-header flex-between">
    <h1>Payslip: <?= e((string) $salary['company_name']) ?></h1>
    <a href="<?= url('/salaries') ?>" class="btn" style="background:var(--text-secondary);color:white;">Back</a>
</div>

<div class="card glass" style="max-width: 700px; margin: 0 auto;">
    <div class="flex-between"
        style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h2 class="sensitive-data" style="margin:0;"><?= e((string) $salary['company_name']) ?></h2>
            <p class="text-secondary" style="margin:0;">Payslip for
                <?= e(date('M d', strtotime((string) $salary['pay_period_start']))) ?> -
                <?= e(date('M d, Y', strtotime((string) $salary['pay_period_end']))) ?>
            </p>
        </div>
        <div class="text-right">
            <h1 class="sensitive-data" style="color: var(--success); margin:0;">
                <?= $baseSym ?><?= number_format($salary['net_pay'], 2) ?>
            </h1>
            <small class="text-secondary">Net Pay</small>
        </div>
    </div>

    <div class="grid grid-2" style="gap: 2rem;">
        <div>
            <h4 style="color: var(--success); margin-bottom: 1rem;">Earnings</h4>
            <ul style="list-style: none; padding: 0; line-height: 2;">
                <li class="flex-between"><span>Basic Salary</span><span
                        class="sensitive-data"><?= number_format($salary['basic_salary'], 2) ?></span></li>
                <?php if ($salary['bonus'] > 0): ?>
                    <li class="flex-between"><span>Bonus</span><span
                            class="sensitive-data"><?= number_format($salary['bonus'], 2) ?></span></li>
                <?php endif; ?>
                <?php if ($salary['overtime_pay'] > 0): ?>
                    <li class="flex-between"><span>Overtime</span><span
                            class="sensitive-data"><?= number_format($salary['overtime_pay'], 2) ?></span></li>
                <?php endif; ?>
                <?php if ($salary['thirteenth_month'] > 0): ?>
                    <li class="flex-between"><span>13th Month</span><span
                            class="sensitive-data"><?= number_format($salary['thirteenth_month'], 2) ?></span></li>
                <?php endif; ?>
                <?php foreach ($allowances as $a): ?>
                    <li class="flex-between"><span><?= e((string) ($a['name'] ?? '')) ?></span><span
                            class="sensitive-data"><?= number_format((float) ($a['amount'] ?? 0), 2) ?></span></li>
                <?php endforeach; ?>
                <li class="flex-between"
                    style="font-weight: bold; border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem;">
                    <span>Gross Pay</span><span class="sensitive-data"><?= number_format($gross, 2) ?></span>
                </li>
            </ul>
        </div>
        <div>
            <h4 style="color: var(--danger); margin-bottom: 1rem;">Deductions</h4>
            <ul style="list-style: none; padding: 0; line-height: 2;">
                <?php if (empty($deductions)): ?>
                    <li class="text-secondary">No deductions</li>
                <?php else: ?>
                    <?php foreach ($deductions as $d): ?>
                        <li class="flex-between"><span><?= e((string) ($d['name'] ?? '')) ?></span><span
                                class="sensitive-data">-<?= number_format((float) ($d['amount'] ?? 0), 2) ?></span></li>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li class="flex-between"
                    style="font-weight: bold; border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem;">
                    <span>Total Deductions</span><span class="sensitive-data">-<?= number_format($totalDed, 2) ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>