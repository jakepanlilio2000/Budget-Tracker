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
    
    <link rel="stylesheet" href="<?= $basePath ?>/public/css/app.css">

    
    
<script>
        const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";
        const BASE_PATH = "<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?>";
    </script>
</head>
<style>
        /* Global Preference Overrides */
        body.privacy-mode .amount { filter: blur(6px); transition: filter 0.2s ease; cursor: crosshair; }
        body.privacy-mode .amount:hover { filter: blur(0); }
    </style>
    <script>
        // Apply classes instantly before the page renders to prevent flashing
        if(localStorage.getItem('pref_privacy') === 'true') {
            document.documentElement.classList.add('privacy-mode');
        }
    </script>
</head>
<body>
    <div class="app-container">