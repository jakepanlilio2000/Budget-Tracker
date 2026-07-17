<?php
declare(strict_types=1);
use App\Core\Auth;
$pageTitle = 'Recurring Income';
ob_start();
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <h1>Recurring Income</h1>
    <button class="btn btn-primary" onclick="openRecurringModal()">
        <i class="fas fa-plus"></i> Add Source
    </button>
</div>

<div class="card glass">
    <?php if (empty($incomes)): ?>
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-sync-alt" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <p class="text-secondary">No recurring income sources yet. Set one up to automate your income tracking!</p>
            <button class="btn btn-primary mt-3" onclick="openRecurringModal()">Create Source</button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Frequency</th>
                        <th>Next Post Date</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incomes as $inc): ?>
                        <tr>
                            <td>
                                <strong><?= e($inc['name']) ?></strong><br>
                                <small class="text-secondary"><?= e($inc['account_name']) ?></small>
                            </td>
                            <td style="color: var(--success); font-weight: bold;">
                                <span
                                    class="sensitive-data"><?= e($inc['currency_symbol']) ?><?= number_format((float) $inc['amount'], 2) ?></span>
                            </td>
                            <td><?= ucfirst(str_replace('-', ' ', e($inc['frequency']))) ?></td>
                            <td><?= e(date('M d, Y', strtotime((string) $inc['next_post_date']))) ?></td>
                            <td>
                                <span
                                    class="badge badge-<?= $inc['status'] === 'active' ? 'success' : ($inc['status'] === 'paused' ? 'warning' : 'secondary') ?>">
                                    <?= ucfirst(e($inc['status'])) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.5rem;">
                                    <?php if ($inc['status'] === 'active'): ?>
                                        <form method="POST" action="<?= url('/recurring-incomes/post-now/' . $inc['id']) ?>"
                                            style="display:inline;">
                                            <?= \App\Core\CSRF::field() ?>
                                            <button type="submit" class="btn btn-sm"
                                                style="background: var(--success); color: white;" title="Post Now"><i
                                                    class="fas fa-play"></i></button>
                                        </form>
                                        <form method="POST" action="<?= url('/recurring-incomes/skip/' . $inc['id']) ?>"
                                            style="display:inline;" onsubmit="return confirm('Skip next occurrence?')">
                                            <?= \App\Core\CSRF::field() ?>
                                            <button type="submit" class="btn btn-sm"
                                                style="background: var(--text-secondary); color: white;" title="Skip"><i
                                                    class="fas fa-forward"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= url('/recurring-incomes/toggle-status/' . $inc['id']) ?>"
                                        style="display:inline;">
                                        <?= \App\Core\CSRF::field() ?>
                                        <button type="submit" class="btn btn-sm"
                                            style="background: <?= $inc['status'] === 'active' ? '#f59e0b' : 'var(--success)' ?>; color: white;"
                                            title="<?= $inc['status'] === 'active' ? 'Pause' : 'Resume' ?>">
                                            <i class="fas fa-<?= $inc['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= url('/recurring-incomes/delete/' . $inc['id']) ?>"
                                        style="display:inline;" onsubmit="return confirm('Delete this source?')">
                                        <?= \App\Core\CSRF::field() ?>
                                        <button type="submit" class="btn btn-sm"
                                            style="background: var(--danger); color: white;"><i
                                                class="fas fa-trash"></i></button>
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
<!-- RECURRING INCOME CREATE MODAL              -->
<!-- ========================================== -->
<div id="recurringModal" class="modal-overlay" style="display: none;"
    onclick="if(event.target===this) closeRecurringModal()">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div class="flex-between"
            style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            <h3 style="margin:0;">Add Recurring Income Source</h3>
            <button class="btn-icon" onclick="closeRecurringModal()" style="font-size: 1.2rem;"><i
                    class="fas fa-times"></i></button>
        </div>

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
                    <label>Account *</label>
                    <select name="account_id" required>
                        <option value="">-- Select Account --</option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>">
                                <?= e($acc['name']) ?> (<?= e($acc['currency_symbol']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <input type="hidden" name="currency_id" value="<?= $baseCurrency['id'] ?>">

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
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="2"></textarea>
            </div>

            <div class="flex-between mt-4" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button type="button" class="btn" style="background: var(--text-secondary); color: white;"
                    onclick="closeRecurringModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Source</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRecurringModal() {
        document.getElementById('recurringModal').style.display = 'flex';
    }

    function closeRecurringModal() {
        document.getElementById('recurringModal').style.display = 'none';
    }

    function toggleCustomInterval() {
        const freq = document.getElementById('freqSelect').value;
        document.getElementById('customIntervalWrap').style.display = freq === 'custom' ? 'block' : 'none';
    }
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>