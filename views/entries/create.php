<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <h1>Create Entry</h1>
</header>
<div class="card" style="max-width: 600px;">
    <form action="<?= $basePath ?>/entries/<?= $profile_id ?>/store" method="POST">
        <?php include "views/partials/entry_form.php"; ?>
    </form>
</div>