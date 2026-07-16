<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Preferences & Personalization';
ob_start();
$prefs = $preferences;
?>
<div class="page-header">
    <h1>Preferences</h1>
    <p class="text-secondary">Customize your experience, privacy, and interface.</p>
</div>

<form method="POST" action="<?= url('/preferences/save') ?>" class="form-stack">
    <?= \App\Core\CSRF::field() ?>

    <!-- Appearance -->
    <div class="card glass">
        <h3><i class="fas fa-palette"></i> Appearance</h3>
        <div class="grid grid-2 mt-3" style="gap: 1.5rem;">
            <div class="form-group">
                <label>Theme</label>
                <select name="theme" style="width: 100%;">
                    <option value="auto" <?= $prefs['theme'] === 'auto' ? 'selected' : '' ?>>Auto (Follow System)</option>
                    <option value="light" <?= $prefs['theme'] === 'light' ? 'selected' : '' ?>>Light</option>
                    <option value="dark" <?= $prefs['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                </select>
            </div>
            <div class="form-group">
                <label>Accent Color</label>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <input type="color" name="accent_color" value="<?= e($prefs['accent_color']) ?>"
                        style="height: 40px; width: 60px; padding: 2px; border-radius: 8px; border: 1px solid var(--border-color); cursor: pointer;">
                    <span class="text-secondary"
                        style="font-size: 0.85rem; font-family: monospace;"><?= e($prefs['accent_color']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy & Focus -->
    <div class="card glass">
        <h3><i class="fas fa-eye-slash"></i> Privacy & Focus</h3>
        <div class="mt-3" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="form-group" style="margin: 0;">
                <label class="toggle-label">
                    <label class="toggle-switch">
                        <input type="checkbox" name="privacy_blur" value="1" <?= $prefs['privacy_blur'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span>Privacy Blur</span>
                </label>
                <small class="text-secondary"
                    style="margin-left: 3.5rem; display: block; margin-top: 0.25rem;">Temporarily hide sensitive
                    balances, charts, and financial data.</small>
            </div>

            <div class="form-group" style="margin: 0; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <label class="toggle-label">
                    <label class="toggle-switch">
                        <input type="checkbox" name="zen_mode" value="1" <?= $prefs['zen_mode'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span>Zen Mode</span>
                </label>
                <small class="text-secondary" style="margin-left: 3.5rem; display: block; margin-top: 0.25rem;">Hide
                    sidebars and decorative elements for a distraction-free workspace.</small>
            </div>

            <div class="form-group" style="margin: 0; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <label class="toggle-label">
                    <label class="toggle-switch">
                        <input type="checkbox" name="compact_mode" value="1" <?= $prefs['compact_mode'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span>Compact Mode</span>
                </label>
                <small class="text-secondary" style="margin-left: 3.5rem; display: block; margin-top: 0.25rem;">Reduce
                    padding and font sizes to fit more data on the screen.</small>
            </div>
        </div>
    </div>

    <!-- Financial Settings -->
    <div class="card glass">
        <h3><i class="fas fa-coins"></i> Financial Settings</h3>
        <div class="form-group mt-3">
            <label>Base Currency</label>
            <select name="base_currency_id" style="width: 100%;">
                <?php foreach (\App\Models\Currency::getAll() as $curr): ?>
                    <option value="<?= $curr['id'] ?>" <?= ($prefs['base_currency_id'] ?? 0) == $curr['id'] ? 'selected' : '' ?>>
                        <?= e($curr['code']) ?> - <?= e($curr['name']) ?> (<?= e($curr['symbol']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-secondary" style="display: block; margin-top: 0.5rem;">This currency will be used
                consistently across all dashboards, reports, and calculations.</small>
        </div>
    </div>

    <!-- Navigation -->
    <div class="card glass">
        <h3><i class="fas fa-compass"></i> Navigation</h3>
        <div class="form-group mt-3">
            <label>Default Landing Page</label>
            <select name="default_landing_page" style="width: 100%;">
                <?php
                $currentLanding = $prefs['default_landing_page'] ?? '/dashboard';
                foreach (getAvailableLandingPages() as $route => $name):
                    ?>
                    <option value="<?= e($route) ?>" <?= $currentLanding === $route ? 'selected' : '' ?>>
                        <?= e($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-secondary" style="display: block; margin-top: 0.5rem;">Choose which module opens
                immediately after you log in or visit the home URL.</small>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; max-width: 300px; margin-top: 1rem;">
        <i class="fas fa-save"></i> Save Preferences
    </button>
</form>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>