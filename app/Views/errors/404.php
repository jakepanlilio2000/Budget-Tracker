<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
    <div class="glass-card" style="text-align: center; max-width: 400px; width: 100%;">
        <h1 style="font-size: 4rem; color: var(--accent); margin: 0;">404</h1>
        <h2>Page Not Found</h2>
        <p class="text-secondary">The page you are looking for does not exist or has been moved.</p>
        <a href="<?= url('/dashboard') ?>" class="btn btn-primary" style="margin-top: 1rem; display: inline-block; text-decoration: none;">Return to Dashboard</a>
    </div>
</body>
</html>