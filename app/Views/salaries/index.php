<?php
declare(strict_types=1);
use App\Core\Auth;
use App\Models\CurrencyService;

$pageTitle = 'Salary & Payslips';
ob_start();
$baseSym = CurrencyService::getUserBaseCurrency(Auth::id())['symbol'] ?? '$';
$employers = \App\Models\Employer::getAllByUser(Auth::id()); // Fetch for modal dropdown
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <h1>Salary & Payslips</h1>
    <div style="display:flex; gap:0.5rem;">
        <a href="<?= url('/salaries/export-csv') ?>" class="btn" style="background: var(--text-secondary); color:white;">
            <i class="fas fa-file-csv"></i> Export
        </a>
        <button class="btn btn-primary" onclick="openSalaryModal('create')">
            <i class="fas fa-plus"></i> Add Payslip
        </button>
    </div>
</div>

<div class="grid grid-3 mb-4">
    <div class="card glass stat-card">
        <div class="stat-icon income"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <span class="stat-label">YTD Net Income</span>
            <h3 class="sensitive-data"><?= $baseSym ?><?= number_format((float) ($analytics['total_earned'] ?? 0), 2) ?></h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon balance"><i class="fas fa-gift"></i></div>
        <div class="stat-info">
            <span class="stat-label">YTD Bonuses & Extras</span>
            <h3 class="sensitive-data"><?= $baseSym ?><?= number_format((float) ($analytics['total_extras'] ?? 0), 2) ?></h3>
        </div>
    </div>
    <div class="card glass stat-card">
        <div class="stat-icon expense"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Payslips</span>
            <h3 class="sensitive-data"><?= (int) ($analytics['total_payslips'] ?? 0) ?></h3>
        </div>
    </div>
</div>

<div class="card glass">
    <h3>Recent Payslips</h3>
    <?php if (empty($salaries)): ?>
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <p class="text-secondary">No payslips recorded yet. Click "Add Payslip" to get started.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive mt-3">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Employer</th>
                        <th>Basic</th>
                        <th>Net Pay</th>
                        <th>Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salaries as $s): 
                        // Safely encode arrays for JS data attributes
                        $allowancesJson = htmlspecialchars(json_encode(is_string($s['allowances']) ? json_decode($s['allowances'], true) : ($s['allowances'] ?? [])), ENT_QUOTES, 'UTF-8');
                        $deductionsJson = htmlspecialchars(json_encode(is_string($s['deductions']) ? json_decode($s['deductions'], true) : ($s['deductions'] ?? [])), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td>
                            <?= e(date('M d', strtotime($s['pay_period_start']))) ?> - 
                            <?= e(date('M d, Y', strtotime($s['pay_period_end']))) ?>
                        </td>
                        <td><strong><?= e($s['company_name']) ?></strong></td>
                        <td class="sensitive-data"><?= $baseSym ?><?= number_format((float) $s['basic_salary'], 2) ?></td>
                        <td style="color: var(--success); font-weight: bold;" class="sensitive-data">
                            <?= $baseSym ?><?= number_format((float) $s['net_pay'], 2) ?>
                        </td>
                        <td><?= e(date('M d, Y', strtotime($s['payment_date']))) ?></td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.5rem;">
                                <a href="<?= url('/salaries/show/' . $s['id']) ?>" class="btn btn-sm" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary);" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm edit-salary-btn" style="background: var(--accent); color: white;" title="Edit"
                                    data-id="<?= $s['id'] ?>"
                                    data-employer="<?= $s['employer_id'] ?>"
                                    data-start="<?= e($s['pay_period_start']) ?>"
                                    data-end="<?= e($s['pay_period_end']) ?>"
                                    data-basic="<?= (float)$s['basic_salary'] ?>"
                                    data-bonus="<?= (float)$s['bonus'] ?>"
                                    data-overtime="<?= (float)$s['overtime_pay'] ?>"
                                    data-13th="<?= (float)$s['thirteenth_month'] ?>"
                                    data-date="<?= e($s['payment_date']) ?>"
                                    data-status="<?= e($s['status'] ?? 'paid') ?>"
                                    data-notes="<?= e($s['notes'] ?? '') ?>"
                                    data-allowances='<?= $allowancesJson ?>'
                                    data-deductions='<?= $deductionsJson ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="<?= url('/salaries/delete/' . $s['id']) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this payslip?');">
                                    <?= \App\Core\CSRF::field() ?>
                                    <button type="submit" class="btn btn-sm" style="background: var(--danger); color: white;" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- SALARY CREATE / EDIT MODAL                 -->
<!-- ========================================== -->
<div id="salaryModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this) closeSalaryModal()">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <div class="flex-between" style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            <h3 id="modalTitle" style="margin:0;">Add Payslip</h3>
            <button class="btn-icon" onclick="closeSalaryModal()" style="font-size: 1.2rem;"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" action="" id="salaryForm" class="form-stack">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="id" id="salaryId" value="">
            
            <h4 style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">Employer & Period</h4>
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Employer</label>
                    <select name="employer_id" id="employerSelect" onchange="toggleNewEmployer()" required>
                        <option value="0">-- Select Existing --</option>
                        <?php foreach ($employers as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= e($emp['company_name']) ?></option>
                        <?php endforeach; ?>
                        <option value="0" data-new="1">+ Add New Employer</option>
                    </select>
                </div>
                <div class="form-group" id="newEmployerWrap" style="display:none;">
                    <label>New Company Name</label>
                    <input type="text" name="new_employer_name" placeholder="e.g., Acme Corp">
                </div>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Period Start</label>
                    <input type="date" name="pay_period_start" id="payPeriodStart" required>
                </div>
                <div class="form-group">
                    <label>Period End</label>
                    <input type="date" name="pay_period_end" id="payPeriodEnd" required>
                </div>
            </div>

            <h4 style="margin-top: 1rem; color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">Earnings</h4>
            <div class="grid grid-3">
                <div class="form-group">
                    <label>Basic Salary</label>
                    <input type="number" step="0.01" name="basic_salary" id="basicSalary" required>
                </div>
                <div class="form-group">
                    <label>Bonus</label>
                    <input type="number" step="0.01" name="bonus" id="bonus" value="0">
                </div>
                <div class="form-group">
                    <label>Overtime</label>
                    <input type="number" step="0.01" name="overtime_pay" id="overtimePay" value="0">
                </div>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label>13th Month Pay</label>
                    <input type="number" step="0.01" name="thirteenth_month" id="thirteenthMonth" value="0">
                </div>
                <div class="form-group">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" id="paymentDate" required>
                </div>
            </div>

            <div class="grid grid-2 mt-3">
                <div>
                    <div class="flex-between">
                        <h4 style="margin:0;">Allowances</h4>
                        <button type="button" class="btn btn-sm" style="background:var(--success);color:white;" onclick="addRow('allowance')">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    <div id="allowancesContainer" style="margin-top: 0.5rem;"></div>
                </div>
                <div>
                    <div class="flex-between">
                        <h4 style="margin:0;">Deductions</h4>
                        <button type="button" class="btn btn-sm" style="background:var(--danger);color:white;" onclick="addRow('deduction')">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    <div id="deductionsContainer" style="margin-top: 0.5rem;"></div>
                </div>
            </div>

            <div class="form-group mt-3">
                <label>Notes</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Optional notes..."></textarea>
            </div>

            <div class="flex-between mt-4" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button type="button" class="btn" style="background: var(--text-secondary); color: white;" onclick="closeSalaryModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Payslip</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal Control
function openSalaryModal(mode, data = null) {
    const modal = document.getElementById('salaryModal');
    const form = document.getElementById('salaryForm');
    const title = document.getElementById('modalTitle');
    
    // Reset form
    form.reset();
    document.getElementById('allowancesContainer').innerHTML = '';
    document.getElementById('deductionsContainer').innerHTML = '';
    document.getElementById('newEmployerWrap').style.display = 'none';
    document.getElementById('employerSelect').value = '0';

    if (mode === 'create') {
        title.textContent = 'Add Payslip';
        form.action = '<?= url('/salaries/store') ?>';
        document.getElementById('salaryId').value = '';
        // Set default dates
        document.getElementById('payPeriodStart').value = '<?= date('Y-m-01') ?>';
        document.getElementById('payPeriodEnd').value = '<?= date('Y-m-t') ?>';
        document.getElementById('paymentDate').value = '<?= date('Y-m-d') ?>';
    } else if (mode === 'edit' && data) {
        title.textContent = 'Edit Payslip';
        form.action = '<?= url('/salaries/update/') ?>' + data.id;
        document.getElementById('salaryId').value = data.id;
        
        // Populate fields
        document.getElementById('employerSelect').value = data.employer;
        document.getElementById('payPeriodStart').value = data.start;
        document.getElementById('payPeriodEnd').value = data.end;
        document.getElementById('basicSalary').value = data.basic;
        document.getElementById('bonus').value = data.bonus;
        document.getElementById('overtimePay').value = data.overtime;
        document.getElementById('thirteenthMonth').value = data['13th'];
        document.getElementById('paymentDate').value = data.date;
        document.getElementById('notes').value = data.notes;

        // Populate dynamic rows
        if (data.allowances && data.allowances.length > 0) {
            data.allowances.forEach(a => addRow('allowance', a.name, a.amount));
        }
        if (data.deductions && data.deductions.length > 0) {
            data.deductions.forEach(d => addRow('deduction', d.name, d.amount));
        }
    }
    
    modal.style.display = 'flex';
}

function closeSalaryModal() {
    document.getElementById('salaryModal').style.display = 'none';
}

// Attach data to edit buttons
document.querySelectorAll('.edit-salary-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const data = {
            id: this.dataset.id,
            employer: this.dataset.employer,
            start: this.dataset.start,
            end: this.dataset.end,
            basic: this.dataset.basic,
            bonus: this.dataset.bonus,
            overtime: this.dataset.overtime,
            '13th': this.dataset['13th'],
            date: this.dataset.date,
            notes: this.dataset.notes,
            allowances: JSON.parse(this.dataset.allowances || '[]'),
            deductions: JSON.parse(this.dataset.deductions || '[]')
        };
        openSalaryModal('edit', data);
    });
});

// Form Helpers
function toggleNewEmployer() {
    const sel = document.getElementById('employerSelect');
    document.getElementById('newEmployerWrap').style.display = sel.options[sel.selectedIndex].dataset.new ? 'block' : 'none';
}

function addRow(type, name = '', amount = '') {
    const container = document.getElementById(type === 'allowance' ? 'allowancesContainer' : 'deductionsContainer');
    const prefix = type === 'allowance' ? 'allowance' : 'deduction';
    const div = document.createElement('div');
    div.className = 'grid grid-3';
    div.style.cssText = 'gap:0.5rem; margin-bottom:0.5rem; align-items:end;';
    div.innerHTML = `
        <div class="form-group" style="margin:0;">
            <input type="text" name="${prefix}_name[]" value="${name}" placeholder="Name" required>
        </div>
        <div class="form-group" style="margin:0;">
            <input type="number" step="0.01" name="${prefix}_amount[]" value="${amount}" placeholder="0.00" required>
        </div>
        <button type="button" class="btn btn-sm" style="background:var(--danger);color:white;height:38px; margin-bottom:1rem;" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>