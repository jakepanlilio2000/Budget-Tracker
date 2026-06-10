<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <h1><i class="fa-solid fa-pen-to-square" style="color: var(--accent-blue); margin-right: 8px;"></i> Edit Entry: <?= htmlspecialchars($entry['name'] ?? '') ?></h1>
</header>
<div class="card" style="max-width: 600px;">
    <form action="<?= $basePath ?>/entries/<?= htmlspecialchars((string)($entry['id'] ?? '')) ?>/update" method="POST">
        <?php include "views/partials/entry_form.php"; ?>
    </form>
</div>