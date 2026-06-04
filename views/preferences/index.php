<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <div class="top-bar-left">
        <h1>🎨 App Preferences</h1>
        <p style="color: var(--text-secondary);">Customize your configuration parameters securely attached to database assets.</p>
    </div>
</header>

<div class="card" data-pid="<?= $profile['id'] ?>" style="max-width: 600px; padding: 0; overflow: hidden;">
    <!-- Standardized to use premium .toggle-switch container blocks -->
    <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin:0 0 4px 0;">Privacy Blur</h4>
            <p style="margin:0; font-size:12px; color:var(--text-secondary);">Blurs numbers until hovered over.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-privacy" <?= !empty($profile['pref_privacy']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin:0 0 4px 0;">Disable Animations</h4>
            <p style="margin:0; font-size:12px; color:var(--text-secondary);">Turns off the "counting up" effect for instant loading.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-animations" <?= !empty($profile['pref_animations']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin:0 0 4px 0;">Compact Density</h4>
            <p style="margin:0; font-size:12px; color:var(--text-secondary);">Reduces padding spacing sizes parameters.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-compact" <?= !empty($profile['pref_compact']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>

    <div style="padding: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin:0 0 4px 0;">Zen Mode</h4>
            <p style="margin:0; font-size:12px; color:var(--text-secondary);">Hides charts completely.</p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="pref-zen" <?= !empty($profile['pref_zen']) ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>
</div>