<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Edit Payslip';
ob_start();

// 1. Safely decode JSON strings into arrays
$allowances = is_string($salary['allowances']) ? json_decode($salary['allowances'], true) : ($salary['allowances'] ?? []);
$deductions = is_string($salary['deductions']) ? json_decode($salary['deductions'], true) : ($salary['deductions'] ?? []);

// 2. Ensure they are strictly arrays to prevent foreach/array_column errors
if (!is_array($allowances))
    $allowances = [];
if (!is_array($deductions))
    $deductions = [];
?>
<div class="page-header">
    <h1>Edit Payslip</h1>
</div>
<div class="card glass" style="max-width: 800px;">
    <form method="POST" action="<?= url('/salaries/update/' . $salary['id']) ?>" class="form-stack">
        <?= \App\Core\CSRF::field() ?>

        <h3>Employer & Period</h3>
        <div class="grid grid-2">
            <div class="form-group">
                <label>Employer</label>
                <select name="employer_id" required>
                    <?php foreach ($employers as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= $salary['employer_id'] == $emp['id'] ? 'selected' : '' ?>>
                            <?= e((string) $emp['company_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="paid" <?= $salary['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="pending" <?= $salary['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>
        </div>
        <div class="grid grid-2">
            <div class="form-group"><label>Period Start</label><input type="date" name="pay_period_start"
                    value="<?= e((string) $salary['pay_period_start']) ?>" required></div>
            <div class="form-group"><label>Period End</label><input type="date" name="pay_period_end"
                    value="<?= e((string) $salary['pay_period_end']) ?>" required></div>
        </div>

        <h3 class="mt-4">Earnings</h3>
        <div class="grid grid-3">
            <div class="form-group"><label>Basic Salary</label><input type="number" step="0.01" name="basic_salary"
                    value="<?= (float) $salary['basic_salary'] ?>" required></div>
            <div class="form-group"><label>Bonus</label><input type="number" step="0.01" name="bonus"
                    value="<?= (float) $salary['bonus'] ?>"></div>
            <div class="form-group"><label>Overtime</label><input type="number" step="0.01" name="overtime_pay"
                    value="<?= (float) $salary['overtime_pay'] ?>"></div>
        </div>
        <div class="grid grid-2">
            <div class="form-group"><label>13th Month Pay</label><input type="number" step="0.01"
                    name="thirteenth_month" value="<?= (float) $salary['thirteenth_month'] ?>"></div>
            <div class="form-group"><label>Payment Date</label><input type="date" name="payment_date"
                    value="<?= e((string) $salary['payment_date']) ?>" required></div>
        </div>

        <div class="grid grid-2 mt-3">
            <div>
                <div class="flex-between">
                    <h4>Allowances</h4><button type="button" class="btn btn-sm"
                        style="background:var(--success);color:white;" onclick="addRow('allowance')">+</button>
                </div>
                <div id="allowancesContainer">
                    <?php foreach ($allowances as $a): ?>
                        <div class="grid grid-3" style="gap:0.5rem; margin-bottom:0.5rem; align-items:end;">
                            <input type="text" name="allowance_name[]" value="<?= e((string) ($a['name'] ?? '')) ?>"
                                placeholder="Name" style="margin:0;">
                            <input type="number" step="0.01" name="allowance_amount[]"
                                value="<?= (float) ($a['amount'] ?? 0) ?>" placeholder="Amount" style="margin:0;">
                            <button type="button" class="btn btn-sm"
                                style="background:var(--danger);color:white;height:38px;"
                                onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <div class="flex-between">
                    <h4>Deductions</h4><button type="button" class="btn btn-sm"
                        style="background:var(--danger);color:white;" onclick="addRow('deduction')">+</button>
                </div>
                <div id="deductionsContainer">
                    <?php foreach ($deductions as $d): ?>
                        <div class="grid grid-3" style="gap:0.5rem; margin-bottom:0.5rem; align-items:end;">
                            <input type="text" name="deduction_name[]" value="<?= e((string) ($d['name'] ?? '')) ?>"
                                placeholder="Name" style="margin:0;">
                            <input type="number" step="0.01" name="deduction_amount[]"
                                value="<?= (float) ($d['amount'] ?? 0) ?>" placeholder="Amount" style="margin:0;">
                            <button type="button" class="btn btn-sm"
                                style="background:var(--danger);color:white;height:38px;"
                                onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="form-group mt-3"><label>Notes</label><textarea name="notes"
                rows="2"><?= e((string) ($salary['notes'] ?? '')) ?></textarea></div>
        <div class="flex-between mt-4">
            <a href="<?= url('/salaries') ?>" class="btn"
                style="background: var(--text-secondary); color: white;">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Payslip</button>
        </div>
    </form>
</div>

<script>
    function addRow(type) {
        const container = document.getElementById(type === 'allowance' ? 'allowancesContainer' : 'deductionsContainer');
        const prefix = type === 'allowance' ? 'allowance' : 'deduction';
        const div = document.createElement('div');
        div.className = 'grid grid-3';
        div.style.cssText = 'gap:0.5rem; margin-bottom:0.5rem; align-items:end;';
        div.innerHTML = `
        <input type="text" name="${prefix}_name[]" placeholder="Name" style="margin:0;">
        <input type="number" step="0.01" name="${prefix}_amount[]" placeholder="Amount" style="margin:0;">
        <button type="button" class="btn btn-sm" style="background:var(--danger);color:white;height:38px;" onclick="this.parentElement.remove()">×</button>
    `;
        container.appendChild(div);
    }
</script>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>