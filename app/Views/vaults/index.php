<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Savings Vaults';
ob_start();
$sym = $baseCurrency['symbol'];
?>
<div class="page-header flex-between">
    <h1>Savings Vaults</h1>
    <a href="<?= url('/vaults/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> New Goal</a>
</div>

<!-- Active Vaults -->
<h3 class="mb-3" style="color: var(--text-secondary);">Active Goals</h3>
<?php if (empty($activeVaults)): ?>
    <div class="card glass text-center" style="padding: 3rem;">
        <i class="fas fa-piggy-bank" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
        <p class="text-secondary">No active savings goals. Create one to start tracking your progress!</p>
    </div>
<?php else: ?>
    <div class="grid grid-2">
        <?php foreach ($activeVaults as $v): ?>
        <div class="card glass vault-card">
            <div class="flex-between" style="margin-bottom: 1rem;">
                <div>
                    <h3 style="margin:0;"><?= e($v['name']) ?></h3>
                    <small class="text-secondary"><?= e($v['description'] ?: 'No description') ?></small>
                </div>
                <div class="text-right">
                    <h2 class="sensitive-data" style="margin:0; color: var(--accent);"><?= $sym ?><?= number_format((float)$v['current_amount'], 2) ?></h2>
                    <small class="text-secondary">of <?= $sym ?><?= number_format((float)$v['target_amount'], 2) ?></small>
                </div>
            </div>

            <div style="background: var(--border-color); border-radius: 99px; height: 10px; overflow: hidden; margin-bottom: 0.5rem;">
                <div style="width: <?= $v['metrics']['percentage'] ?>%; height: 100%; background: var(--accent); transition: width 0.5s;"></div>
            </div>
            <div class="flex-between" style="font-size: 0.85rem; color: var(--text-secondary);">
                <span><?= $v['metrics']['percentage'] ?>% Complete</span>
                <span>
                    <?php if ($v['metrics']['estimated_months'] !== null): ?>
                        ~<?= $v['metrics']['estimated_months'] ?> months left
                    <?php else: ?>
                        Add deposits to estimate
                    <?php endif; ?>
                </span>
            </div>

            <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                <a href="<?= url('/vaults/show/' . $v['id']) ?>" class="btn btn-sm" style="background: var(--text-secondary); color: white;">Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Completed / Cancelled History -->
<?php if (!empty($completedVaults) || !empty($cancelledVaults)): ?>
    <h3 class="mt-4 mb-3" style="color: var(--text-secondary);">History</h3>
    <div class="grid grid-2" style="opacity: 0.7;">
        <?php foreach (array_merge($completedVaults, $cancelledVaults) as $v): ?>
        <div class="card glass" style="border-left: 4px solid <?= $v['status'] === 'completed' ? 'var(--success)' : 'var(--text-secondary)' ?>;">
            <div class="flex-between">
                <div>
                    <h3 style="margin:0;"><?= e($v['name']) ?></h3>
                    <small class="text-secondary"><?= ucfirst($v['status']) ?></small>
                </div>
                <div class="text-right sensitive-data">
                    <strong><?= $sym ?><?= number_format((float)$v['current_amount'], 2) ?></strong>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>