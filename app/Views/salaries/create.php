<?php 
$pageTitle = 'Add Payslip';
ob_start(); 
?>
<div class="page-header"><h1>Add Payslip</h1></div>
<div class="card glass" style="max-width: 800px;">
    <form method="POST" action="<?= url('/salaries/store') ?>" class="form-stack">
        <?= \App\Core\CSRF::field() ?>
        
        <h3>Employer & Period</h3>
        <div class="grid grid-2">
            <div class="form-group">
                <label>Employer</label>
                <select name="employer_id" id="employerSelect" onchange="toggleNewEmployer()">
                    <option value="0">-- Select Existing --</option>
                    <?php foreach ($employers as $emp): ?><option value="<?= $emp['id'] ?>"><?= e($emp['company_name']) ?></option><?php endforeach; ?>
                    <option value="0" data-new="1">+ Add New Employer</option>
                </select>
            </div>
            <div class="form-group" id="newEmployerWrap" style="display:none;">
                <label>New Company Name</label>
                <input type="text" name="new_employer_name">
            </div>
        </div>
        <div class="grid grid-2">
            <div class="form-group"><label>Period Start</label><input type="date" name="pay_period_start" required></div>
            <div class="form-group"><label>Period End</label><input type="date" name="pay_period_end" required></div>
        </div>

        <h3 class="mt-4">Earnings</h3>
        <div class="grid grid-3">
            <div class="form-group"><label>Basic Salary</label><input type="number" step="0.01" name="basic_salary" required></div>
            <div class="form-group"><label>Bonus</label><input type="number" step="0.01" name="bonus" value="0"></div>
            <div class="form-group"><label>Overtime</label><input type="number" step="0.01" name="overtime_pay" value="0"></div>
        </div>
        <div class="grid grid-2">
            <div class="form-group"><label>13th Month Pay</label><input type="number" step="0.01" name="thirteenth_month" value="0"></div>
            <div class="form-group"><label>Payment Date</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
        </div>

        <div class="grid grid-2 mt-3">
            <div>
                <div class="flex-between"><h4>Allowances</h4><button type="button" class="btn btn-sm" style="background:var(--success);color:white;" onclick="addRow('allowance')">+</button></div>
                <div id="allowancesContainer"></div>
            </div>
            <div>
                <div class="flex-between"><h4>Deductions</h4><button type="button" class="btn btn-sm" style="background:var(--danger);color:white;" onclick="addRow('deduction')">+</button></div>
                <div id="deductionsContainer"></div>
            </div>
        </div>

        <div class="form-group mt-3"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary btn-block">Save Payslip</button>
    </form>
</div>

<script>
function toggleNewEmployer() {
    const sel = document.getElementById('employerSelect');
    document.getElementById('newEmployerWrap').style.display = sel.options[sel.selectedIndex].dataset.new ? 'block' : 'none';
}
function addRow(type) {
    const container = document.getElementById(type === 'allowance' ? 'allowancesContainer' : 'deductionsContainer');
    const prefix = type === 'allowance' ? 'allowance' : 'deduction';
    const div = document.createElement('div');
    div.className = 'grid grid-3';
    div.style.cssText = 'gap:0.5rem; margin-bottom:0.5rem; align-items:end;';
    div.innerHTML = `
        <input type="text" name="${prefix}_name[]" placeholder="Name" class="form-group" style="margin:0;">
        <input type="number" step="0.01" name="${prefix}_amount[]" placeholder="Amount" class="form-group" style="margin:0;">
        <button type="button" class="btn btn-sm" style="background:var(--danger);color:white;height:38px;" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(div);
}
</script>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>