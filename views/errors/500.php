<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
    <link rel="stylesheet" href="<?= $basePath ?>/public/css/app.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center;">
    <div class="card summary-card" style="padding: 40px; max-width: 400px;">
        <h1 style="color: var(--accent-yellow); font-size: 48px; margin-bottom: 16px;">500</h1>
        <h2 style="margin-bottom: 16px;">Server Error</h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">Something went wrong on our end. Please try again later.</p>
        <a href="/expenses" class="btn primary">Return to Dashboard</a>
    </div>
</body>
</html>