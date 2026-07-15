<?php
declare(strict_types=1);
$pageTitle = 'Settings & Backup';
ob_start();
?>
<div class="page-header">
    <h1>System Settings & Backup</h1>
    <p class="text-secondary">Manage your data exports, restore from backup, and view system information.</p>
</div>

<!-- Export Section -->
<h3 class="mb-3"
    style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
    <i class="fas fa-file-export"></i> Export Data
</h3>
<div class="grid grid-2" style="margin-bottom: 2rem;">
    <!-- JSON Backup -->
    <div class="card glass"
        style="display: flex; flex-direction: column; justify-content: space-between; min-height: 280px;">
        <div>
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div
                    style="background: rgba(59, 130, 246, 0.15); color: var(--accent); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fas fa-file-code"></i>
                </div>
                <div>
                    <h3 style="margin: 0;">JSON Backup</h3>
                    <span class="text-secondary" style="font-size: 0.85rem;">Required for System Restore</span>
                </div>
            </div>
            <p class="text-secondary" style="font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                Exports a complete, structured dump of all your financial data. Use this file to restore your account in
                the future.
            </p>
        </div>
        <a href="<?= url('/settings/backup?format=json') ?>" class="btn btn-primary btn-block">
            <i class="fas fa-download"></i> Download JSON
        </a>
    </div>

    <!-- XLSX Backup -->
    <div class="card glass"
        style="display: flex; flex-direction: column; justify-content: space-between; min-height: 280px;">
        <div>
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div
                    style="background: rgba(16, 185, 129, 0.15); color: var(--success); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div>
                    <h3 style="margin: 0;">Excel (XLSX)</h3>
                    <span class="text-secondary" style="font-size: 0.85rem;">Best for Viewing & Analysis</span>
                </div>
            </div>
            <p class="text-secondary" style="font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                Generates a multi-sheet workbook containing your Transactions, Accounts, and Categories with conditional
                formatting.
            </p>
        </div>
        <a href="<?= url('/settings/backup?format=xlsx') ?>" class="btn btn-block"
            style="background: var(--success); color: white;">
            <i class="fas fa-download"></i> Download XLSX
        </a>
    </div>

    <!-- CSV Backup -->
    <div class="card glass"
        style="display: flex; flex-direction: column; justify-content: space-between; min-height: 280px;">
        <div>
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div
                    style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fas fa-file-csv"></i>
                </div>
                <div>
                    <h3 style="margin: 0;">CSV Export</h3>
                    <span class="text-secondary" style="font-size: 0.85rem;">Universal Compatibility</span>
                </div>
            </div>
            <p class="text-secondary" style="font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                A lightweight, comma-separated values file of your core transaction data. Compatible with Google Sheets
                and legacy tools.
            </p>
        </div>
        <a href="<?= url('/settings/backup?format=csv') ?>" class="btn btn-block"
            style="background: #f59e0b; color: white;">
            <i class="fas fa-download"></i> Download CSV
        </a>
    </div>

    <!-- PDF Report -->
    <div class="card glass"
        style="display: flex; flex-direction: column; justify-content: space-between; min-height: 280px;">
        <div>
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div
                    style="background: rgba(239, 68, 68, 0.15); color: var(--danger); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div>
                    <h3 style="margin: 0;">PDF Report</h3>
                    <span class="text-secondary" style="font-size: 0.85rem;">Read-Only Summary</span>
                </div>
            </div>
            <p class="text-secondary" style="font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                Generates a clean, formatted, read-only PDF summary of your accounts and financial standing. Ideal for
                printing.
            </p>
        </div>
        <a href="<?= url('/settings/backup?format=pdf') ?>" class="btn btn-block"
            style="background: var(--danger); color: white;">
            <i class="fas fa-download"></i> Download PDF
        </a>
    </div>
</div>

<!-- Restore Section -->
<h3 class="mb-3"
    style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
    <i class="fas fa-file-import"></i> Restore Data
</h3>
<div class="card glass" style="border-left: 4px solid #f59e0b;">
    <div class="flex-between" style="align-items: start;">
        <div style="flex: 1;">
            <h3 style="margin-top: 0; color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> Warning: Destructive
                Action</h3>
            <p class="text-secondary" style="font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                Restoring a backup will <strong>permanently delete</strong> all your current accounts, transactions,
                budgets, and vaults, replacing them with the data from the uploaded JSON file. This action cannot be
                undone.
            </p>
            <form method="POST" action="<?= url('/settings/restore') ?>" enctype="multipart/form-data"
                class="form-stack" style="max-width: 500px;">
                <?= \App\Core\CSRF::field() ?>
                <div class="form-group">
                    <label>1. Select JSON Backup File</label>
                    <input type="file" name="backup_file" accept=".json" required style="padding: 0.5rem;">
                </div>
                <div class="form-group">
                    <label>2. Confirm with your Password</label>
                    <input type="password" name="confirm_password" placeholder="Enter your account password" required>
                </div>
                <button type="submit" class="btn" style="background: #f59e0b; color: white; width: 100%;"
                    onclick="return confirm('Are you absolutely sure? All current data will be erased and replaced.')">
                    <i class="fas fa-sync-alt"></i> Erase & Restore Data
                </button>
            </form>
        </div>
    </div>
</div>

<!-- System Info -->
<h3 class="mt-4 mb-3"
    style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
    <i class="fas fa-shield-alt"></i> System Information
</h3>
<div class="card glass">
    <ul style="list-style: none; padding: 0; margin: 0; line-height: 2.2; color: var(--text-secondary);">
        <li><strong>PHP Version:</strong> <?= PHP_VERSION ?></li>
        <li><strong>Database Engine:</strong> MySQL / MariaDB</li>
        <li><strong>Storage Path:</strong> <?= BASE_PATH ?>/storage/</li>
        <li><strong>Environment:</strong> <?= (require BASE_PATH . '/config/config.php')['env'] ?? 'production' ?></li>
    </ul>
</div>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>