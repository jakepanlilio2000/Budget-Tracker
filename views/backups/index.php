<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-hard-drive" style="color: var(--accent-blue); margin-right: 8px;"></i> Backups & Export</h1>
        <p style="color: var(--text-secondary);">Secure your data or reset your financial ledger.</p>
    </div>
</header>

<?php if(isset($_GET['wiped'])): ?>
    <div style="background: rgba(63, 185, 80, 0.1); color: var(--accent-green); padding: 16px; border-radius: 8px; border: 1px solid var(--accent-green); margin-bottom: 24px;">
        <strong><i class="fa-solid fa-circle-check"></i> Success:</strong> All transaction and entry history has been wiped. Your profile and categories were preserved.
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; align-items: start;">
    <div class="card">
        <h3 style="margin-bottom: 8px;"><i class="fa-solid fa-cloud-arrow-down" style="color: var(--accent-blue); margin-right: 8px;"></i> Export Data</h3>
        <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 24px;">Download a copy of your transaction history for use in Excel, Google Sheets, or secure cold storage.</p>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="<?= $basePath ?>/backups/<?= htmlspecialchars((string)$profile['id']) ?>/excel" class="btn primary" style="text-align: center; display: block; background: #217346; border-color: #217346; box-shadow: 0 0 10px rgba(33, 115, 70, 0.5);">
                <i class="fa-solid fa-file-excel" style="margin-right: 4px;"></i> Export Excel
            </a>
            <a href="<?= $basePath ?>/backups/<?= htmlspecialchars((string)$profile['id']) ?>/json" class="btn ghost" style="text-align: center; display: block; border: 1px solid var(--border);">
                <i class="fa-solid fa-file-code" style="margin-right: 4px;"></i> Download as JSON (Dev Backup)
            </a>
        </div>
    </div>

    <div class="card" style="border-color: rgba(248, 81, 73, 0.3);">
        <h3 style="margin-bottom: 8px; color: var(--accent-red);"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 4px;"></i> Danger Zone</h3>
        <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 24px;">This will permanently delete all your entries, transactions, and history. Your name and categories will remain.</p>
        
        <form action="<?= $basePath ?>/backups/<?= htmlspecialchars((string)$profile['id']) ?>/wipe" method="POST" id="wipe-form" onsubmit="return confirm('⚠️ Are you absolutely sure? This will permanently wipe your ledger history and cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            
            <button type="submit" id="wipe-btn" class="btn danger" style="width: 100%;">
                <i class="fa-solid fa-skull-crossbones" style="margin-right: 4px;"></i> Wipe Ledger History
            </button>
        </form>
    </div>
</div>