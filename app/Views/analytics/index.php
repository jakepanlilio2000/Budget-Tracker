<?php 
$pageTitle = 'Analytics & Insights';
ob_start(); 
?>
<div class="page-header">
    <h1>Analytics & AI Insights</h1>
    <p class="text-secondary">Comprehensive financial health, spending trends, and automated radar detections.</p>
</div>

<!-- Financial Health Score -->
<div class="card glass mb-4" style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
    <div style="position: relative; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center;">
        <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="54" fill="none" stroke="var(--border-color)" stroke-width="8"/>
            <circle cx="60" cy="60" r="54" fill="none" stroke="<?= $health['score'] >= 70 ? 'var(--success)' : ($health['score'] >= 40 ? 'var(--accent)' : 'var(--danger)') ?>" 
                    stroke-width="8" stroke-dasharray="339.292" stroke-dashoffset="<?= 339.292 - (339.292 * $health['score'] / 100) ?>" 
                    transform="rotate(-90 60 60)" style="transition: stroke-dashoffset 1s ease;"/>
        </svg>
        <div style="position: absolute; text-align: center;">
            <span style="font-size: 2rem; font-weight: bold; color: var(--text-primary);"><?= $health['score'] ?></span>
            <span style="display: block; font-size: 0.8rem; color: var(--text-secondary);">Grade <?= $health['grade'] ?></span>
        </div>
    </div>
    <div style="flex: 1; min-width: 250px;">
        <h3 style="margin-top: 0;">Financial Health Score</h3>
        <p class="text-secondary">Based on your savings rate (<?= $health['savings_rate'] ?>%), emergency fund coverage (<?= $health['emergency_months'] ?> months), and tracking consistency.</p>
        <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
            <span class="badge" style="background: rgba(16,185,129,0.15); color: var(--success);">Savings: <?= $health['savings_rate'] ?>%</span>
            <span class="badge" style="background: rgba(59,130,246,0.15); color: var(--accent);">Emergency: <?= $health['emergency_months'] ?> mo</span>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <!-- AI Recommendations -->
    <div class="card glass">
        <h3><i class="fas fa-lightbulb" style="color: #fbbf24;"></i> Personalized Insights</h3>
        <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($recommendations as $rec): ?>
            <div style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px; border-left: 3px solid <?= $rec['color'] ?>; display: flex; gap: 1rem; align-items: start;">
                <i class="<?= $rec['icon'] ?>" style="color: <?= $rec['color'] ?>; margin-top: 0.2rem;"></i>
                <div>
                    <strong style="display: block; margin-bottom: 0.25rem;"><?= e($rec['title']) ?></strong>
                    <span class="text-secondary" style="font-size: 0.9rem;"><?= e($rec['text']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Radar: Subscriptions -->
    <div class="card glass">
        <h3><i class="fas fa-radar" style="color: var(--accent);"></i> Radar: Detected Subscriptions</h3>
        <?php if (empty($subscriptions)): ?>
            <p class="text-secondary mt-3">No recurring subscription patterns detected in the last 6 months.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; margin-top: 1rem;">
                <?php foreach ($subscriptions as $sub): ?>
                <li class="flex-between" style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                    <div>
                        <strong><?= e($sub['description'] ?: 'Unknown Service') ?></strong>
                        <div class="text-secondary" style="font-size: 0.8rem;">Occurs ~<?= (int)(180 / $sub['frequency']) ?> days • Last: <?= e(date('M d', strtotime($sub['last_date']))) ?></div>
                    </div>
                    <span style="font-weight: bold; color: var(--danger);">-<?= number_format($sub['total_amount'], 2) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Active Alerts -->
<?php if (!empty($alerts)): ?>
<div class="card glass mt-4" style="border-left: 4px solid #f59e0b;">
    <h3><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Active Radar Alerts</h3>
    <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
        <?php foreach ($alerts as $alert): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: rgba(245, 158, 11, 0.05); border-radius: 8px;">
            <div>
                <span class="badge badge-<?= $alert['severity'] ?>" style="margin-right: 0.5rem;"><?= strtoupper(e($alert['severity'])) ?></span>
                <strong><?= e($alert['title']) ?></strong>
                <p class="text-secondary" style="margin: 0.25rem 0 0; font-size: 0.85rem;"><?= e($alert['description']) ?></p>
            </div>
            <form onsubmit="resolveAlert(event, <?= $alert['id'] ?>)">
                <?= \App\Core\CSRF::field() ?>
                <button type="submit" class="btn btn-sm" style="background: var(--text-secondary); color: white;">Dismiss</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
async function resolveAlert(e, id) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const res = await fetch('<?= url('/analytics/resolve-alert/') ?>' + id, {
            method: 'POST',
            body: formData
        });
        if (res.ok) {
            e.target.closest('.card').remove(); // Simple UI update
            // In production, re-fetch or use a more robust DOM update
        }
    } catch (err) {
        console.error('Failed to resolve alert', err);
    }
}
</script>

<style>
.badge-high { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
.badge-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-low { background: rgba(59, 130, 246, 0.15); color: var(--accent); }
</style>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>