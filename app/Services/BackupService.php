<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class BackupService
{
    public const SCHEMA_VERSION = '1.0.0';
    public const APP_VERSION = '1.0.0';
    public const APP_NAME = 'Expense Tracker';

    public function generateComprehensiveData(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $data = [];
        $modulesIncluded = [];
        $baseCurrency = \App\Models\CurrencyService::getUserBaseCurrency($userId);

        $addModule = function (string $table, array $rows, array $summary = []) use (&$data, &$modulesIncluded) {
            if (!empty($rows)) {
                $data[$table] = ['summary' => $summary, 'records' => $rows];
                $modulesIncluded[] = $table;
            }
        };

        $stmt = $db->prepare("SELECT * FROM accounts WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $addModule('accounts', $rows, ['total_balance' => array_sum(array_column($rows, 'current_balance'))]);
        }

        $stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows)
            $addModule('categories', $rows);

        $stmt = $db->prepare("
            SELECT t.*, a.name as account_name, c.name as category_name, cur.symbol as currency_symbol, cur.code as currency_code
            FROM transactions t 
            LEFT JOIN accounts a ON t.account_id = a.id 
            LEFT JOIN categories c ON t.category_id = c.id 
            LEFT JOIN currencies cur ON t.currency_id = cur.id
            WHERE t.user_id = ? AND t.deleted_at IS NULL 
            ORDER BY t.transaction_date DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $totalIncome = array_sum(array_map(fn($r) => $r['type'] === 'income' ? (float) $r['total_amount'] : 0, $rows));
            $totalExpense = array_sum(array_map(fn($r) => $r['type'] === 'expense' ? (float) $r['total_amount'] : 0, $rows));
            $addModule('transactions', $rows, [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net' => $totalIncome - $totalExpense,
                'count' => count($rows)
            ]);
        }

        $stmt = $db->prepare("SELECT ts.*, c.name as category_name FROM transaction_splits ts LEFT JOIN categories c ON ts.category_id = c.id JOIN transactions t ON ts.transaction_id = t.id WHERE t.user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $addModule('transaction_splits', $rows, ['total_amount' => array_sum(array_column($rows, 'amount'))]);
        }

        $stmt = $db->prepare("SELECT * FROM budgets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $addModule('budgets', $rows, ['total_allocated' => array_sum(array_column($rows, 'amount'))]);
        }

        $stmt = $db->prepare("SELECT b.*, c.name as category_name FROM bills b LEFT JOIN categories c ON b.category_id = c.id WHERE b.user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $total = array_sum(array_column($rows, 'total_amount'));
            $paid = array_sum(array_map(fn($r) => $r['status'] === 'paid' ? (float) $r['total_amount'] : 0, $rows));
            $addModule('bills', $rows, ['total_amount' => $total, 'paid' => $paid, 'unpaid' => $total - $paid]);
        }

        $stmt = $db->prepare("SELECT * FROM bill_payments WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $addModule('bill_payments', $rows, ['total_paid' => array_sum(array_column($rows, 'amount'))]);
        }

        $stmt = $db->prepare("SELECT * FROM employers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows)
            $addModule('employers', $rows);

        $stmt = $db->prepare("SELECT s.*, e.company_name FROM salaries s JOIN employers e ON s.employer_id = e.id WHERE s.user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $addModule('salaries', $rows, [
                'total_net_pay' => array_sum(array_column($rows, 'net_pay')),
                'total_basic' => array_sum(array_column($rows, 'basic_salary'))
            ]);
        }

        $stmt = $db->prepare("SELECT * FROM savings_vaults WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $addModule('savings_vaults', $rows, [
                'total_target' => array_sum(array_column($rows, 'target_amount')),
                'total_current' => array_sum(array_column($rows, 'current_amount'))
            ]);
        }

        $stmt = $db->prepare("SELECT vt.*, sv.name as vault_name FROM vault_transactions vt JOIN savings_vaults sv ON vt.vault_id = sv.id WHERE vt.user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $addModule('vault_transactions', $rows, [
                'total_deposits' => array_sum(array_map(fn($r) => $r['type'] === 'deposit' ? (float) $r['amount'] : 0, $rows)),
                'total_withdrawals' => array_sum(array_map(fn($r) => $r['type'] === 'withdrawal' ? (float) $r['amount'] : 0, $rows))
            ]);
        }

        $stmt = $db->prepare("SELECT * FROM daily_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $mappedRows = array_map(function ($r) {
                $r['description'] = $r['description'] ?? $r['notes'] ?? $r['mood_context'] ?? 'N/A';
                $r['amount'] = (float) ($r['amount'] ?? $r['total_spent'] ?? 0);
                return $r;
            }, $rows);
            $addModule('daily_logs', $mappedRows, ['total_amount' => array_sum(array_column($mappedRows, 'amount'))]);
        }

        $stmt = $db->prepare("SELECT * FROM pending_ledger WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows) {
            $addModule('pending_ledger', $rows, ['total_pending' => array_sum(array_column($rows, 'amount'))]);
        }

        $simpleTables = ['timeline_events', 'recurring_incomes', 'forecast_scenarios', 'radar_alerts'];
        foreach ($simpleTables as $table) {
            $stmt = $db->prepare("SELECT * FROM `$table` WHERE user_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();
            if ($rows) {
                $summary = [];
                if ($table === 'recurring_incomes') {
                    $summary['total_estimated'] = array_sum(array_column($rows, 'amount'));
                }
                $addModule($table, $rows, $summary);
            }
        }

        foreach (['user_fxp_stats', 'user_mastery_stats', 'user_streaks'] as $table) {
            $stmt = $db->prepare("SELECT * FROM `$table` WHERE user_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();
            if ($rows)
                $addModule($table, $rows);
        }

        $stmt = $db->prepare("
            SELECT ua.*, ad.name as achievement_name, ad.icon, ad.color, ad.xp_value
            FROM user_achievements ua
            LEFT JOIN achievement_definitions ad ON ua.achievement_id = ad.id
            WHERE ua.user_id = ?
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows)
            $addModule('user_achievements', $rows);

        foreach (['planning_scenarios', 'planning_loans', 'planning_investments'] as $table) {
            $stmt = $db->prepare("SELECT * FROM `$table` WHERE user_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();
            if ($rows) {
                $summary = [];
                if ($table === 'planning_loans')
                    $summary['total_principal'] = array_sum(array_column($rows, 'principal'));
                if ($table === 'planning_investments')
                    $summary['total_initial'] = array_sum(array_column($rows, 'initial_investment'));
                $addModule($table, $rows, $summary);
            }
        }

        $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        if ($rows)
            $addModule('user_preferences', $rows);

        return ['data' => $data, 'modules' => $modulesIncluded, 'base_currency' => $baseCurrency];
    }

    public function generateBackup(int $userId, string $format = 'json'): array
    {
        $backupUuid = $this->generateUuid();
        $timestamp = date('Y-m-d H:i:s');
        $extracted = $this->generateComprehensiveData($userId);
        $summary = $this->generateFinancialSummary($userId);

        $payload = [
            'metadata' => [
                'app_name' => self::APP_NAME,
                'app_version' => self::APP_VERSION,
                'schema_version' => self::SCHEMA_VERSION,
                'backup_uuid' => $backupUuid,
                'export_timestamp' => $timestamp,
                'user_id' => $userId,
                'format' => $format,
                'base_currency' => $extracted['base_currency']
            ],
            'financial_summary' => $summary,
            'data' => $extracted['data']
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($jsonPayload === false) {
            throw new \Exception('Failed to encode backup data to JSON: ' . json_last_error_msg());
        }

        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir))
            @mkdir($tempDir, 0755, true);

        $tempFile = $tempDir . '/backup_' . bin2hex(random_bytes(8)) . '.json';
        file_put_contents($tempFile, $jsonPayload);

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);
        $filename = 'expense_backup_' . date('Y-m-d_His') . '.' . $format;

        $this->logBackupHistory($userId, $filename, $format, $fileSize, $checksum, $extracted['modules'], $backupUuid);

        return ['filepath' => $tempFile, 'filename' => $filename, 'checksum' => $checksum, 'uuid' => $backupUuid, 'modules' => $extracted['modules']];
    }

    public function generateZipCsv(int $userId): array
    {
        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir))
            @mkdir($tempDir, 0755, true);

        $zipFile = $tempDir . '/backup_' . bin2hex(random_bytes(8)) . '.zip';
        if (file_exists($zipFile))
            unlink($zipFile);

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Failed to create ZIP archive. Error: ' . $zip->getStatusString());
        }

        $extracted = $this->generateComprehensiveData($userId);
        $fmt = fn($val) => is_numeric($val) ? number_format((float) $val, 2, '.', '') : $val;

        $tempCsvFiles = [];

        foreach ($extracted['data'] as $table => $moduleData) {
            $rows = $moduleData['records'];
            $summary = $moduleData['summary'] ?? [];

            $csvFile = $tempDir . '/csv_' . bin2hex(random_bytes(4)) . '.csv';
            $tempCsvFiles[] = $csvFile;

            $fp = fopen($csvFile, 'w');
            if ($fp) {
                if (!empty($summary)) {
                    fputcsv($fp, ['--- MODULE SUMMARY ---'], ',', '"', '\\');
                    foreach ($summary as $key => $val) {
                        fputcsv($fp, [ucfirst(str_replace('_', ' ', $key)), $fmt($val)], ',', '"', '\\');
                    }
                    fputcsv($fp, [], ',', '"', '\\');
                }

                fputcsv($fp, array_keys($rows[0]), ',', '"', '\\');
                foreach ($rows as $row) {
                    fputcsv($fp, $row, ',', '"', '\\');
                }
                fclose($fp);

                $zip->addFile($csvFile, $table . '.csv');
            }
        }

        $summaryData = $this->generateFinancialSummary($userId);
        $summaryData['base_currency'] = $extracted['base_currency'];
        $zip->addFromString('financial_summary.json', json_encode($summaryData, JSON_PRETTY_PRINT));

        if ($zip->close() !== true) {
            throw new \Exception('Failed to close ZIP archive. Status: ' . $zip->getStatusString());
        }

        foreach ($tempCsvFiles as $file) {
            if (file_exists($file))
                unlink($file);
        }

        $fileSize = (int) filesize($zipFile);
        $checksum = hash_file('sha256', $zipFile);
        $filename = 'expense_backup_' . date('Y-m-d_His') . '.zip';
        $this->logBackupHistory($userId, $filename, 'zip', $fileSize, $checksum, $extracted['modules']);

        return ['filepath' => $zipFile, 'filename' => $filename, 'checksum' => $checksum];
    }

    public function generateXlsx(int $userId): array
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new \Exception('PhpSpreadsheet is required for XLSX exports.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $extracted = $this->generateComprehensiveData($userId);
        $baseCurrency = $extracted['base_currency'];
        $fmt = fn($val) => $baseCurrency['symbol'] . number_format((float) $val, 2);

        $summary = $this->generateFinancialSummary($userId);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Financial Summary');
        $sheet->fromArray([
            ['Expense Tracker - Comprehensive Financial Summary'],
            ['Generated:', date('Y-m-d H:i:s')],
            ['Base Currency:', $baseCurrency['code'] ?? 'USD'],
            [],
            ['Total Income', $fmt($summary['totals']['total_income'])],
            ['Total Expenses', $fmt($summary['totals']['total_expense'])],
            ['Net Income', $fmt($summary['totals']['net_income'])],
            ['Total Savings', $fmt($summary['totals']['total_savings'])],
            ['Goals Completed', $summary['totals']['goals_completed']],
            ['Health Score', $summary['health']['overall_score'] . '/100']
        ]);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5:A9')->getFont()->setBold(true);

        foreach ($extracted['data'] as $table => $moduleData) {
            $rows = $moduleData['records'];
            $summaryData = $moduleData['summary'] ?? [];

            $newSheet = $spreadsheet->createSheet();
            $newSheet->setTitle(substr($table, 0, 31));

            $rowNum = 1;
            if (!empty($summaryData)) {
                $newSheet->setCellValue('A' . $rowNum, 'MODULE SUMMARY');
                $newSheet->getStyle('A' . $rowNum)->getFont()->setBold(true);
                $rowNum++;
                foreach ($summaryData as $key => $val) {
                    $newSheet->setCellValue('A' . $rowNum, ucfirst(str_replace('_', ' ', $key)));
                    $newSheet->setCellValue('B' . $rowNum, is_numeric($val) ? $fmt($val) : $val);
                    $rowNum++;
                }
                $rowNum++;
            }

            $newSheet->fromArray($rows, null, 'A' . $rowNum);
            foreach (range('A', 'J') as $col) {
                $newSheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir))
            @mkdir($tempDir, 0755, true);
        $tempFile = $tempDir . '/xlsx_' . bin2hex(random_bytes(8)) . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);
        $filename = 'expense_backup_' . date('Y-m-d_His') . '.xlsx';
        $this->logBackupHistory($userId, $filename, 'xlsx', $fileSize, $checksum, $extracted['modules']);

        return ['filepath' => $tempFile, 'filename' => $filename, 'checksum' => $checksum];
    }

    public function generatePdf(int $userId): array
    {
        if (!class_exists('\Mpdf\Mpdf')) {
            throw new \Exception('mPDF is required for PDF exports.');
        }

        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir))
            @mkdir($tempDir, 0755, true);

        $extracted = $this->generateComprehensiveData($userId);
        $data = $extracted['data'];
        $summary = $this->generateFinancialSummary($userId);
        $baseCurrency = $extracted['base_currency'];
        $fmt = fn($val) => $baseCurrency['symbol'] . number_format((float) $val, 2);

        $html = '
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 11px; color: #334155; }
                h1 { color: #2563EB; font-size: 22px; margin-bottom: 5px; border-bottom: 2px solid #2563EB; padding-bottom: 8px; }
                h2 { color: #1E293B; font-size: 16px; margin-top: 25px; border-bottom: 1px solid #CBD5E1; padding-bottom: 4px; }
                .summary-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 15px; margin: 15px 0; }
                .stat { display: inline-block; width: 32%; text-align: center; }
                .stat-value { font-size: 20px; font-weight: bold; }
                .stat-label { font-size: 10px; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }
                .module-summary { background: #EFF6FF; border-left: 4px solid #2563EB; padding: 10px; margin-bottom: 10px; font-size: 10px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 10px; }
                th { background: #2563EB; color: white; padding: 8px; text-align: left; font-weight: 600; }
                td { padding: 6px 8px; border-bottom: 1px solid #E2E8F0; }
                tr:nth-child(even) { background: #F8FAFC; }
                tr.no-break { page-break-inside: avoid; }
                .income { color: #10B981; font-weight: bold; }
                .expense { color: #EF4444; font-weight: bold; }
                .page-break { page-break-before: always; }
            </style>

            <h1>Complete Financial Report</h1>
            <p style="color: #64748B; margin-top: 0;">Comprehensive overview of your financial activity. Base Currency: ' . ($baseCurrency['code'] ?? 'USD') . '</p>

            <div class="summary-box">
                <div class="stat"><div class="stat-value income">' . $fmt($summary['totals']['total_income']) . '</div><div class="stat-label">Total Income</div></div>
                <div class="stat"><div class="stat-value expense">' . $fmt($summary['totals']['total_expense']) . '</div><div class="stat-label">Total Expenses</div></div>
                <div class="stat"><div class="stat-value" style="color: ' . ($summary['totals']['net_income'] >= 0 ? '#10B981' : '#EF4444') . ';">' . $fmt($summary['totals']['net_income']) . '</div><div class="stat-label">Net Income</div></div>
            </div>
        ';

        if (!empty($data['accounts']['records'])) {
            $s = $data['accounts']['summary'];
            $html .= '<div class="module-summary"><strong>Summary:</strong> Total Balance: ' . $fmt($s['total_balance'] ?? 0) . '</div>';
            $html .= '<h2>Accounts Overview</h2><table><tr><th>Name</th><th>Type</th><th>Institution</th><th style="text-align:right;">Balance</th></tr>';
            foreach ($data['accounts']['records'] as $a) {
                $html .= '<tr class="no-break"><td>' . htmlspecialchars($a['name']) . '</td><td>' . ucfirst(str_replace('_', ' ', $a['type'])) . '</td><td>' . htmlspecialchars($a['institution'] ?: 'N/A') . '</td><td style="text-align:right; font-weight:600;">' . $fmt($a['current_balance']) . '</td></tr>';
            }
            $html .= '</table>';
        }
        if (!empty($data['transactions']['records'])) {
            $s = $data['transactions']['summary'];
            $html .= '<div class="page-break"></div><div class="module-summary"><strong>Summary:</strong> Income: ' . $fmt($s['total_income']) . ' | Expense: ' . $fmt($s['total_expense']) . ' | Net: ' . $fmt($s['net']) . ' | Count: ' . $s['count'] . '</div>';
            $html .= '<h2>Recent Transactions (Last 100)</h2><table><tr><th>Date</th><th>Type</th><th>Description</th><th>Account</th><th style="text-align:right;">Amount</th></tr>';
            $txnCount = 0;
            foreach ($data['transactions']['records'] as $t) {
                if ($txnCount >= 100)
                    break;
                $class = $t['type'] === 'income' ? 'income' : 'expense';
                $sign = $t['type'] === 'income' ? '+' : '-';
                $html .= '<tr class="no-break"><td>' . $t['transaction_date'] . '</td><td>' . ucfirst($t['type']) . '</td><td>' . htmlspecialchars($t['description'] ?: 'N/A') . '</td><td>' . htmlspecialchars($t['account_name'] ?? 'Unknown') . '</td><td style="text-align:right;" class="' . $class . '">' . $sign . $fmt($t['total_amount']) . '</td></tr>';
                $txnCount++;
            }
            $html .= '</table>';
        }

        if (!empty($data['bills']['records'])) {
            $s = $data['bills']['summary'];
            $html .= '<div class="page-break"></div><div class="module-summary"><strong>Summary:</strong> Total: ' . $fmt($s['total_amount']) . ' | Paid: ' . $fmt($s['paid']) . ' | Unpaid: ' . $fmt($s['unpaid']) . '</div>';
            $html .= '<h2>Bills & Recurring Payments</h2><table><tr><th>Name</th><th style="text-align:right;">Amount</th><th>Frequency</th><th>Next Due</th><th>Status</th></tr>';
            foreach ($data['bills']['records'] as $b) {
                $html .= '<tr class="no-break"><td>' . htmlspecialchars($b['name']) . '</td><td style="text-align:right;">' . $fmt($b['total_amount']) . '</td><td>' . ucfirst($b['frequency']) . '</td><td>' . $b['next_due_date'] . '</td><td>' . ucfirst($b['status']) . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (!empty($data['salaries']['records'])) {
            $s = $data['salaries']['summary'];
            $html .= '<div class="page-break"></div><div class="module-summary"><strong>Summary:</strong> Total Net Pay: ' . $fmt($s['total_net_pay']) . ' | Total Basic: ' . $fmt($s['total_basic']) . '</div>';
            $html .= '<h2>Salary Records</h2><table><tr><th>Employer</th><th>Period</th><th style="text-align:right;">Basic</th><th style="text-align:right;">Net Pay</th><th>Date</th></tr>';
            foreach ($data['salaries']['records'] as $s) {
                $html .= '<tr class="no-break"><td>' . htmlspecialchars($s['company_name']) . '</td><td>' . $s['pay_period_start'] . ' to ' . $s['pay_period_end'] . '</td><td style="text-align:right;">' . $fmt($s['basic_salary']) . '</td><td style="text-align:right;" class="income">' . $fmt($s['net_pay']) . '</td><td>' . $s['payment_date'] . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (!empty($data['savings_vaults']['records'])) {
            $s = $data['savings_vaults']['summary'];
            $html .= '<div class="page-break"></div><div class="module-summary"><strong>Summary:</strong> Total Target: ' . $fmt($s['total_target']) . ' | Total Current: ' . $fmt($s['total_current']) . '</div>';
            $html .= '<h2>Savings Vaults</h2><table><tr><th>Goal</th><th style="text-align:right;">Target</th><th style="text-align:right;">Saved</th><th style="text-align:right;">Progress</th><th>Status</th></tr>';
            foreach ($data['savings_vaults']['records'] as $v) {
                $progress = $v['target_amount'] > 0 ? round(($v['current_amount'] / $v['target_amount']) * 100, 1) : 0;
                $html .= '<tr class="no-break"><td>' . htmlspecialchars($v['name']) . '</td><td style="text-align:right;">' . $fmt($v['target_amount']) . '</td><td style="text-align:right;">' . $fmt($v['current_amount']) . '</td><td style="text-align:right;">' . $progress . '%</td><td>' . ucfirst($v['status']) . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (!empty($data['budgets']['records'])) {
            $s = $data['budgets']['summary'];
            $html .= '<div class="page-break"></div><div class="module-summary"><strong>Summary:</strong> Total Allocated: ' . $fmt($s['total_allocated'] ?? 0) . '</div>';
            $html .= '<h2>Budgets</h2><table><tr><th>Month</th><th>Category</th><th style="text-align:right;">Amount</th><th>Period</th></tr>';
            foreach ($data['budgets']['records'] as $b) {
                $html .= '<tr class="no-break">
                    <td>' . htmlspecialchars((string) ($b['month'] ?? 'N/A')) . '</td>
                    <td>' . htmlspecialchars((string) ($b['category_name'] ?? 'N/A')) . '</td>
                    <td style="text-align:right;">' . $fmt($b['amount'] ?? 0) . '</td>
                    <td>' . htmlspecialchars((string) ($b['period'] ?? 'Monthly')) . '</td>
                </tr>';
            }
            $html .= '</table>';
        }

        if (!empty($data['recurring_incomes']['records'])) {
            $s = $data['recurring_incomes']['summary'];
            $html .= '<div class="page-break"></div><div class="module-summary"><strong>Summary:</strong> Total Estimated: ' . $fmt($s['total_estimated'] ?? 0) . '</div>';
            $html .= '<h2>Recurring Income</h2><table><tr><th>Name</th><th style="text-align:right;">Amount</th><th>Frequency</th><th>Next Date</th></tr>';
            foreach ($data['recurring_incomes']['records'] as $r) {
                $html .= '<tr class="no-break"><td>' . htmlspecialchars($r['name']) . '</td><td style="text-align:right; color: #10B981; font-weight:bold;">' . $fmt($r['amount']) . '</td><td>' . htmlspecialchars(ucfirst($r['frequency'])) . '</td><td>' . htmlspecialchars($r['next_post_date']) . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (!empty($data['daily_logs']['records'])) {
            $s = $data['daily_logs']['summary'];
            $html .= '<div class="page-break"></div><div class="module-summary"><strong>Summary:</strong> Total Logged: ' . $fmt($s['total_amount'] ?? 0) . '</div>';
            $html .= '<h2>Daily Logs</h2><table><tr><th>Date</th><th>Description</th><th style="text-align:right;">Amount</th></tr>';
            foreach ($data['daily_logs']['records'] as $l) {
                $html .= '<tr class="no-break"><td>' . htmlspecialchars($l['log_date'] ?? $l['date'] ?? 'N/A') . '</td><td>' . htmlspecialchars($l['description'] ?? 'N/A') . '</td><td style="text-align:right;">' . $fmt($l['amount'] ?? 0) . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (!empty($data['pending_ledger']['records'])) {
            $s = $data['pending_ledger']['summary'];
            $html .= '<div class="page-break"></div><div class="module-summary"><strong>Summary:</strong> Total Pending: ' . $fmt($s['total_pending'] ?? 0) . '</div>';
            $html .= '<h2>Pending Ledger</h2><table><tr><th>Description</th><th style="text-align:right;">Amount</th><th>Due Date</th><th>Status</th></tr>';
            foreach ($data['pending_ledger']['records'] as $p) {
                $html .= '<tr class="no-break"><td>' . htmlspecialchars($p['description']) . '</td><td style="text-align:right;">' . $fmt($p['amount']) . '</td><td>' . htmlspecialchars($p['due_date'] ?? 'N/A') . '</td><td>' . htmlspecialchars(ucfirst($p['status'] ?? 'pending')) . '</td></tr>';
            }
            $html .= '</table>';
        }

        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tempDir]);
        $mpdf->SetAuthor('Expense Tracker');
        $mpdf->SetTitle('Complete Financial Report');
        $mpdf->SetHTMLHeader('<div style="text-align: right; font-size: 10px; color: #64748B; border-bottom: 1px solid #E2E8F0; padding-bottom: 5px;">Expense Tracker Financial Report • Generated: ' . date('M d, Y') . '</div>');
        $mpdf->SetHTMLFooter('<div style="text-align: center; font-size: 10px; color: #64748B; border-top: 1px solid #E2E8F0; padding-top: 5px;">Page {PAGENO} of {nbpg}</div>');

        $mpdf->WriteHTML($html);

        $filename = 'expense_report_' . date('Y-m-d_His') . '.pdf';
        $tempFile = $tempDir . '/pdf_' . bin2hex(random_bytes(8)) . '.pdf';
        $mpdf->Output($tempFile, 'F');

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);
        $this->logBackupHistory($userId, $filename, 'pdf', $fileSize, $checksum, ['pdf_report']);

        return ['filepath' => $tempFile, 'filename' => $filename, 'checksum' => $checksum];
    }

    public function logBackupHistory(int $userId, string $filename, string $format, int $fileSize, string $checksum, array $modules, ?string $uuid = null): void
    {
        $db = Database::getInstance()->getConnection();
        $backupUuid = $uuid ?? $this->generateUuid();
        $stmt = $db->prepare("
            INSERT INTO backup_history (user_id, backup_uuid, filename, format, file_size_bytes, schema_version, modules_included, checksum_sha256, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')
        ");
        $stmt->execute([$userId, $backupUuid, $filename, $format, $fileSize, self::SCHEMA_VERSION, json_encode($modules), $checksum]);
    }

    private function generateFinancialSummary(int $userId): array
    {
        $lifetimeStats = \App\Services\LifetimeStatsService::getStats($userId);
        $fxpStats = \App\Services\FxpEngine::getUserStats($userId);
        $healthData = \App\Services\FinancialHealthService::calculate($userId);

        return [
            'totals' => [
                'total_income' => $lifetimeStats['total_income'],
                'total_expense' => $lifetimeStats['total_expense'],
                'net_income' => $lifetimeStats['total_income'] - $lifetimeStats['total_expense'],
                'total_savings' => $lifetimeStats['total_savings'],
                'total_transactions' => $lifetimeStats['total_transactions'],
                'goals_completed' => $lifetimeStats['goals_completed'],
                'bills_paid' => $lifetimeStats['bills_paid']
            ],
            'progression' => [
                'lifetime_fxp' => $fxpStats['global']['lifetime_fxp'],
                'current_level' => $fxpStats['global']['current_level'],
                'prestige_stars' => $fxpStats['global']['prestige_stars'],
                'longest_streak' => $lifetimeStats['longest_streak']
            ],
            'health' => [
                'overall_score' => $healthData['overall_score'],
                'savings_rate' => $healthData['metrics']['savings_rate'],
                'emergency_fund_months' => $healthData['metrics']['emergency_fund_months']
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function generateUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }

    public function generateHtml(int $userId): array
    {
        $extracted = $this->generateComprehensiveData($userId);
        $data = $extracted['data'];
        $summary = $this->generateFinancialSummary($userId);
        $baseCurrency = $extracted['base_currency'];
        $fmt = fn($val) => $baseCurrency['symbol'] . number_format((float) $val, 2);

        $categoryExpenses = [];
        $accountBalances = [];
        if (!empty($data['transactions']['records'])) {
            foreach ($data['transactions']['records'] as $t) {
                if ($t['type'] === 'expense' && !empty($t['category_name'])) {
                    $cat = $t['category_name'];
                    $categoryExpenses[$cat] = ($categoryExpenses[$cat] ?? 0) + (float) $t['total_amount'];
                }
            }
        }
        if (!empty($data['accounts']['records'])) {
            foreach ($data['accounts']['records'] as $a) {
                $accountBalances[$a['name']] = (float) $a['current_balance'];
            }
        }

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report - ' . date('Y-m-d') . '</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #2563EB; --success: #10b981; --danger: #ef4444; --bg: #f4f7f6; --card: #ffffff; --text: #1e293b; --muted: #64748b; --border: #e2e8f0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 2rem; line-height: 1.5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: var(--card); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem; border-left: 5px solid var(--primary); }
        .header h1 { color: var(--primary); margin: 0 0 0.5rem; }
        .nav { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 2rem; }
        .nav a { background: var(--card); padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; color: var(--muted); font-size: 0.9rem; font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s; }
        .nav a:hover { color: var(--primary); transform: translateY(-2px); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .card { background: var(--card); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .card h2 { margin-top: 0; color: var(--text); font-size: 1.25rem; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .kpi-card { background: var(--card); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .kpi-card h3 { margin: 0 0 0.5rem; color: var(--muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-card .value { font-size: 1.75rem; font-weight: bold; color: var(--text); }
        .chart-container { position: relative; height: 300px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem; }
        th { background: #f8fafc; text-align: left; padding: 0.75rem; color: var(--muted); font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid var(--border); }
        td { padding: 0.75rem; border-bottom: 1px solid var(--border); }
        tr:last-child td { border-bottom: none; }
        .income { color: var(--success); font-weight: bold; }
        .expense { color: var(--danger); font-weight: bold; }
        .summary-box { background: #eff6ff; border-left: 4px solid var(--primary); padding: 1rem; margin-bottom: 1rem; border-radius: 0 8px 8px 0; font-size: 0.9rem; }
        .progress-bg { background: var(--border); border-radius: 99px; height: 8px; overflow: hidden; margin-top: 0.5rem; }
        .progress-fill { background: var(--primary); height: 100%; border-radius: 99px; }
        @media print { body { background: white; padding: 0; } .nav { display: none; } .card { box-shadow: none; border: 1px solid var(--border); break-inside: avoid; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Comprehensive Financial Report</h1>
        <p style="color: var(--muted); margin: 0;">Generated on ' . date('F d, Y H:i:s') . ' | Base Currency: ' . ($baseCurrency['code'] ?? 'USD') . '</p>
    </div>

    <div class="nav">
        <a href="#summary">Summary</a>
        <a href="#accounts">Accounts</a>
        <a href="#budgets">Budgets</a>
        <a href="#bills">Bills</a>
        <a href="#salaries">Salaries</a>
        <a href="#recurring">Recurring Income</a>
        <a href="#vaults">Savings Vaults</a>
        <a href="#logs">Logs & Pending</a>
        <a href="#progression">Progression</a>
    </div>

    <!-- 1. EXECUTIVE SUMMARY -->
    <div id="summary" class="grid">
        <div class="kpi-card"><h3>Total Income</h3><div class="value income">' . $fmt($summary['totals']['total_income']) . '</div></div>
        <div class="kpi-card"><h3>Total Expenses</h3><div class="value expense">' . $fmt($summary['totals']['total_expense']) . '</div></div>
        <div class="kpi-card"><h3>Net Income</h3><div class="value">' . $fmt($summary['totals']['net_income']) . '</div></div>
        <div class="kpi-card"><h3>Total Savings</h3><div class="value">' . $fmt($summary['totals']['total_savings']) . '</div></div>
        <div class="kpi-card"><h3>Health Score</h3><div class="value" style="color: var(--primary);">' . ($summary['health']['overall_score'] ?? 0) . '/100</div></div>
        <div class="kpi-card"><h3>FXP Level</h3><div class="value" style="color: #f59e0b;">' . ($summary['progression']['current_level'] ?? 1) . '</div></div>
    </div>

    <div class="grid" style="grid-template-columns: 1fr 1fr;">
        <div class="card"><h2>Cash Flow Overview</h2><div class="chart-container"><canvas id="cashFlowChart"></canvas></div></div>
        <div class="card"><h2>Expense Breakdown</h2><div class="chart-container"><canvas id="categoryChart"></canvas></div></div>
        <div class="card" style="grid-column: 1 / -1;"><h2>Account Balances</h2><div class="chart-container"><canvas id="accountChart"></canvas></div></div>
    </div>';

        if (!empty($data['accounts']['records'])) {
            $s = $data['accounts']['summary'];
            $html .= '<div id="accounts" class="card"><h2>Accounts</h2><div class="summary-box"><strong>Summary:</strong> Total Balance: ' . $fmt($s['total_balance'] ?? 0) . ' across ' . count($data['accounts']['records']) . ' accounts.</div><table><tr><th>Name</th><th>Type</th><th>Institution</th><th style="text-align:right;">Balance</th></tr>';
            foreach ($data['accounts']['records'] as $a) {
                $html .= '<tr><td>' . htmlspecialchars($a['name']) . '</td><td>' . ucfirst(str_replace('_', ' ', $a['type'])) . '</td><td>' . htmlspecialchars($a['institution'] ?: 'N/A') . '</td><td style="text-align:right; font-weight:600;">' . $fmt($a['current_balance']) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($data['budgets']['records'])) {
            $s = $data['budgets']['summary'];
            $html .= '<div id="budgets" class="card"><h2>Budgets</h2><div class="summary-box"><strong>Summary:</strong> Total Allocated: ' . $fmt($s['total_allocated'] ?? 0) . '</div><table><tr><th>Month</th><th>Category</th><th style="text-align:right;">Amount</th><th>Period</th></tr>';
            foreach ($data['budgets']['records'] as $b) {
                $html .= '<tr>
                    <td>' . htmlspecialchars((string) ($b['month'] ?? 'N/A')) . '</td>
                    <td>' . htmlspecialchars((string) ($b['category_name'] ?? 'N/A')) . '</td>
                    <td style="text-align:right;">' . $fmt($b['amount'] ?? 0) . '</td>
                    <td>' . htmlspecialchars((string) ($b['period'] ?? 'Monthly')) . '</td>
                </tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($data['bills']['records'])) {
            $s = $data['bills']['summary'];
            $html .= '<div id="bills" class="card"><h2>Bills & Recurring Payments</h2><div class="summary-box"><strong>Summary:</strong> Total: ' . $fmt($s['total_amount'] ?? 0) . ' | Paid: ' . $fmt($s['paid'] ?? 0) . ' | Unpaid: ' . $fmt($s['unpaid'] ?? 0) . '</div><table><tr><th>Name</th><th style="text-align:right;">Amount</th><th>Frequency</th><th>Next Due</th><th>Status</th></tr>';
            foreach ($data['bills']['records'] as $b) {
                $html .= '<tr><td>' . htmlspecialchars($b['name']) . '</td><td style="text-align:right;">' . $fmt($b['total_amount']) . '</td><td>' . ucfirst($b['frequency']) . '</td><td>' . $b['next_due_date'] . '</td><td>' . ucfirst($b['status']) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($data['salaries']['records'])) {
            $s = $data['salaries']['summary'];
            $html .= '<div id="salaries" class="card"><h2>Salary Records</h2><div class="summary-box"><strong>Summary:</strong> Total Net Pay: ' . $fmt($s['total_net_pay'] ?? 0) . ' | Total Basic: ' . $fmt($s['total_basic'] ?? 0) . '</div><table><tr><th>Employer</th><th>Period</th><th style="text-align:right;">Basic</th><th style="text-align:right;">Net Pay</th><th>Date</th></tr>';
            foreach ($data['salaries']['records'] as $s) {
                $html .= '<tr><td>' . htmlspecialchars($s['company_name']) . '</td><td>' . $s['pay_period_start'] . ' to ' . $s['pay_period_end'] . '</td><td style="text-align:right;">' . $fmt($s['basic_salary']) . '</td><td style="text-align:right;" class="income">' . $fmt($s['net_pay']) . '</td><td>' . $s['payment_date'] . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($data['recurring_incomes']['records'])) {
            $s = $data['recurring_incomes']['summary'];
            $html .= '<div id="recurring" class="card"><h2>Recurring Income</h2><div class="summary-box"><strong>Summary:</strong> Total Estimated Monthly: ' . $fmt($s['total_estimated'] ?? 0) . '</div><table><tr><th>Name</th><th style="text-align:right;">Amount</th><th>Frequency</th><th>Next Date</th></tr>';
            foreach ($data['recurring_incomes']['records'] as $r) {
                $html .= '<tr><td>' . htmlspecialchars($r['name']) . '</td><td style="text-align:right; color: var(--success); font-weight:bold;">' . $fmt($r['amount']) . '</td><td>' . htmlspecialchars(ucfirst($r['frequency'])) . '</td><td>' . htmlspecialchars($r['next_post_date']) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($data['savings_vaults']['records'])) {
            $s = $data['savings_vaults']['summary'];
            $html .= '<div id="vaults" class="card"><h2>Savings Vaults</h2><div class="summary-box"><strong>Summary:</strong> Total Target: ' . $fmt($s['total_target'] ?? 0) . ' | Total Current: ' . $fmt($s['total_current'] ?? 0) . '</div><table><tr><th>Goal</th><th style="text-align:right;">Target</th><th style="text-align:right;">Saved</th><th style="width: 30%;">Progress</th><th>Status</th></tr>';
            foreach ($data['savings_vaults']['records'] as $v) {
                $progress = $v['target_amount'] > 0 ? min(100, round(($v['current_amount'] / $v['target_amount']) * 100, 1)) : 0;
                $html .= '<tr><td>' . htmlspecialchars($v['name']) . '</td><td style="text-align:right;">' . $fmt($v['target_amount']) . '</td><td style="text-align:right;">' . $fmt($v['current_amount']) . '</td><td><div class="progress-bg"><div class="progress-fill" style="width: ' . $progress . '%;"></div></div><small>' . $progress . '%</small></td><td>' . ucfirst($v['status']) . '</td></tr>';
            }
            $html .= '</table></div>';
        }

        if (!empty($data['daily_logs']['records']) || !empty($data['pending_ledger']['records'])) {
            $html .= '<div id="logs" class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">';

            if (!empty($data['daily_logs']['records'])) {
                $s = $data['daily_logs']['summary'];
                $html .= '<div class="card" style="margin:0;"><h2>Daily Logs</h2><div class="summary-box"><strong>Total Logged:</strong> ' . $fmt($s['total_amount'] ?? 0) . '</div><table><tr><th>Date</th><th>Description</th><th style="text-align:right;">Amount</th></tr>';
                foreach ($data['daily_logs']['records'] as $l) {
                    $html .= '<tr><td>' . htmlspecialchars($l['log_date'] ?? $l['date'] ?? 'N/A') . '</td><td>' . htmlspecialchars($l['description'] ?? 'N/A') . '</td><td style="text-align:right;">' . $fmt($l['amount'] ?? 0) . '</td></tr>';
                }
                $html .= '</table></div>';
            }

            if (!empty($data['pending_ledger']['records'])) {
                $s = $data['pending_ledger']['summary'];
                $html .= '<div class="card" style="margin:0;"><h2>Pending Ledger</h2><div class="summary-box"><strong>Total Pending:</strong> ' . $fmt($s['total_pending'] ?? 0) . '</div><table><tr><th>Description</th><th style="text-align:right;">Amount</th><th>Due Date</th><th>Status</th></tr>';
                foreach ($data['pending_ledger']['records'] as $p) {
                    $html .= '<tr><td>' . htmlspecialchars($p['description']) . '</td><td style="text-align:right;">' . $fmt($p['amount']) . '</td><td>' . htmlspecialchars($p['due_date'] ?? 'N/A') . '</td><td>' . htmlspecialchars(ucfirst($p['status'] ?? 'pending')) . '</td></tr>';
                }
                $html .= '</table></div>';
            }
            $html .= '</div>';
        }

        if (!empty($data['user_fxp_stats']['records']) || !empty($data['user_achievements']['records'])) {
            $html .= '<div id="progression" class="card"><h2>Progression & Achievements</h2>';
            if (!empty($data['user_fxp_stats']['records'])) {
                $fxp = $data['user_fxp_stats']['records'][0];
                $html .= '<div class="summary-box"><strong>Level:</strong> ' . ($fxp['current_level'] ?? 1) . ' | <strong>Lifetime FXP:</strong> ' . number_format($fxp['lifetime_fxp'] ?? 0) . ' | <strong>Prestige Stars:</strong> ' . ($fxp['prestige_stars'] ?? 0) . '</div>';
            }
            if (!empty($data['user_achievements']['records'])) {
                $html .= '<h3 style="margin-top: 1.5rem; font-size: 1rem; color: var(--muted);">Unlocked Achievements (' . count($data['user_achievements']['records']) . ')</h3><table><tr><th>Name</th><th>Unlocked At</th></tr>';
                foreach ($data['user_achievements']['records'] as $ach) {
                    if (!empty($ach['unlocked_at'])) {
                        $html .= '<tr><td>' . htmlspecialchars($ach['name'] ?? $ach['achievement_name'] ?? 'Achievement') . '</td><td>' . htmlspecialchars($ach['unlocked_at']) . '</td></tr>';
                    }
                }
                $html .= '</table>';
            }
            $html .= '</div>';
        }

        $html .= '</div>
<script>
    const ctx1 = document.getElementById("cashFlowChart").getContext("2d");
    new Chart(ctx1, {
        type: "doughnut",
        data: {
            labels: ["Income", "Expenses"],
            datasets: [{
                data: [' . $summary['totals']['total_income'] . ', ' . $summary['totals']['total_expense'] . '],
                backgroundColor: ["#10b981", "#ef4444"],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
    });

    const ctx2 = document.getElementById("categoryChart").getContext("2d");
    new Chart(ctx2, {
        type: "pie",
        data: {
            labels: ' . json_encode(array_keys($categoryExpenses)) . ',
            datasets: [{
                data: ' . json_encode(array_values($categoryExpenses)) . ',
                backgroundColor: ["#3b82f6", "#8b5cf6", "#f59e0b", "#10b981", "#ef4444", "#64748b", "#ec4899", "#14b8a6"],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
    });

    const ctx3 = document.getElementById("accountChart").getContext("2d");
    new Chart(ctx3, {
        type: "bar",
        data: {
            labels: ' . json_encode(array_keys($accountBalances)) . ',
            datasets: [{
                label: "Current Balance",
                data: ' . json_encode(array_values($accountBalances)) . ',
                backgroundColor: "#2563EB",
                borderRadius: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>
</body>
</html>';

        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir))
            @mkdir($tempDir, 0755, true);

        $tempFile = $tempDir . '/report_' . bin2hex(random_bytes(8)) . '.html';
        file_put_contents($tempFile, $html);

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);
        $filename = 'expense_report_' . date('Y-m-d_His') . '.html';
        $this->logBackupHistory($userId, $filename, 'html', $fileSize, $checksum, ['html_report']);

        return ['filepath' => $tempFile, 'filename' => $filename, 'checksum' => $checksum];
    }
}