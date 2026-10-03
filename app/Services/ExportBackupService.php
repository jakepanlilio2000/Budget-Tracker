<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Cache;
use App\Services\MathService;
use App\Services\ExportService;
use App\Services\BackupService;
use App\Services\AccountService;
use App\Services\AchievementEngine;
use App\Services\FinancialSummaryEngine;

/**
 * Unified Institutional-Grade Export, Backup, and Executive Statement Engine
 *
 * Implements full relational database backups (SQL/JSON), RFC 4180 streaming CSV exports
 * with formula injection protection (CWE-1236), AES-256-GCM AEAD encryption, and
 * executive-grade fintech PDF monthly statements.
 */
class ExportBackupService
{
    public const SCHEMA_VERSION = '2.0.0';
    public const APP_VERSION = '2.0.0';
    public const CHUNK_SIZE = 500;

    /**
     * Characters that trigger spreadsheet formula execution in Excel/Calc.
     */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r", '%'];

    /**
     * Neutralize CSV / Spreadsheet Formula Injection (CWE-1236).
     */
    public static function sanitizeCsvCell(mixed $value): string
    {
        return ExportService::sanitizeCsvCell($value);
    }

    /**
     * Write UTF-8 Byte Order Mark (\xEF\xBB\xBF) for proper Excel rendering.
     */
    public static function writeUtf8Bom($stream): void
    {
        ExportService::writeUtf8Bom($stream);
    }

    /**
     * Stream transactions to RFC 4180 compliant CSV directly with explicit audit columns:
     * - Transaction ID, Date, Account, Type, Category, Amount, Currency, Exchange Rate,
     *   Settled Base Amount, Fee, Reference / Transfer Target, Notes.
     */
    public function streamTransactionsCsv(int $userId, array $filters = [], $stream = null): void
    {
        $closeStream = false;
        if ($stream === null) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (!headers_sent()) {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="transactions_audit_' . date('Y-m-d_His') . '.csv"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Content-Type-Options: nosniff');
            }
            $stream = fopen('php://output', 'w');
            $closeStream = true;
        }

        self::writeUtf8Bom($stream);

        // Explicit RFC 4180 Audit Columns
        $headers = [
            'Transaction ID',
            'Date',
            'Account',
            'Type',
            'Category',
            'Amount',
            'Currency',
            'Exchange Rate',
            'Settled Base Amount',
            'Fee',
            'Reference / Transfer Target',
            'Notes'
        ];
        fputcsv($stream, $headers, ',', '"', "\\");

        $db = Database::getInstance()->getConnection();

        $sql = "
            SELECT 
                t.id,
                t.transaction_date,
                COALESCE(a.name, 'N/A') AS account_name,
                t.type,
                COALESCE(c.name, 'Uncategorized') AS category_name,
                t.total_amount,
                COALESCE(cur.code, 'USD') AS currency_code,
                COALESCE(t.rate_applied, '1.000000') AS rate_applied,
                COALESCE(t.settled_amount, t.converted_amount) AS settled_base_amount,
                '0.00' AS fee,
                COALESCE(t.client_mutation_id, '') AS reference_target,
                t.description,
                t.notes
            FROM transactions t
            LEFT JOIN accounts a ON t.account_id = a.id
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN currencies cur ON t.currency_id = cur.id
            WHERE t.user_id = :user_id AND t.deleted_at IS NULL
        ";

        $params = [':user_id' => $userId];

        if (!empty($filters['start_date'])) {
            $sql .= " AND t.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND t.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND t.type = :type";
            $params[':type'] = $filters['type'];
        }

        $sql .= " ORDER BY t.transaction_date DESC, t.id DESC";

        $offset = 0;
        do {
            $chunkSql = $sql . " LIMIT " . self::CHUNK_SIZE . " OFFSET " . $offset;
            $stmt = $db->prepare($chunkSql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $notesCombined = trim(($row['description'] ?? '') . ' | ' . ($row['notes'] ?? ''), ' |');
                $sanitizedRow = [
                    self::sanitizeCsvCell($row['id']),
                    self::sanitizeCsvCell($row['transaction_date']),
                    self::sanitizeCsvCell($row['account_name']),
                    self::sanitizeCsvCell(ucfirst((string) $row['type'])),
                    self::sanitizeCsvCell($row['category_name']),
                    self::sanitizeCsvCell($row['total_amount']),
                    self::sanitizeCsvCell($row['currency_code']),
                    self::sanitizeCsvCell($row['rate_applied']),
                    self::sanitizeCsvCell($row['settled_base_amount']),
                    self::sanitizeCsvCell($row['fee']),
                    self::sanitizeCsvCell($row['reference_target']),
                    self::sanitizeCsvCell($notesCombined)
                ];
                fputcsv($stream, $sanitizedRow, ',', '"', "\\");
            }

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            $rowCount = count($rows);
            $offset += self::CHUNK_SIZE;
        } while ($rowCount === self::CHUNK_SIZE);

        if ($closeStream && is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Generate an executive-grade PDF statement for the user.
     * Features typographic header, executive summary KPIs, styled tabular numerals,
     * directional badges, and print-CSS page break rules.
     */
    public function generateExecutivePdfStatement(int $userId, ?string $month = null, ?array $selectedTxnIds = null): array
    {
        $targetMonth = $month ?? date('Y-m');
        $db = Database::getInstance()->getConnection();

        // 1. Fetch User Data
        $stmtUser = $db->prepare("SELECT id, name, email FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: ['id' => $userId, 'name' => 'User', 'email' => ''];

        // 2. Base Currency
        $baseCurrency = \App\Models\CurrencyService::getUserBaseCurrency($userId);

        // 3. Accounts & Closing Balances
        $stmtAcc = $db->prepare("
            SELECT a.*, COALESCE(cur.code, 'USD') AS currency_code, COALESCE(cur.symbol, '$') AS currency_symbol
            FROM accounts a
            LEFT JOIN currencies cur ON a.currency_id = cur.id
            WHERE a.user_id = ? AND a.deleted_at IS NULL
            ORDER BY a.name ASC
        ");
        $stmtAcc->execute([$userId]);
        $accounts = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

        $endingNetWorth = '0.00';
        foreach ($accounts as $a) {
            $endingNetWorth = MathService::add($endingNetWorth, (string) ($a['current_balance'] ?? '0.00'));
        }

        // 4. Period Transactions (or Granular Selection)
        if (!empty($selectedTxnIds)) {
            $cleanIds = array_filter(array_map('intval', $selectedTxnIds), fn($id) => $id > 0);
            $inPlaceholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $stmtTx = $db->prepare("
                SELECT 
                    t.*,
                    COALESCE(a.name, 'Primary') AS account_name,
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    COALESCE(cur.code, 'USD') AS currency_code,
                    COALESCE(cur.symbol, '$') AS currency_symbol
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN currencies cur ON t.currency_id = cur.id
                WHERE t.user_id = ? 
                  AND t.deleted_at IS NULL
                  AND t.id IN ({$inPlaceholders})
                ORDER BY t.transaction_date ASC, t.id ASC
            ");
            $stmtTx->execute(array_merge([$userId], $cleanIds));
            $formattedPeriod = 'Selected Transactions (' . count($cleanIds) . ' records)';
        } else {
            $stmtTx = $db->prepare("
                SELECT 
                    t.*,
                    COALESCE(a.name, 'Primary') AS account_name,
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    COALESCE(cur.code, 'USD') AS currency_code,
                    COALESCE(cur.symbol, '$') AS currency_symbol
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN currencies cur ON t.currency_id = cur.id
                WHERE t.user_id = ? 
                  AND t.deleted_at IS NULL
                  AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
                ORDER BY t.transaction_date ASC, t.id ASC
            ");
            $stmtTx->execute([$userId, $targetMonth]);
            $periodDate = \DateTimeImmutable::createFromFormat('Y-m-d', $targetMonth . '-01') ?: new \DateTimeImmutable();
            $formattedPeriod = $periodDate->format('F Y');
        }
        $transactions = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

        $totalIncome = '0.00';
        $totalExpense = '0.00';
        foreach ($transactions as $t) {
            $amt = (string) ($t['total_amount'] ?? '0.00');
            $type = strtolower((string) ($t['type'] ?? 'expense'));
            if ($type === 'income') {
                $totalIncome = MathService::add($totalIncome, $amt);
            } elseif ($type === 'expense') {
                $totalExpense = MathService::add($totalExpense, $amt);
            }
        }
        $netCashFlow = MathService::sub($totalIncome, $totalExpense);
        $startingNetWorth = MathService::sub($endingNetWorth, $netCashFlow);

        $summary = [
            'starting_net_worth' => $startingNetWorth,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_cash_flow' => $netCashFlow,
            'ending_net_worth' => $endingNetWorth
        ];

        $generatedAt = date('Y-m-d H:i:s');

        // Render HTML from executive template
        $templatePath = BASE_PATH . '/app/Views/reports/executive_statement.php';
        ob_start();
        $user = $userData;
        include $templatePath;
        $htmlContent = ob_get_clean();

        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $filename = 'statement_' . $targetMonth . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $tempPdfPath = $tempDir . '/' . $filename;

        if (class_exists('\Mpdf\Mpdf')) {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'tempDir' => $tempDir
            ]);
            $mpdf->SetTitle("Financial Statement - {$formattedPeriod}");
            $mpdf->SetAuthor('ExpensePro Enterprise');
            $mpdf->WriteHTML($htmlContent);
            $mpdf->Output($tempPdfPath, 'F');
        } else {
            // HTML Fallback if mPDF is not installed
            $filename = 'statement_' . $targetMonth . '_' . bin2hex(random_bytes(4)) . '.html';
            $tempPdfPath = $tempDir . '/' . $filename;
            file_put_contents($tempPdfPath, $htmlContent);
        }

        $fileSize = (int) filesize($tempPdfPath);
        $checksum = hash_file('sha256', $tempPdfPath);

        // Log audit
        $backupService = new BackupService();
        $backupService->logBackupHistory($userId, $filename, 'pdf', $fileSize, $checksum, ['executive_statement']);

        return [
            'filepath' => $tempPdfPath,
            'filename' => $filename,
            'filesize' => $fileSize,
            'checksum' => $checksum,
            'period' => $formattedPeriod
        ];
    }

    /**
     * Generate full relational database backup (JSON or SQL), compressed and optionally AES-256-GCM encrypted.
     */
    public function generateFullBackup(int $userId, string $format = 'json', ?string $passphrase = null): array
    {
        $backupService = new BackupService();
        return $backupService->generateBackup($userId, $format, $passphrase);
    }

    /**
     * Inspect and dry-run preview a backup archive without touching database records.
     */
    public function validateAndPreview(int $userId, string $filePath, ?string $passphrase = null): array
    {
        $restoreService = new RestoreService();
        return $restoreService->validateAndPreview($userId, $filePath, $passphrase);
    }

    /**
     * Execute atomic restore inside a PDO transaction and run double-entry reconciliation.
     */
    public function executeRestore(int $userId, string $filePath, ?string $passphrase = null): array
    {
        $restoreService = new RestoreService();
        return $restoreService->executeRestore($userId, $filePath, $passphrase);
    }
}
