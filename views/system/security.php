<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>🛡️ System Security</h1>
        <p style="color: var(--text-secondary);">Manage global database backups and restoration.</p>
    </div>
</header>

<div class="card" style="border: 1px solid var(--accent-yellow); margin-top: 24px; max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px;">
        <div style="flex: 1; min-width: 300px;">
            <h3 style="color: var(--accent-yellow); margin-bottom: 8px;">💾 Master Database Management</h3>
            <p style="color: var(--text-secondary); font-size: 13px; margin: 0; line-height: 1.5;">Create a complete JSON snapshot of your entire system, or restore from a previous backup file. <strong>Warning: Restoring a backup will instantly wipe all current profiles and data.</strong></p>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px; min-width: 200px;">
            <a href="<?= $basePath ?>/system/master-backup" class="btn ghost" style="border: 1px solid var(--border); text-align: center;">📥 Download Master Backup</a>
            
            <form action="<?= $basePath ?>/system/master-restore" method="POST" enctype="multipart/form-data" id="restore-form" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <input type="file" name="backup_file" id="backup-file" accept=".json" style="display: none;">
                <button type="button" class="btn danger" id="trigger-restore-btn" style="width: 100%;">📤 Restore Backup</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Database Restore Logic
    document.getElementById('trigger-restore-btn')?.addEventListener('click', () => {
        document.getElementById('backup-file').click();
    });

    document.getElementById('backup-file')?.addEventListener('change', function() {
        if(this.value) {
            confirmAction('Overwrite Entire Database?', 'Are you absolutely sure? This will instantly wipe your current system and replace it entirely with the backup file you selected.', () => {
                document.getElementById('restore-form').submit();
            });
        }
    });
});
</script>