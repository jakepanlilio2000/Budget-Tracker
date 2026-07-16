<?php
declare(strict_types=1);
$pageTitle = 'Adjust Account Balance';
ob_start();
$currentBal = (float) $account['current_balance'];
?>
<div class="page-header">
    <h1>Adjust Balance:
        <?= e($account['name']) ?>
    </h1>
    <p class="text-secondary">This will create an adjustment transaction to preserve your audit trail.</p>
</div>

<div class="card glass" style="max-width: 600px;">
    <form method="POST" action="<?= url('/accounts/process-adjustment/' . $account['id']) ?>" class="form-stack"
        id="adjustForm">
        <?= \App\Core\CSRF::field() ?>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Current Balance</label>
                <input type="text" value="<?= number_format($currentBal, 2) ?>" readonly
                    style="background: rgba(0,0,0,0.05); color: var(--text-secondary); cursor: not-allowed;">
            </div>
            <div class="form-group">
                <label>New Balance *</label>
                <input type="number" step="0.01" name="new_balance" id="newBalance" value="<?= $currentBal ?>" required
                    oninput="calculateDifference()">
            </div>
        </div>

        <div class="form-group">
            <label>Difference</label>
            <input type="text" id="differenceDisplay" readonly
                style="font-weight: bold; font-size: 1.1rem; background: rgba(0,0,0,0.05); cursor: not-allowed;">
            <small class="text-secondary">This amount will be recorded as an income or expense transaction.</small>
        </div>

        <div class="form-group">
            <label>Reason for Adjustment *</label>
            <textarea name="reason" rows="3"
                placeholder="e.g., Correcting bank statement discrepancy, initial setup, etc." required></textarea>
        </div>

        <div class="flex-between mt-4">
            <a href="<?= url('/accounts') ?>" class="btn"
                style="background: var(--text-secondary); color: white;">Cancel</a>
            <button type="submit" class="btn btn-primary">Confirm Adjustment</button>
        </div>
    </form>
</div>

<script>
    function calculateDifference() {
        const current = <?= $currentBal ?>;
        const newVal = parseFloat(document.getElementById('newBalance').value) || 0;
        const diff = newVal - current;
        const display = document.getElementById('differenceDisplay');

        if (diff === 0) {
            display.value = 'No change';
            display.style.color = 'var(--text-secondary)';
        } else if (diff > 0) {
            display.value = '+<?= e($account['currency_symbol']) ?>' + Math.abs(diff).toFixed(2) + ' (Will be recorded as Income)';
            display.style.color = 'var(--success)';
        } else {
            display.value = '-<?= e($account['currency_symbol']) ?>' + Math.abs(diff).toFixed(2) + ' (Will be recorded as Expense)';
            display.style.color = 'var(--danger)';
        }
    }
    // Initialize on load
    calculateDifference();
</script>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>