<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<div class="budget-table-container" id="budget-table">
    <?php foreach (['inflow', 'outflow'] as $type): ?>
        <?php if (!empty($transactions[$type])): ?>
            <?php foreach ($transactions[$type] as $cat_id => $category): ?>
                <div class="category-section">
                    <div class="category-header toggle-collapse">
                        <h4><?= htmlspecialchars($category['name']) ?></h4>
                        <i>▼</i>
                    </div>
                    <div class="category-rows">
                        <?php 
                        $cat_total = 0;
                        foreach ($category['items'] as $tx): 
                            if ($tx['is_checked']) $cat_total += $tx['amount'];
                        ?>
                        <div class="tx-row <?= $tx['is_checked'] ? '' : 'unchecked' ?>" data-id="<?= $tx['id'] ?>">
                            <label class="checkbox-container">
                                <input type="checkbox" class="tx-check" <?= $tx['is_checked'] ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                            </label>
                            <span class="tx-name"><?= htmlspecialchars($tx['name']) ?></span>
                            <span class="tx-amount <?= $type ?>" data-full-val="<?= $tx['amount'] ?>">
                                <?= $profile['currency'] ?> <span class="editable-amount"><?= number_format((float)$tx['amount'], 2) ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <div class="category-footer">
                            <span>Subtotal</span>
                            <span class="amount <?= $type ?> cat-subtotal" data-full-val="<?= $cat_total ?>">
                                <?= $profile['currency'] ?> <span><?= number_format($cat_total, 2) ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>