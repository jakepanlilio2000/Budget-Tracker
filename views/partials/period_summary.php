<div class="summary-grid" id="summary-cards">
    <div class="card summary-card inflow-card">
        <span>💰 Total Inflow</span>
        <h3 class="amount inflow">
            <?= $profile['currency'] ?> <span id="summary-inflow" data-full-val="<?= $summary['total_inflow'] ?>"><?= number_format((float)$summary['total_inflow'], 2) ?></span>
        </h3>
    </div>
    <div class="card summary-card outflow-card">
        <span>💸 Total Outflow</span>
        <h3 class="amount outflow">
            <?= $profile['currency'] ?> <span id="summary-outflow" data-full-val="<?= $summary['total_outflow'] ?>"><?= number_format((float)$summary['total_outflow'], 2) ?></span>
        </h3>
    </div>
    <div class="card summary-card <?= $summary['net'] >= 0 ? 'positive' : 'negative' ?>">
        <span>📈 Net Savings</span>
        <h3 class="amount">
            <span id="summary-sign"><?= $summary['net'] >= 0 ? '+' : '' ?></span><?= $profile['currency'] ?> <span id="summary-net" data-full-val="<?= $summary['net'] ?>"><?= number_format(abs((float)$summary['net']), 2) ?></span>
        </h3>
    </div>
    <div class="card summary-card cumulative-card">
        <span>🏦 Cumulative</span>
        <h3 class="amount">
            <?= $profile['currency'] ?> <span id="summary-cum" data-full-val="<?= $summary['cumulative'] ?>"><?= number_format((float)$summary['cumulative'], 2) ?></span>
        </h3>
    </div>
</div>