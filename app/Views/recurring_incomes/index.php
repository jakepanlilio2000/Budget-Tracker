<?php
declare(strict_types=1);
$pageTitle = 'Recurring Income';
ob_start();
?>
<div class="page-header flex-between">
    <h1>Recurring Income</h1>
    <a href="<?= url('/recurring-incomes/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Source</a>
</div>

<div class="card glass">
    <?php if (empty($incomes)): ?>
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-sync-alt" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <p class="text-secondary">No recurring income sources yet. Set one up to automate your income tracking!</p>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incomes as $inc): ?>
                        <tr>
                            <td><strong>
                                    <?= e($inc['name']) ?>
                                </strong><br><small class="text-secondary">
                                    <?= e($inc['account_name']) ?>
                                </small></td>
                            <td style="color: var(--success); font-weight: bold;">
                                <?= e($inc['currency_symbol']) ?>
                                <?= number_format((float) $inc['amount'], 2) ?>
                            </td>
                            <td>
                                <?= ucfirst(str_replace('-', ' ', e($inc['frequency']))) ?>
                            </td>
                            <td>
                                <?= e(date('M d, Y', strtotime($inc['next_post_date']))) ?>
                            </td>
                            <td>
                                <span
                                    class="badge badge-<?= $inc['status'] === 'active' ? 'success' : ($inc['status'] === 'paused' ? 'warning' : 'secondary') ?>">
                                    <?= ucfirst(e($inc['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
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
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>