<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Services\MathService;
use PDO;

/**
 * Institutional-Grade Streaming Export Service
 *
 * Implements memory-bounded streaming data extraction to php://output
 * with strict CSV formula injection mitigation and UTF-8 BOM encoding.
 * Supports PHP 8.4+ fputcsv compliance, output buffer clearing,
 * granular transaction selection, dynamic multi-currency rate calculation,
 * hierarchical JSON v2.0, and modular sanitized ZIP archives.
 */
class ExportService
{
    public const SCHEMA_VERSION = '2.0.0';
    public const CHUNK_SIZE = 500;

    /**
     * Characters that trigger spreadsheet formula execution in Excel/Calc (CWE-1236).
     */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r", '%'];

    /**
     * Neutralize CSV / Spreadsheet Formula Injection (CWE-1236).
     * If a cell begins with a formula trigger character, prepend a single quote (')
     * so spreadsheet processors parse the cell as raw string text rather than an executable command.
     */
    public static function sanitizeCsvCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;
        if ($str === '') {
            return '';
        }

        // Numeric values (including valid decimals) are safe unless preceded by whitespace/triggers
        if (is_numeric($str) && !in_array($str[0], ['+', '-', '=', '@', '%'], true)) {
            return $str;
        }

        $firstChar = $str[0];
        if (in_array($firstChar, self::FORMULA_TRIGGERS, true)) {
            return "'" . $str;
        }

        // Check for leading whitespace followed by trigger
        $trimmed = ltrim($str);
        if ($trimmed !== '' && in_array($trimmed[0], self::FORMULA_TRIGGERS, true)) {
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Prepend UTF-8 Byte Order Mark (BOM) to stream.
     * Ensures Microsoft Excel correctly detects UTF-8 multi-byte characters and currency glyphs.
     *
     * @param resource $stream
     */
    public static function writeUtf8Bom($stream): void
    {
        fwrite($stream, "\xEF\xBB\xBF");
    }

    /**
     * Clear all active output buffering levels to guarantee no stray PHP deprecation
     * or HTML tags pollute the binary / CSV / JSON stream.
     */
    public static function cleanOutputBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Resolve quick-export date presets into concrete start and end dates.
     *
     * Presets supported:
     * - 'current_month': First to last day of current calendar month
     * - 'previous_quarter': First to last day of previous calendar quarter
     * - 'ytd': Jan 1 of current year to today
     * - 'previous_month': First to last day of preceding month
     */
    public static function resolveDatePreset(string $preset): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        switch (strtolower(trim($preset))) {
            case 'current_month':
                return [
                    'start_date' => $now->modify('first day of this month')->format('Y-m-d'),
                    'end_date' => $now->modify('last day of this month')->format('Y-m-d'),
                    'label' => 'Current Month (' . $now->format('F Y') . ')'
                ];

            case 'previous_month':
                $prev = $now->modify('first day of previous month');
                return [
                    'start_date' => $prev->format('Y-m-d'),
                    'end_date' => $prev->modify('last day of this month')->format('Y-m-d'),
                    'label' => 'Previous Month (' . $prev->format('F Y') . ')'
                ];

            case 'previous_quarter':
                $currentMonth = (int) $now->format('n');
                $currentYear = (int) $now->format('Y');
                $quarter = (int) ceil($currentMonth / 3);

                if ($quarter === 1) {
                    $startYear = $currentYear - 1;
                    $start = "{$startYear}-10-01";
                    $end = "{$startYear}-12-31";
                    $label = "Q4 {$startYear}";
                } elseif ($quarter === 2) {
                    $start = "{$currentYear}-01-01";
                    $end = "{$currentYear}-03-31";
                    $label = "Q1 {$currentYear}";
                } elseif ($quarter === 3) {
                    $start = "{$currentYear}-04-01";
                    $end = "{$currentYear}-06-30";
                    $label = "Q2 {$currentYear}";
                } else {
                    $start = "{$currentYear}-07-01";
                    $end = "{$currentYear}-09-30";
                    $label = "Q3 {$currentYear}";
                }

                return [
                    'start_date' => $start,
                    'end_date' => $end,
                    'label' => "Previous Quarter ({$label})"
                ];

            case 'ytd':
            case 'year_to_date':
                return [
                    'start_date' => $now->format('Y') . '-01-01',
                    'end_date' => $now->format('Y-m-d'),
                    'label' => 'Year-to-Date (' . $now->format('Y') . ')'
                ];

            default:
                return [
                    'start_date' => null,
                    'end_date' => null,
                    'label' => 'Custom'
                ];
        }
    }

    /**
     * Stream transactions to CSV with arbitrary-precision figures and formula injection mitigation.
     * Fully compatible with PHP 8.4+ by explicitly providing all delimiter/enclosure/escape arguments to fputcsv().
     * Supports both granular selected IDs and filtered ranges.
     * Dynamically computes Converted Base Amount via BCMath if stored blank.
     *
     * @param int $userId Authenticated user ID
     * @param array $filters Query filters (start_date, end_date, account_id, category_id, type, currency_id, status, selected_ids)
     * @param resource|null $stream Output resource (defaults to php://output)
     */
    public function streamTransactionsCsv(int $userId, array $filters = [], $stream = null): void
    {
        $closeStream = false;
        if ($stream === null) {
            self::cleanOutputBuffer();

            if (!headers_sent()) {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d_His') . '.csv"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Content-Type-Options: nosniff');
            }
            $stream = fopen('php://output', 'w');
            $closeStream = true;
        }

        self::writeUtf8Bom($stream);

        // Header Definition
        $headers = [
            'Transaction ID',
            'Date',
            'Type',
            'Account',
            'Category',
            'Description',
            'Amount',
            'Currency',
            'Converted Base Amount',
            'Exchange Rate Applied',
            'Status',
            'Notes',
            'Is Recurring',
            'Client Mutation ID',
            'Created At'
        ];
        // PHP 8.4+ compliance: explicitly pass delimiter, enclosure, escape
        fputcsv($stream, $headers, ',', '"', "\\");

        $db = Database::getInstance()->getConnection();

        $sql = "
            SELECT 
                t.id,
                t.transaction_date,
                t.type,
                COALESCE(a.name, 'N/A') AS account_name,
                COALESCE(c.name, 'Uncategorized') AS category_name,
                t.description,
                t.total_amount,
                COALESCE(cur.code, 'USD') AS currency_code,
                t.converted_amount,
                COALESCE(t.rate_applied, '1.000000') AS rate_applied,
                t.settled_amount,
                t.status,
                t.notes,
                t.is_recurring,
                COALESCE(t.client_mutation_id, '') AS client_mutation_id,
                t.created_at
            FROM transactions t
            LEFT JOIN accounts a ON t.account_id = a.id
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN currencies cur ON t.currency_id = cur.id
            WHERE t.user_id = :user_id AND t.deleted_at IS NULL
        ";

        $params = [':user_id' => $userId];

        // Mode 1A: Granular Selected IDs array
        if (!empty($filters['selected_ids']) && is_array($filters['selected_ids'])) {
            $sanitizedIds = array_filter(array_map('intval', $filters['selected_ids']), fn($id) => $id > 0);
            if (!empty($sanitizedIds)) {
                $placeholders = [];
                foreach ($sanitizedIds as $idx => $idVal) {
                    $pKey = ":sel_id_{$idx}";
                    $placeholders[] = $pKey;
                    $params[$pKey] = $idVal;
                }
                $sql .= " AND t.id IN (" . implode(',', $placeholders) . ")";
            }
        } else {
            // Mode 1B: Filtered Range Criteria
            if (!empty($filters['preset'])) {
                $presetDates = self::resolveDatePreset((string) $filters['preset']);
                if (!empty($presetDates['start_date']) && empty($filters['start_date'])) {
                    $filters['start_date'] = $presetDates['start_date'];
                }
                if (!empty($presetDates['end_date']) && empty($filters['end_date'])) {
                    $filters['end_date'] = $presetDates['end_date'];
                }
            }

            if (!empty($filters['start_date'])) {
                $sql .= " AND t.transaction_date >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }
            if (!empty($filters['end_date'])) {
                $sql .= " AND t.transaction_date <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }
            if (!empty($filters['account_id'])) {
                $sql .= " AND t.account_id = :account_id";
                $params[':account_id'] = (int) $filters['account_id'];
            }
            if (!empty($filters['category_id'])) {
                $sql .= " AND t.category_id = :category_id";
                $params[':category_id'] = (int) $filters['category_id'];
            }
            if (!empty($filters['type'])) {
                $sql .= " AND t.type = :type";
                $params[':type'] = $filters['type'];
            }
            if (!empty($filters['status'])) {
                $sql .= " AND t.status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['currency_id'])) {
                $sql .= " AND t.currency_id = :currency_id";
                $params[':currency_id'] = (int) $filters['currency_id'];
            }
        }

        $sql .= " ORDER BY t.transaction_date DESC, t.id DESC";

        $offset = 0;
        do {
            $chunkSql = $sql . " LIMIT " . self::CHUNK_SIZE . " OFFSET " . $offset;
            $stmt = $db->prepare($chunkSql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                // Ensure Converted Base Amount is never blank
                $totalAmount = (string) ($row['total_amount'] ?? '0.00');
                $rateApplied = (string) ($row['rate_applied'] ?? '1.000000');
                $convertedAmount = $row['converted_amount'];

                if ($convertedAmount === null || $convertedAmount === '') {
                    if (!empty($row['settled_amount']) && $row['settled_amount'] !== '0.00') {
                        $convertedAmount = (string) $row['settled_amount'];
                    } else {
                        // Dynamically compute using bcmath: total_amount * rate_applied
                        $convertedAmount = MathService::mul($totalAmount, $rateApplied, MathService::SCALE_MONEY);
                    }
                }

                // Standardize timestamp formatting
                $formattedCreatedAt = !empty($row['created_at']) 
                    ? date('Y-m-d H:i:s', strtotime((string) $row['created_at'])) 
                    : date('Y-m-d H:i:s');

                $sanitizedRow = [
                    self::sanitizeCsvCell($row['id']),
                    self::sanitizeCsvCell($row['transaction_date']),
                    self::sanitizeCsvCell(ucfirst((string) $row['type'])),
                    self::sanitizeCsvCell($row['account_name']),
                    self::sanitizeCsvCell($row['category_name']),
                    self::sanitizeCsvCell($row['description']),
                    self::sanitizeCsvCell($totalAmount),
                    self::sanitizeCsvCell($row['currency_code']),
                    self::sanitizeCsvCell($convertedAmount),
                    self::sanitizeCsvCell($rateApplied),
                    self::sanitizeCsvCell(ucfirst((string) $row['status'])),
                    self::sanitizeCsvCell($row['notes']),
                    !empty($row['is_recurring']) ? 'Yes' : 'No',
                    self::sanitizeCsvCell($row['client_mutation_id']),
                    self::sanitizeCsvCell($formattedCreatedAt)
                ];

                // PHP 8.4+ compliance: explicitly pass delimiter, enclosure, escape
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
     * Stream full accounts overview to CSV with PHP 8.4+ fputcsv compliance.
     *
     * @param resource|null $stream
     */
    public function streamAccountsCsv(int $userId, $stream = null): void
    {
        $closeStream = false;
        if ($stream === null) {
            self::cleanOutputBuffer();

            if (!headers_sent()) {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="accounts_' . date('Y-m-d_His') . '.csv"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
            }
            $stream = fopen('php://output', 'w');
            $closeStream = true;
        }

        self::writeUtf8Bom($stream);

        $headers = [
            'Account ID',
            'Name',
            'Type',
            'Institution',
            'Account Number',
            'Currency',
            'Opening Balance',
            'Current Balance',
            'Status',
            'Notes',
            'Created At'
        ];
        fputcsv($stream, $headers, ',', '"', "\\");

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, COALESCE(cur.code, 'USD') AS currency_code
            FROM accounts a
            LEFT JOIN currencies cur ON a.currency_id = cur.id
            WHERE a.user_id = ? AND a.deleted_at IS NULL
            ORDER BY a.name ASC
        ");
        $stmt->execute([$userId]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sanitizedRow = [
                self::sanitizeCsvCell($row['id']),
                self::sanitizeCsvCell($row['name']),
                self::sanitizeCsvCell(ucfirst((string) $row['type'])),
                self::sanitizeCsvCell($row['institution']),
                self::sanitizeCsvCell($row['account_number']),
                self::sanitizeCsvCell($row['currency_code']),
                self::sanitizeCsvCell($row['opening_balance']),
                self::sanitizeCsvCell($row['current_balance']),
                self::sanitizeCsvCell(ucfirst((string) $row['status'])),
                self::sanitizeCsvCell($row['notes']),
                self::sanitizeCsvCell($row['created_at'])
            ];
            fputcsv($stream, $sanitizedRow, ',', '"', "\\");
        }

        if ($closeStream && is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Stream structured, hierarchical JSON v2.0 export directly to output.
     * Uses incremental flushing to keep memory strictly under 16MB.
     *
     * @param resource|null $stream
     */
    public function streamJsonV2(int $userId, $stream = null): void
    {
        $closeStream = false;
        if ($stream === null) {
            self::cleanOutputBuffer();

            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
                header('Content-Disposition: attachment; filename="expense_export_v2_' . date('Y-m-d_His') . '.json"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Content-Type-Options: nosniff');
            }
            $stream = fopen('php://output', 'w');
            $closeStream = true;
        }

        $db = Database::getInstance()->getConnection();
        $baseCurrency = \App\Models\CurrencyService::getUserBaseCurrency($userId);

        $metadata = [
            'app_name' => 'Expense Tracker Enterprise',
            'schema_version' => self::SCHEMA_VERSION,
            'export_timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'base_currency' => $baseCurrency,
            'generator' => 'App\\Services\\ExportService::streamJsonV2'
        ];

        // Begin JSON Envelope
        fwrite($stream, "{\n");
        fwrite($stream, '  "metadata": ' . json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ",\n");
        fwrite($stream, '  "data": {' . "\n");

        $tables = [
            'accounts' => "SELECT * FROM accounts WHERE user_id = ? AND deleted_at IS NULL ORDER BY id ASC",
            'categories' => "SELECT * FROM categories WHERE user_id = ? AND deleted_at IS NULL ORDER BY id ASC",
            'transactions' => "SELECT * FROM transactions WHERE user_id = ? AND deleted_at IS NULL ORDER BY id ASC",
            'transaction_splits' => "SELECT ts.* FROM transaction_splits ts JOIN transactions t ON ts.transaction_id = t.id WHERE t.user_id = ? ORDER BY ts.id ASC",
            'budgets' => "SELECT * FROM budgets WHERE user_id = ? ORDER BY id ASC",
            'bills' => "SELECT * FROM bills WHERE user_id = ? ORDER BY id ASC",
            'bill_payments' => "SELECT * FROM bill_payments WHERE user_id = ? ORDER BY id ASC",
            'employers' => "SELECT * FROM employers WHERE user_id = ? ORDER BY id ASC",
            'salaries' => "SELECT * FROM salaries WHERE user_id = ? ORDER BY id ASC",
            'savings_vaults' => "SELECT * FROM savings_vaults WHERE user_id = ? ORDER BY id ASC",
            'vault_transactions' => "SELECT * FROM vault_transactions WHERE user_id = ? ORDER BY id ASC",
            'daily_logs' => "SELECT * FROM daily_logs WHERE user_id = ? ORDER BY id ASC",
            'pending_ledger' => "SELECT * FROM pending_ledger WHERE user_id = ? ORDER BY id ASC",
            'recurring_incomes' => "SELECT * FROM recurring_incomes WHERE user_id = ? ORDER BY id ASC",
            'timeline_events' => "SELECT * FROM timeline_events WHERE user_id = ? ORDER BY id ASC",
            'forecast_scenarios' => "SELECT * FROM forecast_scenarios WHERE user_id = ? ORDER BY id ASC",
            'radar_alerts' => "SELECT * FROM radar_alerts WHERE user_id = ? ORDER BY id ASC",
            'user_fxp_stats' => "SELECT * FROM user_fxp_stats WHERE user_id = ? ORDER BY id ASC",
            'user_mastery_stats' => "SELECT * FROM user_mastery_stats WHERE user_id = ? ORDER BY id ASC",
            'user_streaks' => "SELECT * FROM user_streaks WHERE user_id = ? ORDER BY id ASC",
            'user_achievements' => "SELECT * FROM user_achievements WHERE user_id = ? ORDER BY id ASC",
            'planning_scenarios' => "SELECT * FROM planning_scenarios WHERE user_id = ? ORDER BY id ASC",
            'planning_loans' => "SELECT * FROM planning_loans WHERE user_id = ? ORDER BY id ASC",
            'planning_investments' => "SELECT * FROM planning_investments WHERE user_id = ? ORDER BY id ASC",
            'user_preferences' => "SELECT * FROM user_preferences WHERE user_id = ? ORDER BY id ASC"
        ];

        $tableKeys = array_keys($tables);
        $totalTables = count($tableKeys);

        foreach ($tableKeys as $idx => $tableName) {
            $query = $tables[$tableName];
            $isLastTable = ($idx === $totalTables - 1);

            fwrite($stream, '    "' . $tableName . '": [' . "\n");

            // Chunked streaming for each table
            $offset = 0;
            $isFirstRowOverall = true;

            do {
                $chunkSql = $query . " LIMIT " . self::CHUNK_SIZE . " OFFSET " . $offset;
                try {
                    $stmt = $db->prepare($chunkSql);
                    $stmt->execute([$userId]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (\PDOException $e) {
                    $rows = [];
                }

                $rowCount = count($rows);
                for ($r = 0; $r < $rowCount; $r++) {
                    $jsonRow = json_encode($rows[$r], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if ($isFirstRowOverall) {
                        fwrite($stream, "      " . $jsonRow);
                        $isFirstRowOverall = false;
                    } else {
                        fwrite($stream, ",\n      " . $jsonRow);
                    }
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                $offset += self::CHUNK_SIZE;
            } while ($rowCount === self::CHUNK_SIZE);

            fwrite($stream, "\n    ]" . ($isLastTable ? "" : ",") . "\n");
        }

        // Close JSON Envelope
        fwrite($stream, "  }\n}\n");

        if ($closeStream && is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Generate a multi-file ZIP archive containing individual CSVs for each entity
     * with UTF-8 BOM, formula sanitization, and explicit fputcsv parameters.
     */
    public function generateSanitizedZip(int $userId): array
    {
        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $zipFile = $tempDir . '/export_' . bin2hex(random_bytes(8)) . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to initialize ZIP archive on server.');
        }

        $tempFiles = [];
        $db = Database::getInstance()->getConnection();

        $tableQueries = [
            'accounts' => "SELECT * FROM accounts WHERE user_id = ? AND deleted_at IS NULL",
            'categories' => "SELECT * FROM categories WHERE user_id = ? AND deleted_at IS NULL",
            'transactions' => "SELECT * FROM transactions WHERE user_id = ? AND deleted_at IS NULL",
            'budgets' => "SELECT * FROM budgets WHERE user_id = ?",
            'bills' => "SELECT * FROM bills WHERE user_id = ?",
            'salaries' => "SELECT * FROM salaries WHERE user_id = ?",
            'savings_vaults' => "SELECT * FROM savings_vaults WHERE user_id = ?",
            'daily_logs' => "SELECT * FROM daily_logs WHERE user_id = ?"
        ];

        foreach ($tableQueries as $tableName => $query) {
            try {
                $stmt = $db->prepare($query);
                $stmt->execute([$userId]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $csvPath = $tempDir . '/' . $tableName . '_' . bin2hex(random_bytes(4)) . '.csv';
                    $tempFiles[] = $csvPath;
                    $fp = fopen($csvPath, 'w');
                    if ($fp) {
                        self::writeUtf8Bom($fp);
                        fputcsv($fp, array_keys($rows[0]), ',', '"', "\\");
                        foreach ($rows as $row) {
                            $sanitized = array_map([self::class, 'sanitizeCsvCell'], $row);
                            fputcsv($fp, $sanitized, ',', '"', "\\");
                        }
                        fclose($fp);
                        $zip->addFile($csvPath, $tableName . '.csv');
                    }
                }
            } catch (\PDOException $e) {
                Logger::warning("Skipping table {$tableName} in ZIP export: " . $e->getMessage());
            }
        }

        // Add metadata manifest
        $metadata = [
            'app' => 'Expense Tracker Enterprise',
            'schema_version' => self::SCHEMA_VERSION,
            'exported_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId
        ];
        $zip->addFromString('manifest.json', json_encode($metadata, JSON_PRETTY_PRINT));
        $zip->close();

        // Clean temporary CSV files
        foreach ($tempFiles as $f) {
            if (file_exists($f)) {
                @unlink($f);
            }
        }

        $checksum = hash_file('sha256', $zipFile);
        $fileSize = (int) filesize($zipFile);
        $filename = 'expense_archive_' . date('Y-m-d_His') . '.zip';

        return [
            'filepath' => $zipFile,
            'filename' => $filename,
            'checksum' => $checksum,
            'filesize' => $fileSize
        ];
    }
}
