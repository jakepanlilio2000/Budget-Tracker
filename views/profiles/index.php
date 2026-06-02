<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <div class="top-bar-left">
        <h1>Your Profiles</h1>
        <p style="color: var(--text-secondary);">Select a budget profile to continue.</p>
    </div>
</header>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
    <?php foreach ($profiles as $p): ?>
        <a href="<?= $basePath ?>/dashboard/<?= $p['id'] ?>" class="card" style="display: block; position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: <?= htmlspecialchars($p['color']) ?>;"></div>
            <h3 style="margin-top: 12px; font-size: 20px; color: var(--text-primary);"><?= htmlspecialchars($p['name']) ?></h3>
            
            <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; color: var(--text-secondary); font-size: 14px;">
                    <span>Schedule</span>
                    <span style="color: var(--text-primary);"><?= ucwords(str_replace('_', ' ', $p['pay_schedule'])) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; color: var(--text-secondary); font-size: 14px;">
                    <span>Base Income</span>
                    <span class="amount" style="color: var(--accent-green);"><?= $p['currency'] ?> <?= number_format((float)$p['base_income'], 2) ?></span>
                </div>
            </div>
        </a>
    <?php endforeach; ?>

    <a href="<?= $basePath ?>/profile/create" class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed var(--border); background: transparent; min-height: 140px; color: var(--text-secondary);">
        <span style="font-size: 32px; margin-bottom: 8px;">+</span>
        <span>Create New Profile</span>
    </a>
</div>