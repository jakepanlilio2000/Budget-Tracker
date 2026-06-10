<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<div style="display: flex; align-items: center; justify-content: center; min-height: 80vh; animation: fadeUp 0.4s ease-out;">
    <div class="card" style="text-align: center; max-width: 600px; padding: 48px 32px; border-top: 4px solid var(--accent-red); box-shadow: 0 20px 40px rgba(248, 81, 73, 0.1);">
        
        <div style="width: 80px; height: 80px; background: rgba(248, 81, 73, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px auto;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 36px; color: var(--accent-red);"></i>
        </div>
        
        <h1 style="font-size: 32px; margin-bottom: 16px; font-weight: 800;">500 - System Exception</h1>
        <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 24px; font-size: 15px;">
            A critical logic or database error occurred while the server was attempting to process your transaction request.
        </p>
        
        <?php if (!empty($errorMessage)): ?>
            <div style="background: rgba(22, 27, 34, 0.8); border: 1px solid rgba(248, 81, 73, 0.3); border-left: 4px solid var(--accent-red); padding: 16px; border-radius: 8px; font-family: 'JetBrains Mono', monospace; font-size: 12px; text-align: left; overflow-x: auto; margin-bottom: 32px; color: var(--text-secondary);">
                <strong style="color: var(--accent-red); display: block; margin-bottom: 8px;">FATAL EXCEPTION TRACE:</strong>
                <?= nl2br(htmlspecialchars($errorMessage)) ?>
            </div>
        <?php endif; ?>
        
        <a href="<?= $basePath ?>/" class="btn ghost" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid var(--border); padding: 12px 24px; font-size: 15px;">
            <i class="fa-solid fa-arrow-left-long"></i> Retreat to Safety
        </a>
    </div>
</div>

<style>
@keyframes fadeUp {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
</style>