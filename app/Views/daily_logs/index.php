<?php 
$pageTitle = 'Daily Spenditures Log';
ob_start(); 
?>
<div class="page-header flex-between">
    <h1>Daily Expenditure Journal</h1>
    <button class="btn btn-primary" onclick="document.getElementById('logModal').style.display='flex'">
        <i class="fas fa-pen"></i> Log Today
    </button>
</div>

<div class="grid grid-2">
    <?php foreach ($logs as $log): ?>
    <div class="card glass">
        <div class="flex-between">
            <h3 style="margin:0;"><?= e(date('M d, Y', strtotime($log['log_date']))) ?></h3>
            <span class="badge" style="background: var(--border-color);"><?= e($log['mood_context'] ?: 'Neutral') ?></span>
        </div>
        <div style="margin-top: 1rem; font-size: 1.2rem; font-weight: bold; color: var(--danger);">
            - <?= number_format($log['total_spent'], 2) ?>
        </div>
        <?php if ($log['notes']): ?>
            <p class="text-secondary" style="margin-top: 0.5rem; font-style: italic; font-size: 0.9rem;">"<?= e($log['notes']) ?>"</p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<div id="logModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content glass" style="padding: 1.5rem;">
        <h3>Daily Log Entry</h3>
        <form method="POST" action="<?= url('/daily-logs/store') ?>" class="form-stack mt-3">
            <?= \App\Core\CSRF::field() ?>
            <div class="form-group"><label>Date</label><input type="date" name="log_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group"><label>Total Spent Today</label><input type="number" step="0.01" name="total_spent" value="0.00" required></div>
            <div class="form-group"><label>Mood / Context</label>
                <select name="mood_context">
                    <option value="Neutral">Neutral</option><option value="Productive">Productive</option>
                    <option value="Stressful">Stressful</option><option value="Rewarding">Rewarding</option>
                </select>
            </div>
            <div class="form-group"><label>Notes / Reflections</label><textarea name="notes" rows="3" placeholder="What drove today's spending?"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Save Log</button>
        </form>
    </div>
</div>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>