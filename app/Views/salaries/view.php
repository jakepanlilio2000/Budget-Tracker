<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Models\CurrencyService;

$pageTitle = 'Payslip Details';
ob_start();
$baseSym = CurrencyService::getUserBaseCurrency(Auth::id())['symbol'] ?? '$';

$allowances = is_string($salary['allowances']) ? json_decode($salary['allowances'], true) : ($salary['allowances'] ?? []);
$deductions = is_string($salary['deductions']) ? json_decode($salary['deductions'], true) : ($salary['deductions'] ?? []);

if (!is_array($allowances))
    $allowances = [];
if (!is_array($deductions))
    $deductions = [];

$salary['basic_salary'] = (float) ($salary['basic_salary'] ?? 0);
$salary['bonus'] = (float) ($salary['bonus'] ?? 0);
$salary['overtime_pay'] = (float) ($salary['overtime_pay'] ?? 0);
$salary['thirteenth_month'] = (float) ($salary['thirteenth_month'] ?? 0);
$salary['net_pay'] = (float) ($salary['net_pay'] ?? 0);

$gross = $salary['basic_salary'] + $salary['bonus'] + $salary['overtime_pay'] + $salary['thirteenth_month'] + array_sum(array_column($allowances, 'amount'));
$totalDed = array_sum(array_column($deductions, 'amount'));
?>

<div class="page-header flex-between">
    <div>
        <h1>Payslip Details</h1>
        <p class="text-secondary"><?= e((string) $salary['company_name']) ?></p>
    </div>
    <a href="<?= url('/salaries') ?>" class="btn" style="background:var(--text-secondary);color:white;">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
</div>

<div class="card glass" style="max-width: 800px; margin: 0 auto;">
    <div class="flex-between"
        style="border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 class="sensitive-data" style="margin:0; font-size: 1.5rem;"><?= e((string) $salary['company_name']) ?>
            </h2>
            <p class="text-secondary" style="margin:0.5rem 0 0;">
                Period: <?= e(date('M d', strtotime((string) $salary['pay_period_start']))) ?> -
                <?= e(date('M d, Y', strtotime((string) $salary['pay_period_end']))) ?>
            </p>
            <p class="text-secondary" style="margin:0.25rem 0 0;">
                Paid on: <?= e(date('M d, Y', strtotime((string) $salary['payment_date']))) ?>
            </p>
        </div>
        <div class="text-right">
            <small class="text-secondary" style="text-transform: uppercase; letter-spacing: 0.05em;">Net Pay</small>
            <h1 class="sensitive-data" style="color: var(--success); margin:0.25rem 0 0; font-size: 2rem;">
                <?= $baseSym ?><?= number_format($salary['net_pay'], 2) ?>
            </h1>
        </div>
    </div>

    <div class="grid grid-2" style="gap: 3rem;">
        <div>
            <h4 style="color: var(--success); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-arrow-up"></i> Earnings
            </h4>
            <ul style="list-style: none; padding: 0; line-height: 2.2;">
                <li class="flex-between">
                    <span>Basic Salary</span>
                    <span class="sensitive-data"><?= number_format($salary['basic_salary'], 2) ?></span>
                </li>
                <?php if ($salary['bonus'] > 0): ?>
                    <li class="flex-between">
                        <span>Bonus</span>
                        <span class="sensitive-data"><?= number_format($salary['bonus'], 2) ?></span>
                    </li>
                <?php endif; ?>
                <?php if ($salary['overtime_pay'] > 0): ?>
                    <li class="flex-between">
                        <span>Overtime</span>
                        <span class="sensitive-data"><?= number_format($salary['overtime_pay'], 2) ?></span>
                    </li>
                <?php endif; ?>
                <?php if ($salary['thirteenth_month'] > 0): ?>
                    <li class="flex-between">
                        <span>13th Month</span>
                        <span class="sensitive-data"><?= number_format($salary['thirteenth_month'], 2) ?></span>
                    </li>
                <?php endif; ?>
                <?php foreach ($allowances as $a): ?>
                    <li class="flex-between">
                        <span><?= e((string) ($a['name'] ?? 'Allowance')) ?></span>
                        <span class="sensitive-data"><?= number_format((float) ($a['amount'] ?? 0), 2) ?></span>
                    </li>
                <?php endforeach; ?>
                <li class="flex-between"
                    style="font-weight: bold; border-top: 2px solid var(--border-color); margin-top: 1rem; padding-top: 1rem; font-size: 1.1rem;">
                    <span>Gross Pay</span>
                    <span class="sensitive-data"><?= number_format($gross, 2) ?></span>
                </li>
            </ul>
        </div>

        <div>
            <h4 style="color: var(--danger); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-arrow-down"></i> Deductions
            </h4>
            <ul style="list-style: none; padding: 0; line-height: 2.2;">
                <?php if (empty($deductions)): ?>
                    <li class="text-secondary" style="font-style: italic;">No deductions applied</li>
                <?php else: ?>
                    <?php foreach ($deductions as $d): ?>
                        <li class="flex-between">
                            <span><?= e((string) ($d['name'] ?? 'Deduction')) ?></span>
                            <span class="sensitive-data">-<?= number_format((float) ($d['amount'] ?? 0), 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li class="flex-between"
                    style="font-weight: bold; border-top: 2px solid var(--border-color); margin-top: 1rem; padding-top: 1rem; font-size: 1.1rem;">
                    <span>Total Deductions</span>
                    <span class="sensitive-data">-<?= number_format($totalDed, 2) ?></span>
                </li>
            </ul>

            <?php if (!empty($salary['notes'])): ?>
                <div
                    style="margin-top: 2rem; padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px; border-left: 3px solid var(--accent);">
                    <small class="text-secondary" style="text-transform: uppercase; font-weight: 600;">Notes</small>
                    <p style="margin: 0.5rem 0 0; font-style: italic; color: var(--text-primary);">
                        <?= e((string) $salary['notes']) ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>