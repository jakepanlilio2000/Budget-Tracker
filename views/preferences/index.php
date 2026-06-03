<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<header class="top-bar">
    <div class="top-bar-left">
        <h1>🎨 App Preferences</h1>
        <p style="color: var(--text-secondary);">Customize your tracking experience. Saved locally to your browser.</p>
    </div>
</header>

<div class="card" style="max-width: 600px; padding: 0; overflow: hidden;">
    
    <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0 0 4px 0;">Privacy Blur</h4>
            <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">Blurs all financial numbers until you hover over them. Great for public spaces or taking screenshots.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-privacy">
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0 0 4px 0;">Disable Count Animations</h4>
            <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">Turns off the "counting up" effect on the dashboard summary cards for instant loading.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-animations">
            <span class="toggle-slider"></span>
        </label>
    </div>

</div>

<style>
/* Modern Toggle Switch CSS */
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--bg-primary); border: 1px solid var(--border); transition: .3s; border-radius: 24px; }
.toggle-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: var(--text-secondary); transition: .3s; border-radius: 50%; }
input:checked + .toggle-slider { background-color: var(--accent-blue); border-color: var(--accent-blue); }
input:checked + .toggle-slider:before { transform: translateX(20px); background-color: white; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const privacyToggle = document.getElementById('pref-privacy');
    const animToggle = document.getElementById('pref-animations');

    // Load saved preferences
    privacyToggle.checked = localStorage.getItem('pref_privacy') === 'true';
    animToggle.checked = localStorage.getItem('pref_no_anim') === 'true';

    // Save & Apply Privacy Blur
    privacyToggle.addEventListener('change', (e) => {
        localStorage.setItem('pref_privacy', e.target.checked);
        if(e.target.checked) document.body.classList.add('privacy-mode');
        else document.body.classList.remove('privacy-mode');
        showToast('Privacy preference saved');
    });

    // Save Animation Preference
    animToggle.addEventListener('change', (e) => {
        localStorage.setItem('pref_no_anim', e.target.checked);
        showToast('Animation preference saved');
    });
});
</script>