<div class="summary-grid" id="summary-cards">
    <div class="card summary-card">
        <span>💰 Total Inflow</span>
        <h3 class="amount inflow" data-value="<?= $summary['total_inflow'] ?>">
            <?= $profile['currency'] ?> <span id="summary-inflow"><?= number_format((float)$summary['total_inflow'], 2) ?></span>
        </h3>
    </div>
    <div class="card summary-card">
        <span>💸 Total Outflow</span>
        <h3 class="amount outflow" data-value="<?= $summary['total_outflow'] ?>">
            <?= $profile['currency'] ?> <span id="summary-outflow"><?= number_format((float)$summary['total_outflow'], 2) ?></span>
        </h3>
    </div>
    <div class="card summary-card <?= $summary['net'] >= 0 ? 'positive' : 'negative' ?>">
        <span>📈 Net Savings</span>
        <h3 class="amount" data-value="<?= $summary['net'] ?>">
            <?= $summary['net'] >= 0 ? '+' : '' ?><?= $profile['currency'] ?> <span id="summary-net"><?= number_format((float)$summary['net'], 2) ?></span>
        </h3>
    </div>
    <div class="card summary-card">
        <span>🏦 Cumulative</span>
        <h3 class="amount" data-value="<?= $summary['cumulative'] ?>">
            <?= $profile['currency'] ?> <span id="summary-cum"><?= number_format((float)$summary['cumulative'], 2) ?></span>
        </h3>
    </div>
</div>