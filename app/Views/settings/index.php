<?php
declare(strict_types=1);
use App\Core\Auth;
$pageTitle = 'Settings & Data Management';
ob_start();
$user = Auth::user();
?>

<div class="page-header">
    <h1>Settings & Data Management</h1>
    <p class="text-secondary">Manage your exports, backups, and application preferences.</p>
</div>

<div class="card glass" style="padding: 0; overflow: hidden;">
    <!-- Tab Navigation -->
    <div
        style="display: flex; border-bottom: 1px solid var(--border-color); overflow-x: auto; background: rgba(0,0,0,0.02);">
        <button class="tab-btn active" onclick="switchTab('export')"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid var(--accent); color: var(--accent); font-weight: 600; cursor: pointer;">Export
            & Backup</button>
        <button class="tab-btn" onclick="switchTab('history')"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">Backup
            History</button>
        <button class="tab-btn" onclick="switchTab('import')"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">Restore
            & Import</button>
        <button class="tab-btn" onclick="switchTab('data')"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">Data
            Management</button>
        <button class="tab-btn" onclick="switchTab('about')"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">Acknowledgements</button>
    </div>

    <div style="padding: 1.5rem;">

        <!-- TAB 1: EXPORT & BACKUP -->
        <div id="tab-export" class="tab-content">
            <h3>Export Your Financial Data</h3>
            <p class="text-secondary" style="margin-bottom: 1.5rem;">Download a complete snapshot of your financial
                ecosystem. All exports are strictly isolated to your account.</p>
            <div class="grid grid-4" style="gap: 1rem;">
                <a href="<?= url('/settings/backup?format=json') ?>" class="btn btn-primary"
                    style="text-align: center; text-decoration: none;"><i class="fas fa-file-code"></i> JSON Backup</a>
                <a href="<?= url('/settings/backup?format=zip') ?>" class="btn"
                    style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none;"><i
                        class="fas fa-file-archive"></i> CSV Archive (ZIP)</a>
                <a href="<?= url('/settings/backup?format=xlsx') ?>" class="btn"
                    style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none;"><i
                        class="fas fa-file-excel"></i> Excel Report</a>
                <a href="<?= url('/settings/backup?format=pdf') ?>" class="btn"
                    style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none;"><i
                        class="fas fa-file-pdf"></i> PDF Report</a>
                <a href="<?= url('/settings/backup?format=html') ?>" class="btn"
                    style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none;"><i
                        class="fas fa-file-code"></i> HTML Interactive Report</a>
            </div>
        </div>

        <!-- TAB 2: BACKUP HISTORY -->
        <div id="tab-history" class="tab-content" style="display: none;">
            <h3>Backup History</h3>
            <p class="text-secondary" style="margin-bottom: 1.5rem;">A log of all backup operations performed on your
                account.</p>
            <?php if (empty($backupHistory)): ?>
                <p class="text-secondary text-center" style="padding: 2rem;">No backups have been generated yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Filename</th>
                                <th>Format</th>
                                <th>Size</th>
                                <th>Modules</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backupHistory as $bh):
                                $modules = json_decode($bh['modules_included'], true) ?? [];
                                ?>
                                <tr>
                                    <td><?= e(date('M d, Y H:i', strtotime($bh['created_at']))) ?></td>
                                    <td><small class="text-secondary"><?= e($bh['filename']) ?></small></td>
                                    <td><span class="badge"
                                            style="background: var(--border-color);"><?= strtoupper(e($bh['format'])) ?></span>
                                    </td>
                                    <td><?= number_format($bh['file_size_bytes'] / 1024, 1) ?> KB</td>
                                    <td><small><?= count($modules) ?> modules</small></td>
                                    <td>
                                        <?php if ($bh['status'] === 'restored'): ?>
                                            <span style="color: var(--success);"><i class="fas fa-check"></i> Restored</span>
                                        <?php elseif ($bh['status'] === 'completed'): ?>
                                            <span style="color: var(--accent);"><i class="fas fa-check-circle"></i> Completed</span>
                                        <?php elseif ($bh['status'] === 'failed'): ?>
                                            <span style="color: var(--danger);"><i class="fas fa-times"></i> Failed</span>
                                        <?php else: ?>
                                            <span class="text-secondary">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 3: RESTORE & IMPORT -->
        <div id="tab-import" class="tab-content" style="display: none;">
            <h3>Restore from Backup</h3>
            <p class="text-secondary" style="margin-bottom: 1.5rem;">Upload a JSON backup file to restore your
                workspace. This will replace your current financial data.</p>

            <div class="card glass" style="background: rgba(0,0,0,0.02); border: 1px dashed var(--border-color);">
                <form id="previewRestoreForm" onsubmit="handlePreviewRestore(event)">
                    <?= \App\Core\CSRF::field() ?>
                    <div class="form-group">
                        <label>Select JSON Backup File</label>
                        <input type="file" name="backup_file" id="backupFile" accept=".json" required
                            style="width: 100%; padding: 0.5rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" id="previewBtn"><i class="fas fa-search"></i> Analyze
                        Backup</button>
                </form>
            </div>

            <div id="restorePreview" style="display: none; margin-top: 1.5rem;">
                <h4>Restore Preview</h4>
                <div id="previewDetails" class="grid grid-2" style="gap: 1rem; margin-bottom: 1rem;"></div>

                <div class="card glass"
                    style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2);">
                    <p style="color: var(--danger); font-weight: 600; margin-bottom: 0.5rem;"><i
                            class="fas fa-exclamation-triangle"></i> Warning</p>
                    <p class="text-secondary" style="font-size: 0.9rem;">Restoring this backup will <strong>permanently
                            delete</strong> your current financial data and replace it with the data from the file. This
                        action cannot be undone.</p>
                </div>

                <form method="POST" action="<?= url('/settings/execute-restore') ?>" id="executeRestoreForm"
                    style="margin-top: 1rem;" onsubmit="return confirmExecuteRestore(event)">
                    <?= \App\Core\CSRF::field() ?>
                    <input type="hidden" name="backup_file_path" id="finalBackupPath">
                    <div class="form-group">
                        <label>Enter your password to confirm restoration:</label>
                        <input type="password" name="confirm_password" required
                            style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    </div>
                    <button type="submit" class="btn" style="background: var(--danger); color: white;"><i
                            class="fas fa-undo"></i> Erase & Restore Backup</button>
                </form>
            </div>
        </div>

        <!-- TAB 4: DATA MANAGEMENT -->
        <div id="tab-data" class="tab-content" style="display: none;">
            <h3>Data Management</h3>
            <p class="text-secondary" style="margin-bottom: 1.5rem;">Permanently remove your financial data from the
                system.</p>

            <div class="card glass" style="border: 1px solid var(--danger); background: rgba(239, 68, 68, 0.02);">
                <h4 style="color: var(--danger);">Delete All Financial Data</h4>
                <p class="text-secondary" style="font-size: 0.9rem; margin-bottom: 1rem;">This will safely delete all
                    transactions, accounts, budgets, bills, vaults, and achievements associated with your account. Your
                    login credentials, preferences, and backup history will remain intact.</p>

                <form method="POST" action="<?= url('/settings/delete-all') ?>"
                    onsubmit="return confirmDeleteAll(event)">
                    <?= \App\Core\CSRF::field() ?>
                    <div class="form-group">
                        <label>Enter your password to confirm deletion:</label>
                        <input type="password" name="confirm_password" required
                            style="width: 100%; max-width: 400px; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    </div>
                    <button type="submit" class="btn" style="background: var(--danger); color: white;"><i
                            class="fas fa-trash"></i> Delete All My Data</button>
                </form>
            </div>
        </div>

        <!-- TAB 5: ACKNOWLEDGEMENTS -->
        <div id="tab-about" class="tab-content" style="display: none;">
            <h3>Acknowledgements</h3>
            <div class="card glass" style="max-width: 600px; text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; color: var(--accent); margin-bottom: 1rem;"><i class="fas fa-code"></i>
                </div>
                <h2 style="margin: 0 0 0.5rem;">Expense Tracker</h2>
                <p class="text-secondary" style="margin-bottom: 1.5rem;">Enterprise-Grade Personal Finance Platform</p>

                <div
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: left; background: rgba(0,0,0,0.02); padding: 1.5rem; border-radius: 8px;">
                    <div><strong>Developer:</strong><br>Jake Panlilio</div>
                    <div><strong>Organization:</strong><br>StackSync Solutions</div>
                    <div><strong>Copyright:</strong><br>© 2026 StackSync Solutions</div>
                    <div><strong>Facebook:</strong><br><a href="https://fb.com/StackSyncSolutions" target="_blank"
                            class="link">fb.com/StackSyncSolutions</a></div>
                    <div><strong>Framework:</strong><br>Vanilla PHP MVC</div>
                    <div><strong>Database:</strong><br>MySQL</div>
                    <div><strong>Server:</strong><br>Apache</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.style.borderBottom = '2px solid transparent';
            el.style.color = 'var(--text-secondary)';
        });
        document.getElementById('tab-' + tabId).style.display = 'block';
        const activeBtn = event.target;
        activeBtn.style.borderBottom = '2px solid var(--accent)';
        activeBtn.style.color = 'var(--accent)';
    }

    async function handlePreviewRestore(e) {
        e.preventDefault();
        const form = document.getElementById('previewRestoreForm');
        const btn = document.getElementById('previewBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
        btn.disabled = true;

        const formData = new FormData(form);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

        try {
            const res = await fetch('<?= url('/settings/preview-restore') ?>', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                document.getElementById('restorePreview').style.display = 'block';
                const meta = data.preview.metadata;
                const counts = data.preview.record_counts;

                let html = `
                    <div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">Backup Date</div>
                        <div style="font-weight: 600;">${meta.export_timestamp}</div>
                    </div>
                    <div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">Schema Version</div>
                        <div style="font-weight: 600;">${meta.schema_version}</div>
                    </div>
                    <div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">Total Records</div>
                        <div style="font-weight: 600; color: var(--accent);">${data.preview.total_records}</div>
                    </div>
                    <div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">Modules Included</div>
                        <div style="font-weight: 600;">${Object.keys(counts).length}</div>
                    </div>
                `;
                document.getElementById('previewDetails').innerHTML = html;
            } else {
                alert('Validation Failed: ' + data.error);
            }
        } catch (err) {
            alert('Network error during preview.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    function confirmExecuteRestore(e) {
        return confirm('⚠️ CRITICAL WARNING: This will ERASE your current data and replace it with the backup. Are you absolutely sure?');
    }

    function confirmDeleteAll(e) {
        return confirm('⚠️ CRITICAL WARNING: This will PERMANENTLY DELETE all your financial data. This cannot be undone. Continue?');
    }
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>