<?php
declare(strict_types=1);
use App\Core\Auth;
$pageTitle = 'Settings & Data Management';
ob_start();
$user = Auth::user();
?>

<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Settings & Data Management</h1>
            <p class="text-secondary">Export institutional-grade datasets, configure AES-256-GCM backups, and restore workspace snapshots.</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= url('/settings/backup?format=stream_csv') ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-decoration: none;">
                <i class="fas fa-file-csv text-accent"></i> Quick CSV Export
            </a>
            <a href="<?= url('/settings/backup?format=json') ?>" class="btn btn-primary" style="text-decoration: none;">
                <i class="fas fa-database"></i> Full JSON Backup
            </a>
        </div>
    </div>
</div>

<div class="card glass" style="padding: 0; overflow: hidden; margin-bottom: 2rem;">
    <!-- Tab Navigation -->
    <div style="display: flex; border-bottom: 1px solid var(--border-color); overflow-x: auto; background: rgba(0,0,0,0.02);">
        <button class="tab-btn active" onclick="switchSettingsTab('export', event)"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid var(--accent); color: var(--accent); font-weight: 600; cursor: pointer;">
            <i class="fas fa-cloud-download-alt mr-1"></i> Export & Backup
        </button>
        <button class="tab-btn" onclick="switchSettingsTab('encryption', event)"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">
            <i class="fas fa-lock mr-1"></i> Encrypted Backups
        </button>
        <button class="tab-btn" onclick="switchSettingsTab('import', event)"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">
            <i class="fas fa-cloud-upload-alt mr-1"></i> Restore & Staging
        </button>
        <button class="tab-btn" onclick="switchSettingsTab('history', event)"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">
            <i class="fas fa-history mr-1"></i> Backup History
        </button>
        <button class="tab-btn" onclick="switchSettingsTab('data', event)"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">
            <i class="fas fa-shield-alt mr-1"></i> Data Management
        </button>
        <button class="tab-btn" onclick="switchSettingsTab('about', event)"
            style="padding: 1rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); cursor: pointer;">
            <i class="fas fa-info-circle mr-1"></i> About
        </button>
    </div>

    <div style="padding: 1.5rem;">

        <!-- TAB 1: EXPORT & BACKUP -->
        <div id="tab-export" class="tab-content">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 0.25rem;">Standard Exports & Snapshots</h3>
                <p class="text-secondary" style="font-size: 0.9rem;">
                    Download unencrypted financial snapshots and reports. All CSV exports feature automatic UTF-8 BOM encoding and spreadsheet formula injection protection.
                </p>
            </div>

            <div class="grid grid-3" style="gap: 1.25rem; margin-bottom: 2rem;">
                <div class="card glass" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; color: var(--accent);">
                                <i class="fas fa-file-code fa-lg"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 1rem;">JSON v2.0 Snapshot</h4>
                                <small class="text-secondary">Full hierarchical ecosystem</small>
                            </div>
                        </div>
                        <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">
                            Complete database state export formatted in structured JSON v2.0 schema, including accounts, transactions, splits, vaults, and progression stats.
                        </p>
                    </div>
                    <a href="<?= url('/settings/backup?format=json') ?>" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                        <i class="fas fa-download mr-1"></i> Download JSON v2.0
                    </a>
                </div>

                <div class="card glass" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: var(--success);">
                                <i class="fas fa-database fa-lg"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 1rem;">Pure-PHP SQL Dump</h4>
                                <small class="text-secondary">Native SQL DDL & Inserts</small>
                            </div>
                        </div>
                        <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">
                            Chunked SQL dump compatible with MySQL and MariaDB, generating clean transactional inserts for seamless server-side restoration.
                        </p>
                    </div>
                    <a href="<?= url('/settings/backup?format=sql') ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none;">
                        <i class="fas fa-file-download mr-1"></i> Download SQL Dump
                    </a>
                </div>

                <div class="card glass" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                                <i class="fas fa-file-csv fa-lg"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 1rem;">Streaming CSV (O(1) Memory)</h4>
                                <small class="text-secondary">Direct ledger streaming</small>
                            </div>
                        </div>
                        <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1rem;">
                            Memory-bounded transaction ledger streaming directly to output. Safe from formula injection and formatted with UTF-8 BOM.
                        </p>
                    </div>
                    <a href="<?= url('/settings/backup?format=stream_csv') ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none;">
                        <i class="fas fa-stream mr-1"></i> Stream Transactions CSV
                    </a>
                </div>
            </div>

            <h4 style="margin-bottom: 1rem;">Auxiliary Reports & Archives</h4>
            <div class="grid grid-4" style="gap: 1rem;">
                <a href="<?= url('/settings/backup?format=zip') ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none; padding: 0.85rem;">
                    <i class="fas fa-file-archive text-accent mr-1"></i> Modular ZIP Archive
                </a>
                <a href="<?= url('/settings/backup?format=xlsx') ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none; padding: 0.85rem;">
                    <i class="fas fa-file-excel text-success mr-1"></i> Excel Spreadsheet (.xlsx)
                </a>
                <a href="<?= url('/settings/backup?format=pdf') ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none; padding: 0.85rem;">
                    <i class="fas fa-file-pdf text-danger mr-1"></i> PDF Financial Statement
                </a>
                <a href="<?= url('/settings/backup?format=html') ?>" class="btn" style="background: var(--bg-glass-solid); border: 1px solid var(--border-color); color: var(--text-primary); text-align: center; text-decoration: none; padding: 0.85rem;">
                    <i class="fas fa-code mr-1"></i> Interactive HTML Report
                </a>
            </div>
        </div>

        <!-- TAB 2: ENCRYPTED BACKUPS -->
        <div id="tab-encryption" class="tab-content" style="display: none;">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 0.25rem;">Military-Grade Encrypted Backups</h3>
                <p class="text-secondary" style="font-size: 0.9rem;">
                    Protect sensitive financial records with authenticated <strong>AES-256-GCM</strong> encryption and <strong>PBKDF2 key derivation (100,000 SHA-256 iterations)</strong>. Archives are pre-compressed with Gzip before symmetric encryption.
                </p>
            </div>

            <div class="card glass" style="max-width: 650px; background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); padding: 1.5rem;">
                <form id="encryptedBackupForm" onsubmit="handleEncryptedBackup(event)">
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Choose Base Format:</label>
                        <div style="display: flex; gap: 1.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="radio" name="enc_format" value="json" checked>
                                <span>JSON v2.0 Archive (.json.gz.enc)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="radio" name="enc_format" value="sql">
                                <span>SQL Database Dump (.sql.gz.enc)</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="encPassphrase" style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Set Encryption Passphrase:</label>
                        <div style="position: relative;">
                            <input type="password" id="encPassphrase" required placeholder="Enter strong encryption passphrase"
                                style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary);">
                            <button type="button" onclick="togglePassVisibility('encPassphrase', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-secondary" style="margin-top: 0.35rem; display: block;">
                            <i class="fas fa-shield-alt text-accent"></i> Key derived via PBKDF2 (100,000 rounds) + 12-byte IV + 16-byte authentication tag.
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="encPassphraseConfirm" style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Confirm Passphrase:</label>
                        <input type="password" id="encPassphraseConfirm" required placeholder="Re-enter passphrase"
                            style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary);">
                    </div>

                    <div style="background: rgba(245, 158, 11, 0.08); border-left: 4px solid #f59e0b; padding: 0.85rem; border-radius: 0 8px 8px 0; margin-bottom: 1.5rem; font-size: 0.85rem;">
                        <strong style="color: #d97706;"><i class="fas fa-exclamation-circle mr-1"></i> Security Notice:</strong>
                        There is no password recovery for encrypted backups. If you lose this passphrase, the encrypted data cannot be decrypted or restored.
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
                        <i class="fas fa-lock mr-1"></i> Generate & Download Encrypted Backup
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB 3: RESTORE & IMPORT -->
        <div id="tab-import" class="tab-content" style="display: none;">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 0.25rem;">Staging Import & Workspace Restore</h3>
                <p class="text-secondary" style="font-size: 0.9rem;">
                    Upload an unencrypted (.json, .sql) or AES-256-GCM encrypted (.enc) backup file. The staging engine performs a zero-side-effect dry run to preview schema compatibility, record counts, and integrity warnings.
                </p>
            </div>

            <!-- Upload & Analyze Form -->
            <div class="card glass" style="background: rgba(0,0,0,0.02); border: 1px dashed var(--border-color); padding: 1.5rem; max-width: 650px; margin-bottom: 1.5rem;">
                <form id="previewRestoreForm" onsubmit="handlePreviewRestore(event)">
                    <?= \App\Core\CSRF::field() ?>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Select Backup Archive:</label>
                        <input type="file" name="backup_file" id="backupFile" accept=".json,.sql,.enc" required
                            style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface);">
                        <small class="text-secondary" style="display: block; margin-top: 0.35rem;">Supported formats: .json, .sql, .enc, .gz.enc</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Passphrase (Required only if encrypted):</label>
                        <div style="position: relative;">
                            <input type="password" name="passphrase" id="restorePassphrase" placeholder="Leave empty for unencrypted backups"
                                style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary);">
                            <button type="button" onclick="togglePassVisibility('restorePassphrase', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="previewBtn">
                        <i class="fas fa-search mr-1"></i> Inspect & Dry-Run Preview
                    </button>
                </form>
            </div>

            <!-- Staging Preview Modal / Container -->
            <div id="restorePreview" style="display: none; max-width: 750px;">
                <div class="card glass" style="border-left: 4px solid var(--accent); padding: 1.5rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h4 style="margin: 0;"><i class="fas fa-clipboard-check text-accent mr-1"></i> Staging Dry-Run Analysis</h4>
                        <span id="formatBadge" class="badge" style="background: var(--accent); color: white; padding: 0.35rem 0.75rem;">JSON v2.0</span>
                    </div>

                    <div id="previewMetaGrid" class="grid grid-3" style="gap: 0.75rem; margin-bottom: 1.25rem;"></div>

                    <h5 style="margin-bottom: 0.5rem; font-size: 0.9rem;">Entity Breakdown:</h5>
                    <div id="previewCountsTable" class="table-responsive" style="margin-bottom: 1.25rem; max-height: 220px; overflow-y: auto;"></div>

                    <div id="previewWarnings" style="display: none; margin-bottom: 1.25rem;"></div>

                    <div class="card glass" style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); padding: 1rem; margin-bottom: 1.5rem;">
                        <p style="color: var(--danger); font-weight: 600; margin-bottom: 0.35rem;">
                            <i class="fas fa-exclamation-triangle"></i> Irreversible Action Warning
                        </p>
                        <p class="text-secondary" style="font-size: 0.85rem; margin: 0;">
                            Executing this restoration will <strong>permanently overwrite</strong> all current accounts, transactions, budgets, bills, and vault records for your user. An automated double-entry ledger reconciliation will execute immediately post-restore.
                        </p>
                    </div>

                    <!-- Execution Form -->
                    <form method="POST" action="<?= url('/settings/execute-restore') ?>" id="executeRestoreForm" enctype="multipart/form-data" onsubmit="return confirmExecuteRestore(event)">
                        <?= \App\Core\CSRF::field() ?>
                        <input type="file" name="backup_file" id="executeBackupFileInput" style="display: none;">
                        <input type="hidden" name="passphrase" id="executePassphraseHidden">

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Enter Account Password to Confirm:</label>
                            <input type="password" name="confirm_password" required placeholder="Your current account login password"
                                style="width: 100%; max-width: 400px; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary);">
                        </div>

                        <button type="submit" class="btn" style="background: var(--danger); color: white; padding: 0.75rem 1.5rem;">
                            <i class="fas fa-undo mr-1"></i> Execute Atomic Restore & Reconcile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TAB 4: BACKUP HISTORY -->
        <div id="tab-history" class="tab-content" style="display: none;">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 0.25rem;">Backup Audit Log</h3>
                <p class="text-secondary" style="font-size: 0.9rem;">
                    Immutable audit log of all backup archives generated and restored across your workspace.
                </p>
            </div>

            <?php if (empty($backupHistory)): ?>
                <div class="text-center" style="padding: 3rem; background: rgba(0,0,0,0.01); border-radius: 12px; border: 1px dashed var(--border-color);">
                    <i class="fas fa-archive fa-3x text-secondary" style="opacity: 0.4; margin-bottom: 1rem;"></i>
                    <p class="text-secondary" style="margin: 0;">No backups have been recorded yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Filename</th>
                                <th>Format</th>
                                <th>File Size</th>
                                <th>SHA-256 Checksum</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backupHistory as $bh): ?>
                                <tr>
                                    <td><?= e(date('M d, Y H:i:s', strtotime($bh['created_at']))) ?></td>
                                    <td><code><?= e($bh['filename']) ?></code></td>
                                    <td>
                                        <span class="badge" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
                                            <?= strtoupper(e($bh['format'])) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($bh['file_size_bytes'] / 1024, 1) ?> KB</td>
                                    <td>
                                        <code style="font-size: 0.75rem;" title="<?= e($bh['checksum_sha256']) ?>">
                                            <?= substr(e($bh['checksum_sha256']), 0, 16) ?>...
                                        </code>
                                    </td>
                                    <td>
                                        <?php if ($bh['status'] === 'restored'): ?>
                                            <span style="color: var(--success); font-weight: 600;"><i class="fas fa-check"></i> Restored</span>
                                        <?php elseif ($bh['status'] === 'completed'): ?>
                                            <span style="color: var(--accent); font-weight: 600;"><i class="fas fa-check-circle"></i> Active</span>
                                        <?php elseif ($bh['status'] === 'failed'): ?>
                                            <span style="color: var(--danger); font-weight: 600;"><i class="fas fa-times"></i> Failed</span>
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

        <!-- TAB 5: DATA MANAGEMENT -->
        <div id="tab-data" class="tab-content" style="display: none;">
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 0.25rem;">Data Purging & Workspace Reset</h3>
                <p class="text-secondary" style="font-size: 0.9rem;">Permanently remove historical transactions and ledger data while retaining your profile credentials.</p>
            </div>

            <div class="card glass" style="border: 1px solid var(--danger); background: rgba(239, 68, 68, 0.02); padding: 1.5rem; max-width: 650px;">
                <h4 style="color: var(--danger); margin-top: 0;"><i class="fas fa-trash-alt mr-1"></i> Erase All Financial Records</h4>
                <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1.25rem;">
                    This operation executes an irreversible CASCADE wipe of all transactions, splits, accounts, budgets, bills, and savings vaults. Your login credentials, security settings, and backup history log will remain intact.
                </p>

                <form method="POST" action="<?= url('/settings/delete-all') ?>" onsubmit="return confirmDeleteAll(event)">
                    <?= \App\Core\CSRF::field() ?>
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Enter Account Password to Confirm Wipe:</label>
                        <input type="password" name="confirm_password" required placeholder="Current account login password"
                            style="width: 100%; max-width: 400px; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary);">
                    </div>
                    <button type="submit" class="btn" style="background: var(--danger); color: white; padding: 0.75rem 1.5rem;">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Delete All Financial Data
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB 6: ABOUT -->
        <div id="tab-about" class="tab-content" style="display: none;">
            <div class="card glass" style="max-width: 650px; text-align: center; padding: 2.5rem; margin: 0 auto;">
                <div style="font-size: 3rem; color: var(--accent); margin-bottom: 1rem;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2 style="margin: 0 0 0.5rem;">Expense Tracker Enterprise</h2>
                <p class="text-secondary" style="margin-bottom: 1.5rem;">Institutional-Grade Personal Finance & Double-Entry Ledger</p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: left; background: rgba(0,0,0,0.02); padding: 1.5rem; border-radius: 8px; font-size: 0.9rem;">
                    <div><strong>Developer:</strong><br>Jake Panlilio</div>
                    <div><strong>Organization:</strong><br>StackSync Solutions</div>
                    <div><strong>Architecture:</strong><br>Vanilla PHP 8.x MVC + MariaDB</div>
                    <div><strong>Ledger Precision:</strong><br>BCMath Arbitrary-Precision</div>
                    <div><strong>Encryption:</strong><br>AES-256-GCM + PBKDF2</div>
                    <div><strong>Copyright:</strong><br>© 2026 StackSync Solutions</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function switchSettingsTab(tabId, ev) {
        document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.style.borderBottom = '2px solid transparent';
            el.style.color = 'var(--text-secondary)';
            el.classList.remove('active');
        });
        const target = document.getElementById('tab-' + tabId);
        if (target) {
            target.style.display = 'block';
        }
        if (ev && ev.currentTarget) {
            ev.currentTarget.style.borderBottom = '2px solid var(--accent)';
            ev.currentTarget.style.color = 'var(--accent)';
            ev.currentTarget.classList.add('active');
        }
    }

    function togglePassVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            input.type = 'password';
            btn.innerHTML = '<i class="fas fa-eye"></i>';
        }
    }

    function handleEncryptedBackup(e) {
        e.preventDefault();
        const p1 = document.getElementById('encPassphrase').value;
        const p2 = document.getElementById('encPassphraseConfirm').value;
        if (p1 !== p2) {
            alert('Error: Encryption passphrases do not match.');
            return;
        }
        if (p1.length < 8) {
            alert('Error: Passphrase must be at least 8 characters long for military-grade protection.');
            return;
        }
        const format = document.querySelector('input[name="enc_format"]:checked').value;
        window.location.href = '<?= url('/settings/backup') ?>?format=' + encodeURIComponent(format) + '&passphrase=' + encodeURIComponent(p1);
    }

    async function handlePreviewRestore(e) {
        e.preventDefault();
        const form = document.getElementById('previewRestoreForm');
        const fileInput = document.getElementById('backupFile');
        const passInput = document.getElementById('restorePassphrase');
        const btn = document.getElementById('previewBtn');
        const originalText = btn.innerHTML;

        if (!fileInput.files.length) {
            alert('Please select a backup archive to analyze.');
            return;
        }

        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Decrypting & Analyzing...';
        btn.disabled = true;

        const formData = new FormData(form);

        try {
            const res = await fetch('<?= url('/settings/preview-restore') ?>', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                document.getElementById('restorePreview').style.display = 'block';
                const meta = data.preview.metadata || {};
                const counts = data.preview.record_counts || {};
                const warnings = data.preview.warnings || [];

                document.getElementById('formatBadge').innerText = (data.preview.format || 'json').toUpperCase() + ' ARCHIVE';

                // Metadata cards
                document.getElementById('previewMetaGrid').innerHTML = `
                    <div style="padding: 0.85rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <small class="text-secondary">Export Date</small>
                        <div style="font-weight: 600;">${meta.export_timestamp || 'N/A'}</div>
                    </div>
                    <div style="padding: 0.85rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <small class="text-secondary">Schema Version</small>
                        <div style="font-weight: 600;">${meta.schema_version || '2.0.0'}</div>
                    </div>
                    <div style="padding: 0.85rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <small class="text-secondary">Total Records</small>
                        <div style="font-weight: 700; color: var(--accent);">${data.preview.total_records || 0}</div>
                    </div>
                `;

                // Entity breakdown badges
                let tableHtml = '<table class="data-table" style="font-size: 0.85rem;"><thead><tr><th>Module</th><th style="text-align:right;">Records Count</th></tr></thead><tbody>';
                for (const [table, count] of Object.entries(counts)) {
                    tableHtml += `<tr><td><code>${table}</code></td><td style="text-align:right; font-weight:600;">${count}</td></tr>`;
                }
                tableHtml += '</tbody></table>';
                document.getElementById('previewCountsTable').innerHTML = tableHtml;

                // Warnings
                const warnBox = document.getElementById('previewWarnings');
                if (warnings.length > 0) {
                    warnBox.style.display = 'block';
                    warnBox.innerHTML = '<div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 0.75rem; border-radius: 0 8px 8px 0; font-size: 0.85rem;">' +
                        warnings.map(w => `<div><i class="fas fa-info-circle text-warning mr-1"></i> ${w}</div>`).join('') +
                        '</div>';
                } else {
                    warnBox.style.display = 'none';
                }

                // Sync file & passphrase to execute form
                document.getElementById('executePassphraseHidden').value = passInput.value;
                const dt = new DataTransfer();
                dt.items.add(fileInput.files[0]);
                document.getElementById('executeBackupFileInput').files = dt.files;

                // Smooth scroll to preview
                document.getElementById('restorePreview').scrollIntoView({ behavior: 'smooth' });

            } else {
                alert('Analysis Rejected: ' + (data.error || 'Invalid or corrupted backup archive.'));
            }
        } catch (err) {
            alert('Network error while analyzing backup file: ' + err.message);
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    function confirmExecuteRestore(e) {
        return confirm('⚠️ CRITICAL WARNING: This will PERMANENTLY ERASE your existing financial workspace data and replace it with records from the archive. Continue?');
    }

    function confirmDeleteAll(e) {
        return confirm('⚠️ CRITICAL WARNING: This will PERMANENTLY DELETE all your transactions, budgets, accounts, and financial history. This operation CANNOT be undone. Are you sure?');
    }
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>