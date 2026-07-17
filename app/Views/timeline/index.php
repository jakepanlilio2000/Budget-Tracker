<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Financial Timeline';
ob_start();
$sym = $baseCurrency['symbol'];
$f = $filters;
?>
<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Financial Timeline</h1>
        <p class="text-secondary">Your complete history of financial activities.</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('filterDrawer').classList.toggle('open')">
        <i class="fas fa-filter"></i> Filters
    </button>
</div>

<div class="grid" style="grid-template-columns: 280px 1fr; gap: 1.5rem; align-items: start;">
    <!-- Filter Sidebar -->
    <div class="card glass" id="filterDrawer" style="position: sticky; top: 80px;">
        <h3><i class="fas fa-sliders-h"></i> Filters</h3>
        <form method="GET" action="<?= url('/timeline') ?>" class="form-stack mt-3">
            <div class="form-group">
                <label>Module</label>
                <select name="module">
                    <option value="">All Modules</option>
                    <option value="transactions" <?= ($f['module'] ?? '') === 'transactions' ? 'selected' : '' ?>>
                        Transactions</option>
                    <option value="bills" <?= ($f['module'] ?? '') === 'bills' ? 'selected' : '' ?>>Bills</option>
                    <option value="vaults" <?= ($f['module'] ?? '') === 'vaults' ? 'selected' : '' ?>>Savings Vaults
                    </option>
                    <option value="salaries" <?= ($f['module'] ?? '') === 'salaries' ? 'selected' : '' ?>>Salaries
                    </option>
                </select>
            </div>
            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="date_from" value="<?= e($f['date_from'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="date_to" value="<?= e($f['date_to'] ?? '') ?>">
            </div>
            <div class="flex-between mt-3">
                <a href="<?= url('/timeline') ?>" class="btn btn-sm"
                    style="background: var(--text-secondary); color: white;">Clear</a>
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
            </div>
        </form>
    </div>

    <!-- Timeline Feed -->
    <div class="card glass" style="min-height: 500px;">
        <?php if (empty($events)): ?>
            <div class="text-center" style="padding: 3rem;">
                <i class="fas fa-history" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <p class="text-secondary">No timeline events found for the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="timeline-feed" id="timelineFeed">
                <?php foreach ($events as $event): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker" style="background: <?= e($event['color']) ?>;"></div>
                        <div class="glass timeline-card"
                            style="padding: 1rem; border-radius: 8px; border-left: 3px solid <?= e($event['color']) ?>;">
                            <div class="flex-between" style="margin-bottom: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas <?= e($event['icon']) ?>" style="color: <?= e($event['color']) ?>;"></i>
                                    <strong>
                                        <?= ucfirst(e($event['action'])) ?> in
                                        <?= ucfirst(e($event['module'])) ?>
                                    </strong>
                                </div>
                                <small class="text-secondary">
                                    <?= e(date('M d, Y h:i A', strtotime($event['created_at']))) ?>
                                </small>
                            </div>
                            <p style="margin: 0 0 0.5rem; color: var(--text-primary);">
                                <?= e($event['description']) ?>
                            </p>

                            <?php if ($event['amount'] > 0): ?>
                                <div class="sensitive-data"
                                    style="font-weight: bold; color: <?= $event['action'] === 'deposit' || $event['action'] === 'income' ? 'var(--success)' : 'var(--danger)' ?>">
                                    <?= $event['action'] === 'deposit' || $event['action'] === 'income' ? '+' : '-' ?>
                                    <?= e($event['currency_symbol'] ?: $sym) ?>
                                    <?= number_format((float) $event['amount'], 2) ?>
                                </div>
                            <?php endif; ?>

                            <div
                                style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--text-secondary); display: flex; gap: 1rem; flex-wrap: wrap;">
                                <?php if ($event['account_name']): ?><span><i class="fas fa-building-columns"></i>
                                        <?= e($event['account_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($event['category_name']): ?><span><i class="fas fa-tag"></i>
                                        <?= e($event['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-3">
                <button id="loadMoreBtn" class="btn"
                    style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary);">
                    Load More
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    let offset = 50;
    document.getElementById('loadMoreBtn')?.addEventListener('click', async function () {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

        try {
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.set('offset', offset);
            const res = await fetch('<?= url('/timeline/load-more') ?>?' + currentParams.toString());
            const data = await res.json();

            if (data.success && data.events.length > 0) {
                const feed = document.getElementById('timelineFeed');
                data.events.forEach(ev => {
                    const div = document.createElement('div');
                    div.className = 'timeline-item';

                    const amountSign = (parseFloat(ev.amount) > 0 && (ev.action === 'deposit' || ev.action === 'income')) ? '+' : '-';
                    const amountColor = (parseFloat(ev.amount) > 0 && (ev.action === 'deposit' || ev.action === 'income')) ? 'var(--success)' : 'var(--danger)';

                    div.innerHTML = `
                        <div class="timeline-marker" style="background: ${ev.color};"></div>
                        <div class="glass timeline-card" style="padding: 1rem; border-radius: 8px; border-left: 3px solid ${ev.color};">
                            <div class="flex-between" style="margin-bottom: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas ${ev.icon}" style="color: ${ev.color};"></i>
                                    <strong>${ev.action.charAt(0).toUpperCase() + ev.action.slice(1)} in ${ev.module.charAt(0).toUpperCase() + ev.module.slice(1)}</strong>
                                </div>
                                <small class="text-secondary">${new Date(ev.created_at).toLocaleString()}</small>
                            </div>
                            <p style="margin: 0 0 0.5rem;">${ev.description}</p>
                            ${parseFloat(ev.amount) > 0 ? `
                                <div class="sensitive-data" style="font-weight: bold; color: ${amountColor};">
                                    ${amountSign} ${ev.currency_symbol || '<?= $sym ?>'} ${Math.abs(parseFloat(ev.amount)).toFixed(2)}
                                </div>
                            ` : ''}
                            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--text-secondary); display: flex; gap: 1rem; flex-wrap: wrap;">
                                ${ev.account_name ? `<span><i class="fas fa-building-columns"></i> ${ev.account_name}</span>` : ''}
                                ${ev.category_name ? `<span><i class="fas fa-tag"></i> ${ev.category_name}</span>` : ''}
                            </div>
                        </div>
                    `;
                    feed.appendChild(div);
                });

                offset += 50;
                if (!data.hasMore) {
                    this.style.display = 'none';
                }
            } else {
                this.style.display = 'none';
            }
        } catch (err) {
            console.error('Failed to load more', err);
            this.innerHTML = 'Error Loading';
        } finally {
            if (this.style.display !== 'none') {
                this.disabled = false;
                this.innerHTML = 'Load More';
            }
        }
    });
</script>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>