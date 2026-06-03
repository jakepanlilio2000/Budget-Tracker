<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>🎨 App Preferences</h1>
        <p style="color: var(--text-secondary);">Customize your tracking experience. Saved securely to the database.</p>
    </div>
</header>

<script>
    document.documentElement.classList.remove('privacy-mode', 'no-anim-mode', 'compact-mode', 'zen-mode');
    <?php if ($profile['pref_privacy']): ?> document.documentElement.classList.add('privacy-mode'); <?php endif; ?>
    <?php if ($profile['pref_animations']): ?> document.documentElement.classList.add('no-anim-mode'); <?php endif; ?>
    <?php if ($profile['pref_compact']): ?> document.documentElement.classList.add('compact-mode'); <?php endif; ?>
    <?php if ($profile['pref_zen']): ?> document.documentElement.classList.add('zen-mode'); <?php endif; ?>
</script>

<div class="card" style="max-width: 600px; padding: 0; overflow: hidden;">
    
    <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0 0 4px 0;">Privacy Blur</h4>
            <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">Blurs all financial numbers until you hover over them.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-privacy" <?= !empty($profile['pref_privacy']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0 0 4px 0;">Disable Animations</h4>
            <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">Turns off the "counting up" effect for instant loading.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-animations" <?= !empty($profile['pref_animations']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0 0 4px 0;">Compact Density</h4>
            <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">Reduces padding to fit more data on your screen.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-compact" <?= !empty($profile['pref_compact']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div style="padding: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0 0 4px 0;">Zen Mode</h4>
            <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">Hides all graphical charts, leaving only raw ledgers.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-zen" <?= !empty($profile['pref_zen']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>

</div>

<style>
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--bg-primary); border: 1px solid var(--border); transition: .3s; border-radius: 24px; }
.toggle-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: var(--text-secondary); transition: .3s; border-radius: 50%; }
input:checked + .toggle-slider { background-color: var(--accent-blue); border-color: var(--accent-blue); }
input:checked + .toggle-slider:before { transform: translateX(20px); background-color: white; }
</style>

<script>
// We use an Immediately Invoked Function Expression (IIFE) instead of DOMContentLoaded
// so that the SPA Turbo Engine executes this instantly upon navigation.
(function initPreferences() {
    ['privacy', 'animations', 'compact', 'zen'].forEach(pref => {
        const toggle = document.getElementById(`pref-${pref}`);
        const dbKey = `pref_${pref}`;
        const className = pref === 'animations' ? 'no-anim-mode' : `${pref}-mode`;
        
        if(toggle) {
            toggle.addEventListener('change', async (e) => {
                const isChecked = e.target.checked;
                
                // 1. Instantly update UI for the SPA feel
                if(isChecked) document.documentElement.classList.add(className);
                else document.documentElement.classList.remove(className);
                
                // 2. Send the update to the Database seamlessly
                const formData = new FormData();
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
                formData.append('key', dbKey);
                formData.append('state', isChecked ? '1' : '0'); // Strict integers
                
                try {
                    const res = await fetch(`<?= $basePath ?>/preferences/<?= $profile['id'] ?>/toggle`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    
                    if(data.success) {
                        if (typeof showToast === 'function') showToast('Preference saved to database');
                    } else {
                        throw new Error('DB Update Failed');
                    }
                } catch(err) {
                    console.error('Preference Sync Error:', err);
                    if (typeof showToast === 'function') showToast('Failed to sync. Reverting.', 'error');
                    
                    // Revert the toggle and UI if the network/database fails
                    toggle.checked = !isChecked; 
                    if(!isChecked) document.documentElement.classList.add(className);
                    else document.documentElement.classList.remove(className);
                }
            });
        }
    });
})();
</script>