<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Models\CurrencyService;

$pageTitle = 'Salary & Payslips';
ob_start(); 
$baseSym = CurrencyService::getUserBaseCurrency(Auth::id())['symbol'] ?? '$';
?>
<div class="page-header flex-between">
    <h1>Salary & Payslips</h1>
    <div style="display:flex; gap:0.5rem;">
        <a href="<?= url('/salaries/export-csv') ?>" class="btn" style="background: var(--text-secondary); color:white;"><i class="fas fa-file-csv"></i> Export</a>
        <a href="<?= url('/salaries/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Payslip</a>
    </div>
</div>

<div class="grid grid-3 mb-4">
    <div class="card glass stat-card">
        <div class="stat-icon income"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <span class="stat-label">YTD Net Income</span>
            <h3 class="sensitive-data"><?= $baseSym ?><?= number_format((float)($analytics['total_earned'] ?? 0), 2) ?></h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon balance"><i class="fas fa-gift"></i></div>
        <div class="stat-info">
            <span class="stat-label">YTD Bonuses & Extras</span>
            <h3 class="sensitive-data"><?= $baseSym ?><?= number_format((float)($analytics['total_extras'] ?? 0), 2) ?></h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon expense"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Payslips</span>
            <h3 class="sensitive-data"><?= (int)($analytics['total_payslips'] ?? 0) ?></h3>
        </div>
    </div>
</div>

<div class="card glass">
    <h3>Recent Payslips</h3>
    <?php if (empty($salaries)): ?>
        <p class="text-secondary mt-3">No payslips recorded yet.</p>
    <?php else: ?>
        <div class="table-responsive mt-3">
            <table class="data-table">
                <thead>
                    <tr><th>Period</th><th>Employer</th><th>Basic</th><th>Net Pay</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($salaries as $s): ?>
                    <tr>
                        <td><?= e(date('M d', strtotime($s['pay_period_start']))) ?> - <?= e(date('M d, Y', strtotime($s['pay_period_end']))) ?></td>
                        <td><strong><?= e($s['company_name']) ?></strong></td>
                        <!-- FIX: Cast to (float) to satisfy PHP 8.1+ strict typing -->
                        <td><?= $baseSym ?><?= number_format((float)$s['basic_salary'], 2) ?></td>
                        <td style="color: var(--success); font-weight: bold;"><?= $baseSym ?><?= number_format((float)$s['net_pay'], 2) ?></td>
                        <td><?= e(date('M d, Y', strtotime($s['payment_date']))) ?></td>
                        <td><a href="<?= url('/salaries/show/' . $s['id']) ?>" class="link">View Details</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>