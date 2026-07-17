<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'Authentication';

// Check for a theme cookie, default to 'system'
$theme = $_COOKIE['theme_preference'] ?? 'system';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Expense Tracker</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="<?= asset('favicon.ico') ?>" sizes="any">
    <script>
        // Instant theme application for auth pages to prevent FOUC (Flash of Unstyled Content)
        (function () {
            let theme = '<?= e($theme) ?>';
            if (theme === 'system') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>

<body class="auth-body">
    <!-- Auth Theme Toggle -->
    <button class="btn-icon auth-theme-toggle" id="auth-theme-toggle" title="Toggle Theme" aria-label="Toggle Theme"
        style="position: absolute; top: 1.5rem; right: 1.5rem; z-index: 10; background: var(--bg-glass); border: 1px solid var(--border-color); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-primary);">
        <i class="fas fa-desktop"></i>
    </button>

    <div class="auth-container">
        <div class="auth-card glass">
            <div class="auth-header">
                <h1><i class="fas fa-wallet"></i> ExpensePro</h1>
                <p>Enterprise Expense Management</p>
            </div>
            <?= $content ?? '' ?>
        </div>
    </div>
    <script src="<?= asset('js/app.js') ?>"></script>
</body>

</html>