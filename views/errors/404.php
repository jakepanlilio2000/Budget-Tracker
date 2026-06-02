<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Not Found</title>
    <?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<link rel="stylesheet" href="<?= $basePath ?>/public/css/app.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center;">
    <div class="card summary-card" style="padding: 40px; max-width: 400px;">
        <h1 style="color: var(--accent-red); font-size: 48px; margin-bottom: 16px;">404</h1>
        <h2 style="margin-bottom: 16px;">Page Not Found</h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">The page you are looking for doesn't exist or has been moved.</p>
        <a href="/expenses" class="btn primary">Return to Dashboard</a>
    </div>
</body>
</html>