<?php
declare(strict_types=1);
$pageTitle = 'Add Recurring Income';
ob_start();
?>
<div class="page-header">
    <h1>Add Recurring Income Source</h1>
</div>
<div class="card glass" style="max-width: 600px;">
    <form method="POST" action="<?= url('/recurring-incomes/store') ?>" class="form-stack">
        <?= \App\Core\CSRF::field() ?>

        <div class="form-group">
            <label>Source Name *</label>
            <input type="text" name="name" required placeholder="e.g., Monthly Salary, Rental Income">
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Amount *</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
            <div class="form-group">
                <label>Currency</label>
                <select name="currency_id">
                    <?php foreach ($currencies as $curr): ?>
                        <option value="<?= $curr['id'] ?>" <?= $curr['id'] == $baseCurrency['id'] ? 'selected' : '' ?>>
                            <?= e($curr['code']) ?> - <?= e($curr['name']) ?> (<?= e($curr['symbol']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Frequency *</label>
                <select name="frequency" id="freqSelect" onchange="toggleCustomInterval()">
                    <option value="monthly">Monthly</option>
                    <option value="bi-weekly">Bi-Weekly</option>
                    <option value="weekly">Weekly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                    <option value="custom">Custom (Days)</option>
                </select>
            </div>
            <div class="form-group" id="customIntervalWrap" style="display: none;">
                <label>Every X Days</label>
                <input type="number" name="custom_interval_days" min="1" value="30">
            </div>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label>Start Date *</label>
                <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>End Date (Optional)</label>
                <input type="date" name="end_date">
                <small class="text-secondary">Leave blank for "Never Ends"</small>
            </div>
        </div>

        <div class="form-group">
            <label>Category (Optional)</label>
            <select name="category_id">
                <option value="">-- None --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>">
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="2"></textarea>
        </div>

        <div class="flex-between mt-4">
            <a href="<?= url('/recurring-incomes') ?>" class="btn"
                style="background: var(--text-secondary); color: white;">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Source</button>
        </div>
    </form>
</div>

<script>
    function toggleCustomInterval() {
        const freq = document.getElementById('freqSelect').value;
        document.getElementById('customIntervalWrap').style.display = freq === 'custom' ? 'block' : 'none';
    }
</script>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>