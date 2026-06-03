<?php
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fintech Budget Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="<?= $basePath ?>/public/css/app.css">

    <script>
        const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";
        const BASE_PATH = "<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?>";
    </script>
</head>
    <?php
    $appPrefs = ['privacy' => 0, 'anim' => 0, 'compact' => 0, 'zen' => 0];
    
    if (isset($_SESSION['user_id'])) {
        $db = \config\Database::getInstance();
        $pid = $profile['id'] ?? null;
        
        if ($pid) {
            $prefRow = $db->query("SELECT pref_privacy, pref_animations, pref_compact, pref_zen FROM profiles WHERE id = " . (int)$pid)->fetch();
        } else {
            $prefRow = $db->query("SELECT pref_privacy, pref_animations, pref_compact, pref_zen FROM profiles WHERE user_id = " . (int)$_SESSION['user_id'] . " LIMIT 1")->fetch();
        }

        if ($prefRow) {
            $appPrefs = [
                'privacy' => (int)$prefRow['pref_privacy'],
                'anim' => (int)$prefRow['pref_animations'],
                'compact' => (int)$prefRow['pref_compact'],
                'zen' => (int)$prefRow['pref_zen']
            ];
        }
    }
    ?>
    <style>
        html.privacy-mode .amount { filter: blur(6px) !important; transition: filter 0.2s ease; cursor: crosshair; }
        html.privacy-mode .amount:hover { filter: blur(0) !important; }
        html.compact-mode .card { padding: 12px 16px !important; }
        html.compact-mode td, html.compact-mode th { padding: 8px 12px !important; }
        html.compact-mode .summary-card h3 { font-size: 24px !important; }
        html.zen-mode canvas { display: none !important; }
        html.zen-mode .chart-container { display: none !important; }
    </style>
    <script>
        document.documentElement.classList.remove('privacy-mode', 'no-anim-mode', 'compact-mode', 'zen-mode');
        <?php if ($appPrefs['privacy']): ?> document.documentElement.classList.add('privacy-mode'); <?php endif; ?>
        <?php if ($appPrefs['anim']): ?> document.documentElement.classList.add('no-anim-mode'); <?php endif; ?>
        <?php if ($appPrefs['compact']): ?> document.documentElement.classList.add('compact-mode'); <?php endif; ?>
        <?php if ($appPrefs['zen']): ?> document.documentElement.classList.add('zen-mode'); <?php endif; ?>
    </script>
</head>
<body>
    <div class="app-container">