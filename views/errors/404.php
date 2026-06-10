<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<div style="display: flex; align-items: center; justify-content: center; min-height: 80vh; animation: fadeUp 0.4s ease-out;">
    <div class="card" style="text-align: center; max-width: 500px; padding: 48px 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
        <div style="width: 80px; height: 80px; background: rgba(88, 166, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px auto;">
            <i class="fa-solid fa-route" style="font-size: 36px; color: var(--accent-blue);"></i>
        </div>
        
        <h1 style="font-size: 32px; margin-bottom: 16px; font-weight: 800;">404 - Node Not Found</h1>
        <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 32px; font-size: 15px;">
            The financial ledger, system route, or specific data node you are trying to access does not exist or has been moved.
        </p>
        
        <a href="<?= $basePath ?>/" class="btn primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; font-size: 15px;">
            <i class="fa-solid fa-house"></i> Return to Global Portfolio
        </a>
    </div>
</div>

<style>
@keyframes fadeUp {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
</style>