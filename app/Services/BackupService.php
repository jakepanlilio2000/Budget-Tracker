<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Services\MathService;
use PDO;

/**
 * Institutional-Grade Backup and Recovery Engine
 *
 * Provides pure-PHP chunked SQL database dumping, AES-256-GCM authenticated
 * encryption with PBKDF2 key derivation, Gzip compression, and memory-bounded exports.
 */
class BackupService
{
    public const SCHEMA_VERSION = '2.0.0';
    public const APP_VERSION = '2.0.0';
    public const APP_NAME = 'Expense Tracker Enterprise';
    public const CHUNK_SIZE = 500;

    /**
     * Magic header bytes for encrypted backup archives: "EXPBKP" + 1-byte version 0x01
     */
    public const MAGIC_HEADER = "EXPBKP\x01";

    /**
     * PBKDF2 Iteration count for key derivation (OWASP standard >= 100,000 for SHA-256).
     */
    private const PBKDF2_ROUNDS = 100000;

    /**
     * Tables included in user-scoped backup archives, in topological dependency order.
     */
    private const BACKUP_TABLES = [
        'accounts',
        'categories',
        'employers',
        'savings_vaults',
        'bills',
        'budgets',
        'salaries',
        'transactions',
        'transaction_splits',
        'vault_transactions',
        'bill_payments',
        'daily_logs',
        'pending_ledger',
        'recurring_incomes',
        'timeline_events',
        'forecast_scenarios',
        'radar_alerts',
        'user_fxp_stats',
        'user_mastery_stats',
        'user_streaks',
        'user_achievements',
        'planning_scenarios',
        'planning_loans',
        'planning_investments',
        'user_preferences'
    ];

    /**
     * Extract comprehensive user data with arbitrary-precision monetary summation.
     */
    public function generateComprehensiveData(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $data = [];
        $modulesIncluded = [];
        $baseCurrency = \App\Models\CurrencyService::getUserBaseCurrency($userId);

        $addModule = function (string $table, array $rows, array $summary = []) use (&$data, &$modulesIncluded) {
            if (!empty($rows)) {
                $data[$table] = [
                    'summary' => $summary,
                    'records' => $rows
                ];
                $modulesIncluded[] = $table;
            }
        };

        // 1. Accounts
        $stmt = $db->prepare("SELECT * FROM accounts WHERE user_id = ? AND deleted_at IS NULL ORDER BY id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $totalBal = '0.00';
            foreach ($rows as $r) {
                $totalBal = MathService::add($totalBal, (string) ($r['current_balance'] ?? '0.00'));
            }
            $addModule('accounts', $rows, ['total_balance' => $totalBal]);
        }

        // 2. Categories
        $stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? AND deleted_at IS NULL ORDER BY id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $addModule('categories', $rows);
        }

        // 3. Transactions
        $stmt = $db->prepare("
            SELECT t.*, a.name as account_name, c.name as category_name, cur.symbol as currency_symbol, cur.code as currency_code
            FROM transactions t 
            LEFT JOIN accounts a ON t.account_id = a.id 
            LEFT JOIN categories c ON t.category_id = c.id 
            LEFT JOIN currencies cur ON t.currency_id = cur.id
            WHERE t.user_id = ? AND t.deleted_at IS NULL 
            ORDER BY t.transaction_date DESC, t.id DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $totalIncome = '0.00';
            $totalExpense = '0.00';
            foreach ($rows as $r) {
                $amt = (string) ($r['total_amount'] ?? '0.00');
                if (($r['type'] ?? '') === 'income') {
                    $totalIncome = MathService::add($totalIncome, $amt);
                } elseif (($r['type'] ?? '') === 'expense') {
                    $totalExpense = MathService::add($totalExpense, $amt);
                }
            }
            $net = MathService::sub($totalIncome, $totalExpense);
            $addModule('transactions', $rows, [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net' => $net,
                'count' => count($rows)
            ]);
        }

        // 4. Transaction Splits
        $stmt = $db->prepare("
            SELECT ts.*, c.name as category_name 
            FROM transaction_splits ts 
            LEFT JOIN categories c ON ts.category_id = c.id 
            JOIN transactions t ON ts.transaction_id = t.id 
            WHERE t.user_id = ?
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $totalSplit = '0.00';
            foreach ($rows as $r) {
                $totalSplit = MathService::add($totalSplit, (string) ($r['amount'] ?? '0.00'));
            }
            $addModule('transaction_splits', $rows, ['total_amount' => $totalSplit]);
        }

        // 5. Budgets
        $stmt = $db->prepare("SELECT * FROM budgets WHERE user_id = ? ORDER BY id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $totalAlloc = '0.00';
            foreach ($rows as $r) {
                $totalAlloc = MathService::add($totalAlloc, (string) ($r['amount'] ?? '0.00'));
            }
            $addModule('budgets', $rows, ['total_allocated' => $totalAlloc]);
        }

        // 6. Bills
        $stmt = $db->prepare("SELECT b.*, c.name as category_name FROM bills b LEFT JOIN categories c ON b.category_id = c.id WHERE b.user_id = ? ORDER BY b.id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $totalBills = '0.00';
            $paidBills = '0.00';
            foreach ($rows as $r) {
                $amt = (string) ($r['total_amount'] ?? '0.00');
                $totalBills = MathService::add($totalBills, $amt);
                if (($r['status'] ?? '') === 'paid') {
                    $paidBills = MathService::add($paidBills, $amt);
                }
            }
            $unpaidBills = MathService::sub($totalBills, $paidBills);
            $addModule('bills', $rows, [
                'total_amount' => $totalBills,
                'paid' => $paidBills,
                'unpaid' => $unpaidBills
            ]);
        }

        // 7. Bill Payments
        $stmt = $db->prepare("SELECT * FROM bill_payments WHERE user_id = ? ORDER BY id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $totalPaid = '0.00';
            foreach ($rows as $r) {
                $totalPaid = MathService::add($totalPaid, (string) ($r['amount'] ?? '0.00'));
            }
            $addModule('bill_payments', $rows, ['total_paid' => $totalPaid]);
        }

        // 8. Employers & Salaries
        $stmt = $db->prepare("SELECT * FROM employers WHERE user_id = ? ORDER BY id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $addModule('employers', $rows);
        }

        $stmt = $db->prepare("SELECT s.*, e.company_name FROM salaries s JOIN employers e ON s.employer_id = e.id WHERE s.user_id = ? ORDER BY s.id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $netPay = '0.00';
            $basic = '0.00';
            foreach ($rows as $r) {
                $netPay = MathService::add($netPay, (string) ($r['net_pay'] ?? '0.00'));
                $basic = MathService::add($basic, (string) ($r['basic_salary'] ?? '0.00'));
            }
            $addModule('salaries', $rows, [
                'total_net_pay' => $netPay,
                'total_basic' => $basic
            ]);
        }

        // 9. Savings Vaults & Transactions
        $stmt = $db->prepare("SELECT * FROM savings_vaults WHERE user_id = ? ORDER BY id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $target = '0.00';
            $current = '0.00';
            foreach ($rows as $r) {
                $target = MathService::add($target, (string) ($r['target_amount'] ?? '0.00'));
                $current = MathService::add($current, (string) ($r['current_amount'] ?? '0.00'));
            }
            $addModule('savings_vaults', $rows, [
                'total_target' => $target,
                'total_current' => $current
            ]);
        }

        $stmt = $db->prepare("SELECT vt.*, sv.name as vault_name FROM vault_transactions vt JOIN savings_vaults sv ON vt.vault_id = sv.id WHERE vt.user_id = ? ORDER BY vt.id ASC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $dep = '0.00';
            $with = '0.00';
            foreach ($rows as $r) {
                $amt = (string) ($r['amount'] ?? '0.00');
                if (($r['type'] ?? '') === 'deposit') {
                    $dep = MathService::add($dep, $amt);
                } elseif (($r['type'] ?? '') === 'withdrawal') {
                    $with = MathService::add($with, $amt);
                }
            }
            $addModule('vault_transactions', $rows, [
                'total_deposits' => $dep,
                'total_withdrawals' => $with
            ]);
        }

        // 10. Simple Tables
        $simpleTables = [
            'daily_logs',
            'pending_ledger',
            'timeline_events',
            'recurring_incomes',
            'forecast_scenarios',
            'radar_alerts',
            'user_fxp_stats',
            'user_mastery_stats',
            'user_streaks',
            'user_achievements',
            'planning_scenarios',
            'planning_loans',
            'planning_investments',
            'user_preferences'
        ];

        foreach ($simpleTables as $tbl) {
            try {
                $stmt = $db->prepare("SELECT * FROM `{$tbl}` WHERE user_id = ? ORDER BY id ASC");
                $stmt->execute([$userId]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    $addModule($tbl, $rows);
                }
            } catch (\PDOException $e) {
                // Ignore missing optional tables
            }
        }

        return [
            'data' => $data,
            'modules' => $modulesIncluded,
            'base_currency' => $baseCurrency
        ];
    }

    /**
     * Pure-PHP chunked SQL dumper. Generates an executable, valid SQL file
     * containing table data for the specific user.
     */
    public function generateSqlDump(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir . '/dump_' . bin2hex(random_bytes(8)) . '.sql';
        $fp = fopen($tempFile, 'w');
        if (!$fp) {
            throw new \RuntimeException('Failed to open temporary file for SQL dump.');
        }

        $timestamp = date('Y-m-d H:i:s');
        $uuid = $this->generateUuid();

        // Write SQL Preamble
        fwrite($fp, "-- ==============================================================\n");
        fwrite($fp, "-- EXPENSE TRACKER ENTERPRISE PURE-PHP SQL EXPORT\n");
        fwrite($fp, "-- Backup UUID: {$uuid}\n");
        fwrite($fp, "-- User ID: {$userId}\n");
        fwrite($fp, "-- Timestamp: {$timestamp} UTC\n");
        fwrite($fp, "-- Schema Version: " . self::SCHEMA_VERSION . "\n");
        fwrite($fp, "-- ==============================================================\n\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($fp, "SET time_zone = '+00:00';\n");
        fwrite($fp, "START TRANSACTION;\n\n");

        $modulesIncluded = [];

        foreach (self::BACKUP_TABLES as $table) {
            try {
                // Count rows for user in this table
                if ($table === 'transaction_splits') {
                    $countStmt = $db->prepare("SELECT COUNT(*) FROM transaction_splits ts JOIN transactions t ON ts.transaction_id = t.id WHERE t.user_id = ?");
                } elseif ($table === 'vault_transactions') {
                    $countStmt = $db->prepare("SELECT COUNT(*) FROM vault_transactions vt JOIN savings_vaults sv ON vt.vault_id = sv.id WHERE vt.user_id = ?");
                } else {
                    $countStmt = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE user_id = ?");
                }

                $countStmt->execute([$userId]);
                $totalRows = (int) $countStmt->fetchColumn();

                if ($totalRows === 0) {
                    continue;
                }

                $modulesIncluded[] = $table;
                fwrite($fp, "-- -------------------------------------------------------------\n");
                fwrite($fp, "-- Records for table: `{$table}` ({$totalRows} rows)\n");
                fwrite($fp, "-- -------------------------------------------------------------\n");

                $offset = 0;
                do {
                    if ($table === 'transaction_splits') {
                        $query = "SELECT ts.* FROM transaction_splits ts JOIN transactions t ON ts.transaction_id = t.id WHERE t.user_id = :uid ORDER BY ts.id ASC LIMIT :lim OFFSET :off";
                    } elseif ($table === 'vault_transactions') {
                        $query = "SELECT vt.* FROM vault_transactions vt JOIN savings_vaults sv ON vt.vault_id = sv.id WHERE vt.user_id = :uid ORDER BY vt.id ASC LIMIT :lim OFFSET :off";
                    } else {
                        $query = "SELECT * FROM `{$table}` WHERE user_id = :uid ORDER BY id ASC LIMIT :lim OFFSET :off";
                    }

                    $stmt = $db->prepare($query);
                    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
                    $stmt->bindValue(':lim', self::CHUNK_SIZE, PDO::PARAM_INT);
                    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
                    $stmt->execute();
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($rows)) {
                        $columns = array_keys($rows[0]);
                        $escapedColumns = array_map(fn($col) => "`" . str_replace("`", "``", $col) . "`", $columns);
                        $colList = implode(', ', $escapedColumns);

                        $valChunks = [];
                        foreach ($rows as $row) {
                            $rowVals = [];
                            foreach ($columns as $col) {
                                $val = $row[$col];
                                if ($val === null) {
                                    $rowVals[] = 'NULL';
                                } elseif (is_int($val) || is_float($val)) {
                                    $rowVals[] = (string) $val;
                                } else {
                                    $rowVals[] = $db->quote((string) $val);
                                }
                            }
                            $valChunks[] = "(" . implode(', ', $rowVals) . ")";
                        }

                        fwrite($fp, "INSERT INTO `{$table}` ({$colList}) VALUES\n");
                        fwrite($fp, implode(",\n", $valChunks) . ";\n\n");
                    }

                    $rowCount = count($rows);
                    $offset += self::CHUNK_SIZE;
                } while ($rowCount === self::CHUNK_SIZE);

            } catch (\PDOException $e) {
                fwrite($fp, "-- Skipping table `{$table}` due to database notice: " . $e->getMessage() . "\n\n");
            }
        }

        // Postamble
        fwrite($fp, "COMMIT;\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fwrite($fp, "-- End of dump\n");
        fclose($fp);

        $checksum = hash_file('sha256', $tempFile);
        $fileSize = (int) filesize($tempFile);
        $filename = 'expense_backup_' . date('Y-m-d_His') . '.sql';

        $this->logBackupHistory($userId, $filename, 'sql', $fileSize, $checksum, $modulesIncluded, $uuid);

        return [
            'filepath' => $tempFile,
            'filename' => $filename,
            'checksum' => $checksum,
            'uuid' => $uuid,
            'filesize' => $fileSize,
            'modules' => $modulesIncluded
        ];
    }

    /**
     * Authenticated Symmetric Encryption using AES-256-GCM with PBKDF2 key derivation.
     *
     * Binary Format:
     * - Magic Header: 7 bytes ("EXPBKP\x01")
     * - Salt: 16 bytes
     * - IV: 12 bytes
     * - Tag: 16 bytes (GCM authentication tag)
     * - Ciphertext: variable length
     */
    public static function encryptData(string $plaintext, string $passphrase): string
    {
        if ($passphrase === '') {
            throw new \InvalidArgumentException('Encryption passphrase cannot be empty.');
        }

        $salt = random_bytes(16);
        $iv = random_bytes(12); // Standard 96-bit IV for GCM
        $key = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ROUNDS, 32, true);

        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('AES-256-GCM encryption failed: ' . openssl_error_string());
        }

        return self::MAGIC_HEADER . $salt . $iv . $tag . $ciphertext;
    }

    /**
     * Decrypt AES-256-GCM binary payload and verify authentication tag.
     */
    public static function decryptData(string $binaryPayload, string $passphrase): string
    {
        $headerLen = strlen(self::MAGIC_HEADER);
        if (strlen($binaryPayload) < $headerLen + 16 + 12 + 16) {
            throw new \RuntimeException('Invalid encrypted payload: file is too small or truncated.');
        }

        $magic = substr($binaryPayload, 0, $headerLen);
        if ($magic !== self::MAGIC_HEADER) {
            throw new \RuntimeException('Invalid file signature. File is not an authentic encrypted backup.');
        }

        $offset = $headerLen;
        $salt = substr($binaryPayload, $offset, 16);
        $offset += 16;
        $iv = substr($binaryPayload, $offset, 12);
        $offset += 12;
        $tag = substr($binaryPayload, $offset, 16);
        $offset += 16;
        $ciphertext = substr($binaryPayload, $offset);

        $key = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ROUNDS, 32, true);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed: incorrect password or corrupted/tampered payload.');
        }

        return $plaintext;
    }

    /**
     * Generate an encrypted backup (JSON or SQL), compressed with Gzip, encrypted with AES-256-GCM.
     */
    public function generateEncryptedBackup(int $userId, string $passphrase, string $baseFormat = 'json'): array
    {
        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $uuid = $this->generateUuid();

        if ($baseFormat === 'sql') {
            $dumpResult = $this->generateSqlDump($userId);
            $rawPayload = file_get_contents($dumpResult['filepath']);
            @unlink($dumpResult['filepath']);
            $modules = $dumpResult['modules'];
        } else {
            $extracted = $this->generateComprehensiveData($userId);
            $summary = $this->generateFinancialSummary($userId);
            $payload = [
                'metadata' => [
                    'app_name' => self::APP_NAME,
                    'app_version' => self::APP_VERSION,
                    'schema_version' => self::SCHEMA_VERSION,
                    'backup_uuid' => $uuid,
                    'export_timestamp' => date('Y-m-d H:i:s'),
                    'user_id' => $userId,
                    'format' => 'json.gz.enc',
                    'base_currency' => $extracted['base_currency']
                ],
                'financial_summary' => $summary,
                'data' => $extracted['data']
            ];
            $rawPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $modules = $extracted['modules'];
        }

        // 1. Compress with Gzip
        $compressed = gzencode($rawPayload, 9);
        if ($compressed === false) {
            throw new \RuntimeException('Failed to compress backup payload with Gzip.');
        }

        // 2. Encrypt with AES-256-GCM
        $encrypted = self::encryptData($compressed, $passphrase);

        $filename = 'expense_backup_' . date('Y-m-d_His') . '.' . $baseFormat . '.gz.enc';
        $tempFile = $tempDir . '/enc_' . bin2hex(random_bytes(8)) . '.enc';
        file_put_contents($tempFile, $encrypted);

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);

        $this->logBackupHistory($userId, $filename, $baseFormat . '.gz.enc', $fileSize, $checksum, $modules, $uuid);

        return [
            'filepath' => $tempFile,
            'filename' => $filename,
            'checksum' => $checksum,
            'uuid' => $uuid,
            'filesize' => $fileSize,
            'modules' => $modules
        ];
    }

    /**
     * Unified backup generator dispatching across formats.
     */
    public function generateBackup(int $userId, string $format = 'json', ?string $passphrase = null): array
    {
        if (!empty($passphrase)) {
            $baseFormat = str_starts_with($format, 'sql') ? 'sql' : 'json';
            return $this->generateEncryptedBackup($userId, $passphrase, $baseFormat);
        }

        if ($format === 'sql') {
            return $this->generateSqlDump($userId);
        }

        if ($format === 'zip' || $format === 'csv') {
            return $this->generateZipCsv($userId);
        }

        if ($format === 'xlsx') {
            return $this->generateXlsx($userId);
        }

        if ($format === 'pdf') {
            return $this->generatePdf($userId);
        }

        if ($format === 'html') {
            return $this->generateHtml($userId);
        }

        // Default JSON
        $backupUuid = $this->generateUuid();
        $extracted = $this->generateComprehensiveData($userId);
        $summary = $this->generateFinancialSummary($userId);

        $payload = [
            'metadata' => [
                'app_name' => self::APP_NAME,
                'app_version' => self::APP_VERSION,
                'schema_version' => self::SCHEMA_VERSION,
                'backup_uuid' => $backupUuid,
                'export_timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $userId,
                'format' => 'json',
                'base_currency' => $extracted['base_currency']
            ],
            'financial_summary' => $summary,
            'data' => $extracted['data']
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($jsonPayload === false) {
            throw new \RuntimeException('Failed to encode backup data to JSON: ' . json_last_error_msg());
        }

        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir . '/backup_' . bin2hex(random_bytes(8)) . '.json';
        file_put_contents($tempFile, $jsonPayload);

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);
        $filename = 'expense_backup_' . date('Y-m-d_His') . '.json';

        $this->logBackupHistory($userId, $filename, 'json', $fileSize, $checksum, $extracted['modules'], $backupUuid);

        return [
            'filepath' => $tempFile,
            'filename' => $filename,
            'checksum' => $checksum,
            'uuid' => $backupUuid,
            'filesize' => $fileSize,
            'modules' => $extracted['modules']
        ];
    }

    /**
     * Generate modular CSV ZIP archive with formula sanitization.
     */
    public function generateZipCsv(int $userId): array
    {
        $exportService = new ExportService();
        $res = $exportService->generateSanitizedZip($userId);
        $this->logBackupHistory($userId, $res['filename'], 'zip', $res['filesize'], $res['checksum'], ['csv_archive']);
        return $res;
    }

    /**
     * Generate spreadsheet report using PhpOffice Spreadsheet.
     */
    public function generateXlsx(int $userId): array
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new \RuntimeException('PhpSpreadsheet is required for XLSX exports.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $extracted = $this->generateComprehensiveData($userId);
        $baseCurrency = $extracted['base_currency'];
        $fmt = fn($val) => ($baseCurrency['symbol'] ?? '$') . number_format((float) $val, 2);

        $summary = $this->generateFinancialSummary($userId);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Financial Summary');
        $sheet->fromArray([
            ['Expense Tracker Enterprise - Financial Summary'],
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
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
        $tempFile = $tempDir . '/xlsx_' . bin2hex(random_bytes(8)) . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);
        $filename = 'expense_backup_' . date('Y-m-d_His') . '.xlsx';
        $this->logBackupHistory($userId, $filename, 'xlsx', $fileSize, $checksum, $extracted['modules']);

        return ['filepath' => $tempFile, 'filename' => $filename, 'checksum' => $checksum];
    }

    /**
     * Generate executive PDF report using mPDF.
     */
    public function generatePdf(int $userId): array
    {
        if (!class_exists('\Mpdf\Mpdf')) {
            throw new \RuntimeException('mPDF is required for PDF exports.');
        }

        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $extracted = $this->generateComprehensiveData($userId);
        $data = $extracted['data'];
        $summary = $this->generateFinancialSummary($userId);
        $baseCurrency = $extracted['base_currency'];
        $fmt = fn($val) => ($baseCurrency['symbol'] ?? '$') . number_format((float) $val, 2);

        $html = '
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 11px; color: #334155; }
                h1 { color: #2563EB; font-size: 22px; margin-bottom: 5px; border-bottom: 2px solid #2563EB; padding-bottom: 8px; }
                h2 { color: #1E293B; font-size: 16px; margin-top: 25px; border-bottom: 1px solid #CBD5E1; padding-bottom: 4px; }
                .summary-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 15px; margin: 15px 0; }
                .stat { display: inline-block; width: 32%; text-align: center; }
                .stat-value { font-size: 20px; font-weight: bold; }
                .stat-label { font-size: 10px; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 10px; }
                th { background: #2563EB; color: white; padding: 8px; text-align: left; }
                td { padding: 6px 8px; border-bottom: 1px solid #E2E8F0; }
                .income { color: #10B981; font-weight: bold; }
                .expense { color: #EF4444; font-weight: bold; }
            </style>
            <h1>Institutional Financial Statement</h1>
            <p style="color: #64748B;">Comprehensive audit summary. Base Currency: ' . ($baseCurrency['code'] ?? 'USD') . '</p>
            <div class="summary-box">
                <div class="stat"><div class="stat-value income">' . $fmt($summary['totals']['total_income']) . '</div><div class="stat-label">Total Income</div></div>
                <div class="stat"><div class="stat-value expense">' . $fmt($summary['totals']['total_expense']) . '</div><div class="stat-label">Total Expenses</div></div>
                <div class="stat"><div class="stat-value" style="color: ' . ((float)$summary['totals']['net_income'] >= 0 ? '#10B981' : '#EF4444') . ';">' . $fmt($summary['totals']['net_income']) . '</div><div class="stat-label">Net Income</div></div>
            </div>
        ';

        if (!empty($data['accounts']['records'])) {
            $html .= '<h2>Accounts Overview</h2><table><tr><th>Name</th><th>Type</th><th>Institution</th><th style="text-align:right;">Balance</th></tr>';
            foreach ($data['accounts']['records'] as $a) {
                $html .= '<tr><td>' . htmlspecialchars($a['name']) . '</td><td>' . ucfirst((string) $a['type']) . '</td><td>' . htmlspecialchars($a['institution'] ?: 'N/A') . '</td><td style="text-align:right;">' . $fmt($a['current_balance']) . '</td></tr>';
            }
            $html .= '</table>';
        }

        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tempDir]);
        $mpdf->SetAuthor('Expense Tracker Enterprise');
        $mpdf->SetTitle('Institutional Financial Statement');
        $mpdf->WriteHTML($html);

        $filename = 'expense_report_' . date('Y-m-d_His') . '.pdf';
        $tempFile = $tempDir . '/pdf_' . bin2hex(random_bytes(8)) . '.pdf';
        $mpdf->Output($tempFile, 'F');

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);
        $this->logBackupHistory($userId, $filename, 'pdf', $fileSize, $checksum, ['pdf_report']);

        return ['filepath' => $tempFile, 'filename' => $filename, 'checksum' => $checksum];
    }

    /**
     * Generate standalone interactive HTML report.
     */
    public function generateHtml(int $userId): array
    {
        $extracted = $this->generateComprehensiveData($userId);
        $data = $extracted['data'];
        $summary = $this->generateFinancialSummary($userId);
        $baseCurrency = $extracted['base_currency'];
        $fmt = fn($val) => ($baseCurrency['symbol'] ?? '$') . number_format((float) $val, 2);

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Report - ' . date('Y-m-d') . '</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h1 { color: #2563eb; margin-top: 0; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 2rem 0; }
        .card { padding: 1.5rem; background: #f1f5f9; border-radius: 8px; text-align: center; }
        .card .val { font-size: 1.5rem; font-weight: bold; margin-top: 0.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { padding: 0.75rem; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; }
    </style>
</head>
<body>
<div class="container">
    <h1>Financial Snapshot</h1>
    <p style="color: #64748b;">Generated ' . date('F d, Y H:i:s') . ' | Base Currency: ' . ($baseCurrency['code'] ?? 'USD') . '</p>
    <div class="grid">
        <div class="card"><div>Total Income</div><div class="val" style="color: #10b981;">' . $fmt($summary['totals']['total_income']) . '</div></div>
        <div class="card"><div>Total Expense</div><div class="val" style="color: #ef4444;">' . $fmt($summary['totals']['total_expense']) . '</div></div>
        <div class="card"><div>Net Position</div><div class="val">' . $fmt($summary['totals']['net_income']) . '</div></div>
    </div>
</div>
</body>
</html>';

        $tempDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir . '/report_' . bin2hex(random_bytes(8)) . '.html';
        file_put_contents($tempFile, $html);

        $fileSize = (int) filesize($tempFile);
        $checksum = hash_file('sha256', $tempFile);
        $filename = 'expense_report_' . date('Y-m-d_His') . '.html';
        $this->logBackupHistory($userId, $filename, 'html', $fileSize, $checksum, ['html_report']);

        return ['filepath' => $tempFile, 'filename' => $filename, 'checksum' => $checksum];
    }

    /**
     * Persist backup record to backup_history table.
     */
    public function logBackupHistory(int $userId, string $filename, string $format, int $fileSize, string $checksum, array $modules, ?string $uuid = null): void
    {
        $db = Database::getInstance()->getConnection();
        $backupUuid = $uuid ?? $this->generateUuid();
        try {
            $stmt = $db->prepare("
                INSERT INTO backup_history (user_id, backup_uuid, filename, format, file_size_bytes, schema_version, modules_included, checksum_sha256, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')
            ");
            $stmt->execute([$userId, $backupUuid, $filename, $format, $fileSize, self::SCHEMA_VERSION, json_encode($modules), $checksum]);
        } catch (\PDOException $e) {
            Logger::error("Failed to log backup history: " . $e->getMessage());
        }
    }

    public function generateFinancialSummary(int $userId): array
    {
        $lifetimeStats = \App\Services\LifetimeStatsService::getStats($userId);
        $fxpStats = \App\Services\FxpEngine::getUserStats($userId);
        $healthData = \App\Services\FinancialHealthService::calculate($userId);

        $totalInc = MathService::parseDecimal((string) ($lifetimeStats['total_income'] ?? '0.00'));
        $totalExp = MathService::parseDecimal((string) ($lifetimeStats['total_expense'] ?? '0.00'));
        $netInc = MathService::sub($totalInc, $totalExp);

        return [
            'totals' => [
                'total_income' => $totalInc,
                'total_expense' => $totalExp,
                'net_income' => $netInc,
                'total_savings' => MathService::parseDecimal((string) ($lifetimeStats['total_savings'] ?? '0.00')),
                'total_transactions' => (int) ($lifetimeStats['total_transactions'] ?? 0),
                'goals_completed' => (int) ($lifetimeStats['goals_completed'] ?? 0),
                'bills_paid' => (int) ($lifetimeStats['bills_paid'] ?? 0)
            ],
            'progression' => [
                'lifetime_fxp' => $fxpStats['global']['lifetime_fxp'] ?? 0,
                'current_level' => $fxpStats['global']['current_level'] ?? 1,
                'prestige_stars' => $fxpStats['global']['prestige_stars'] ?? 0,
                'longest_streak' => $lifetimeStats['longest_streak'] ?? 0
            ],
            'health' => [
                'overall_score' => $healthData['overall_score'] ?? 100,
                'savings_rate' => $healthData['metrics']['savings_rate'] ?? 0,
                'emergency_fund_months' => $healthData['metrics']['emergency_fund_months'] ?? 0
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}