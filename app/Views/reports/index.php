<?php 
$pageTitle = 'Reports';
ob_start(); 
?>
<div class="page-header flex-between">
    <h1>Expense Report</h1>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input type="month" name="month" value="<?= e($currentMonth) ?>" onchange="window.location.href='?month='+this.value" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary);">
        <a href="<?= url('/reports/export-csv?month=' . $currentMonth) ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary);"><i class="fas fa-file-csv text-accent"></i> Export CSV</a>
        <a href="<?= url('/reports/export-pdf?month=' . $currentMonth) ?>" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Executive Statement (PDF)</a>
    </div>
</div>

<div class="card glass">
    <?php if (empty($reportData)): ?>
        <div class="text-center" style="padding: 2rem;">
            <i class="fas fa-chart-pie" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <p class="text-secondary">No expense data found for this month.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Transactions</th>
                        <th>Total Spent</th>
                        <th>% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = '0.00';
                    foreach ($reportData as $r) {
                        $grandTotal = \App\Services\MathService::add($grandTotal, (string) ($r['total_amount'] ?? '0.00'));
                    }
                    foreach ($reportData as $row): 
                        $pct = \App\Services\MathService::gt($grandTotal, '0.00') ? (float) \App\Services\MathService::mul(\App\Services\MathService::div((string)$row['total_amount'], $grandTotal, 4), '100', 1) : 0.0;
                    ?>
                    <tr>
                        <td>
                            <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:<?= e($row['color']) ?>; margin-right:0.5rem;"></span>
                            <?= e($row['category_name']) ?>
                        </td>
                        <td><?= (int)$row['transaction_count'] ?></td>
                        <td><strong><?= number_format($row['total_amount'], 2) ?></strong></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <div style="flex:1; background:var(--border-color); height:6px; border-radius:3px;">
                                    <div style="width:<?= $pct ?>%; background:<?= e($row['color']) ?>; height:100%; border-radius:3px;"></div>
                                </div>
                                <span style="font-size:0.85rem;"><?= number_format($pct, 1) ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold; border-top: 2px solid var(--border-color);">
                        <td colspan="2">Grand Total</td>
                        <td class="sensitive-data" colspan="2"><?= number_format($grandTotal, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php 
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]); 
?>