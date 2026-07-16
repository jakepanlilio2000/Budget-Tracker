<?php
$pageTitle = 'New Savings Goal';
ob_start();
?>
<div class="page-header"><h1>Create Savings Goal</h1></div>
<div class="card glass" style="max-width: 600px;">
    <form method="POST" action="<?= url('/vaults/store') ?>" class="form-stack">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-group">
            <label>Goal Name *</label>
            <input type="text" name="name" required placeholder="e.g., RTX 5080 GPU, Emergency Fund">
        </div>
        <div class="form-group">
            <label>Target Amount *</label>
            <input type="number" step="0.01" name="target_amount" required placeholder="0.00">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Why are you saving for this?"></textarea>
        </div>
        <div class="flex-between mt-4">
            <a href="<?= url('/vaults') ?>" class="btn" style="background: var(--text-secondary); color: white;">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Goal</button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>