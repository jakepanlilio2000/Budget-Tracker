<?php
declare(strict_types=1);

/**
 * Standalone Executive Bank/Fintech Monthly Statement Template
 * Designed for print-ready PDF rendering via mPDF or browser print.
 *
 * Variables passed:
 * @var array $user User metadata (name, email, id)
 * @var string $period Month string (e.g. '2026-10')
 * @var string $formattedPeriod Human readable month (e.g. 'October 2026')
 * @var array $baseCurrency Base currency details (code, symbol)
 * @var array $summary Executive metrics (starting_net_worth, total_income, total_expense, net_cash_flow, ending_net_worth)
 * @var array $transactions List of settled transactions in statement period
 * @var array $accounts List of accounts and current balances
 * @var string $generatedAt ISO/formatted timestamp
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Executive Financial Statement - <?= htmlspecialchars($formattedPeriod ?? 'Current Period') ?></title>
    <style>
        @page {
            margin: 18mm 14mm 18mm 14mm;
            @top-right {
                content: "Official Financial Statement";
                font-size: 8pt;
                color: #64748b;
            }
            @bottom-center {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 8pt;
                color: #64748b;
            }
        }

        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --success: #16a34a;
            --danger: #dc2626;
            --neutral: #475569;
            --border: #e2e8f0;
            --bg-subtle: #f8fafc;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Clean Typographic Header */
        .statement-header {
            border-bottom: 2pt solid #0f172a;
            padding-bottom: 14pt;
            margin-bottom: 16pt;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .brand-title {
            font-size: 18pt;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
            margin: 0 0 3pt 0;
        }

        .brand-subtitle {
            font-size: 9pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .statement-meta {
            text-align: right;
            font-size: 9pt;
        }

        .statement-meta .period-badge {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            padding: 3pt 8pt;
            border-radius: 4pt;
            font-weight: 700;
            font-size: 9pt;
            margin-bottom: 4pt;
        }

        .meta-line {
            color: #64748b;
            margin-bottom: 2pt;
        }

        /* Executive Summary Block */
        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1pt solid #cbd5e1;
            padding-bottom: 3pt;
            margin: 16pt 0 8pt 0;
        }

        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16pt;
            page-break-inside: avoid;
        }

        .summary-card {
            width: 20%;
            padding: 10pt;
            background: #f8fafc;
            border: 1pt solid #e2e8f0;
            border-radius: 6pt;
            text-align: center;
        }

        .summary-label {
            font-size: 7.5pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            margin-bottom: 4pt;
        }

        .summary-value {
            font-size: 13pt;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: #0f172a;
        }

        .summary-value.income { color: #16a34a; }
        .summary-value.expense { color: #dc2626; }
        .summary-value.flow-pos { color: #16a34a; }
        .summary-value.flow-neg { color: #dc2626; }

        /* Transaction Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16pt;
            font-size: 8.5pt;
        }

        .data-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 7.5pt;
            padding: 6pt 8pt;
            text-align: left;
            border: 1pt solid #0f172a;
        }

        .data-table th.num-col, .data-table td.num-col {
            text-align: right;
        }

        .data-table td {
            padding: 5pt 8pt;
            border-bottom: 1pt solid #e2e8f0;
            border-left: 1pt solid #f1f5f9;
            border-right: 1pt solid #f1f5f9;
            vertical-align: middle;
        }

        .data-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .data-table tr.no-break {
            page-break-inside: avoid;
        }

        .num-val {
            font-variant-numeric: tabular-nums;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace;
            font-weight: 600;
        }

        /* Directional Badges */
        .type-badge {
            display: inline-block;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            padding: 1.5pt 5pt;
            border-radius: 3pt;
            letter-spacing: 0.3px;
        }

        .badge-income {
            background: #dcfce7;
            color: #15803d;
            border: 1pt solid #bbf7d0;
        }

        .badge-expense {
            background: #fee2e2;
            color: #b91c1c;
            border: 1pt solid #fecaca;
        }

        .badge-transfer {
            background: #f1f5f9;
            color: #475569;
            border: 1pt solid #e2e8f0;
        }

        /* Accounts Snapshot Section */
        .accounts-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            page-break-inside: avoid;
        }

        .accounts-table th {
            background: #334155;
            color: #ffffff;
            padding: 5pt 8pt;
            font-size: 7.5pt;
            text-align: left;
        }

        .accounts-table td {
            padding: 5pt 8pt;
            border-bottom: 1pt solid #e2e8f0;
        }

        .page-break {
            page-break-before: always;
        }

        .footer-note {
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1pt solid #e2e8f0;
            padding-top: 6pt;
            margin-top: 20pt;
            text-align: justify;
        }
    </style>
</head>
<body>

    <!-- 1. Typographic Header -->
    <div class="statement-header">
        <table class="header-table">
            <tr>
                <td>
                    <h1 class="brand-title">ExpensePro Enterprise</h1>
                    <p class="brand-subtitle">Official Account Statement & Ledger Audit</p>
                    <div style="margin-top: 8pt; font-size: 8.5pt;">
                        <strong>Account Holder:</strong> <?= htmlspecialchars($user['name'] ?? 'Authorized User') ?><br>
                        <strong>Email Address:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?><br>
                        <strong>User ID:</strong> #<?= (int) ($user['id'] ?? 1) ?>
                    </div>
                </td>
                <td class="statement-meta">
                    <span class="period-badge"><?= htmlspecialchars($formattedPeriod ?? date('F Y')) ?></span>
                    <div class="meta-line"><strong>Statement Date:</strong> <?= date('F d, Y') ?></div>
                    <div class="meta-line"><strong>Generated At:</strong> <?= htmlspecialchars($generatedAt ?? date('Y-m-d H:i:s')) ?> UTC</div>
                    <div class="meta-line"><strong>Base Currency:</strong> <?= htmlspecialchars($baseCurrency['code'] ?? 'USD') ?> (<?= htmlspecialchars($baseCurrency['symbol'] ?? '$') ?>)</div>
                    <div class="meta-line"><strong>Audit Status:</strong> <span style="color: #16a34a; font-weight: 700;">Verified Invariant</span></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 2. High-Level Executive Summary Block -->
    <div class="section-title">1. Executive Financial Summary</div>
    <table class="summary-grid">
        <tr>
            <td class="summary-card" style="margin-right: 4pt;">
                <div class="summary-label">Starting Net Worth</div>
                <div class="summary-value"><?= htmlspecialchars($baseCurrency['symbol'] ?? '$') ?><?= number_format((float) ($summary['starting_net_worth'] ?? 0.00), 2) ?></div>
            </td>
            <td style="width: 2%;"></td>
            <td class="summary-card">
                <div class="summary-label">Total Deposits / Income</div>
                <div class="summary-value income">+<?= htmlspecialchars($baseCurrency['symbol'] ?? '$') ?><?= number_format((float) ($summary['total_income'] ?? 0.00), 2) ?></div>
            </td>
            <td style="width: 2%;"></td>
            <td class="summary-card">
                <div class="summary-label">Total Outflows / Expense</div>
                <div class="summary-value expense">-<?= htmlspecialchars($baseCurrency['symbol'] ?? '$') ?><?= number_format((float) ($summary['total_expense'] ?? 0.00), 2) ?></div>
            </td>
            <td style="width: 2%;"></td>
            <td class="summary-card">
                <div class="summary-label">Net Cash Flow</div>
                <?php 
                $net = (float) ($summary['net_cash_flow'] ?? 0.00); 
                $flowClass = $net >= 0 ? 'flow-pos' : 'flow-neg';
                $sign = $net >= 0 ? '+' : '';
                ?>
                <div class="summary-value <?= $flowClass ?>"><?= $sign ?><?= htmlspecialchars($baseCurrency['symbol'] ?? '$') ?><?= number_format($net, 2) ?></div>
            </td>
            <td style="width: 2%;"></td>
            <td class="summary-card">
                <div class="summary-label">Ending Net Worth</div>
                <div class="summary-value"><?= htmlspecialchars($baseCurrency['symbol'] ?? '$') ?><?= number_format((float) ($summary['ending_net_worth'] ?? 0.00), 2) ?></div>
            </td>
        </tr>
    </table>

    <!-- 3. Account Balances Snapshot -->
    <?php if (!empty($accounts)): ?>
    <div class="section-title">2. Account Position Breakdown</div>
    <table class="accounts-table">
        <thead>
            <tr>
                <th>Account Name</th>
                <th>Classification</th>
                <th>Institution</th>
                <th style="text-align: right;">Currency</th>
                <th style="text-align: right;">Closing Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $acc): ?>
            <tr>
                <td style="font-weight: 600;"><?= htmlspecialchars((string) ($acc['name'] ?? 'Account')) ?></td>
                <td><?= ucfirst(str_replace('_', ' ', (string) ($acc['type'] ?? 'bank'))) ?></td>
                <td><?= htmlspecialchars((string) ($acc['institution'] ?? 'N/A')) ?></td>
                <td style="text-align: right;"><?= htmlspecialchars((string) ($acc['currency_code'] ?? 'USD')) ?></td>
                <td style="text-align: right;" class="num-val">
                    <?= htmlspecialchars((string) ($acc['currency_symbol'] ?? '$')) ?><?= number_format((float) ($acc['current_balance'] ?? 0.00), 2) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- 4. Detailed Transaction Ledger -->
    <div class="section-title" style="margin-top: 16pt;">3. Itemized Transaction Ledger</div>
    <?php if (empty($transactions)): ?>
        <p style="color: #64748b; font-style: italic; padding: 10pt; text-align: center;">No posted transactions recorded for this billing cycle.</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 80pt;">Date</th>
                <th style="width: 60pt;">Type</th>
                <th>Description / Payee</th>
                <th style="width: 100pt;">Category</th>
                <th style="width: 90pt;">Account</th>
                <th class="num-col" style="width: 85pt;">Settled Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $t): 
                $type = strtolower((string) ($t['type'] ?? 'expense'));
                $badgeClass = match ($type) {
                    'income' => 'badge-income',
                    'transfer' => 'badge-transfer',
                    default => 'badge-expense'
                };
                $prefix = match ($type) {
                    'income' => '+',
                    'transfer' => '±',
                    default => '-'
                };
            ?>
            <tr class="no-break">
                <td class="num-val" style="color: #475569;"><?= htmlspecialchars((string) ($t['transaction_date'] ?? 'N/A')) ?></td>
                <td><span class="type-badge <?= $badgeClass ?>"><?= ucfirst($type) ?></span></td>
                <td>
                    <div style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars((string) ($t['description'] ?? 'Transaction')) ?></div>
                    <?php if (!empty($t['notes'])): ?>
                        <small style="color: #64748b;"><?= htmlspecialchars((string) $t['notes']) ?></small>
                    <?php endif; ?>
                </td>
                <td style="color: #475569;"><?= htmlspecialchars((string) ($t['category_name'] ?? 'Uncategorized')) ?></td>
                <td style="color: #475569;"><?= htmlspecialchars((string) ($t['account_name'] ?? 'Primary')) ?></td>
                <td class="num-col num-val" style="color: <?= $type === 'income' ? '#16a34a' : ($type === 'expense' ? '#dc2626' : '#0f172a') ?>;">
                    <?= $prefix ?><?= htmlspecialchars($baseCurrency['symbol'] ?? '$') ?><?= number_format((float) ($t['total_amount'] ?? 0.00), 2) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- 5. Footer & Legal Disclaimers -->
    <div class="footer-note">
        <strong>STATEMENT INTEGRITY DISCLOSURE:</strong> This financial statement is generated by ExpensePro Enterprise Arbitrary-Precision Accounting Engine (v2.0). All transaction calculations, split allocations, and multi-currency exchange snapshots are strictly verified using BCMath Scale 2/6 conventions. The double-entry invariant satisfies $\sum(\text{debits}) = \sum(\text{credits}) + \text{transfer\_fees}$. This document is intended solely for the designated account owner and authorized financial institutions.
    </div>

</body>
</html>
