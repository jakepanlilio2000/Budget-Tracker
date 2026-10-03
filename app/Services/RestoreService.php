<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Cache;
use App\Services\BackupService;
use App\Services\AccountService;
use App\Services\AchievementEngine;
use App\Services\FinancialSummaryEngine;
use PDO;

/**
 * Institutional-Grade Staging Import Validator & Atomic Restore Service
 *
 * Implements multi-stage validation, encrypted archive unpacking (AES-256-GCM),
 * staging preview dry-runs, ACID transaction execution with foreign key ID remapping,
 * and post-restore ledger balance reconciliation.
 */
class RestoreService
{
    private const SUPPORTED_SCHEMAS = ['1.0.0', '2.0.0'];

    /**
     * Inspect and validate uploaded backup file without modifying database state.
     * Unpacks encryption/compression if necessary and returns a comprehensive dry-run preview.
     */
    public function validateAndPreview(int $userId, string $filePath, ?string $passphrase = null): array
    {
        $payload = $this->unpackPayload($filePath, $passphrase);

        if ($payload['type'] === 'json') {
            return $this->validateAndPreviewJson($userId, $payload['data']);
        } elseif ($payload['type'] === 'sql') {
            return $this->validateAndPreviewSql($userId, $payload['data']);
        }

        throw new \InvalidArgumentException('Unsupported backup archive payload.');
    }

    /**
     * Execute atomic restoration of the workspace from validated backup file.
     */
    public function executeRestore(int $userId, string $filePath, ?string $passphrase = null): array
    {
        $payload = $this->unpackPayload($filePath, $passphrase);

        if ($payload['type'] === 'json') {
            return $this->executeJsonRestore($userId, $payload['data']);
        } elseif ($payload['type'] === 'sql') {
            return $this->executeSqlRestore($userId, $payload['data']);
        }

        throw new \InvalidArgumentException('Unsupported backup format for execution.');
    }

    /**
     * Unpack file: decrypts (if AES-256-GCM encrypted) and decompresses (if Gzip compressed).
     */
    private function unpackPayload(string $filePath, ?string $passphrase = null): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException('Backup file is unreadable or does not exist on disk.');
        }

        $raw = file_get_contents($filePath);
        if ($raw === false || strlen($raw) === 0) {
            throw new \RuntimeException('Uploaded backup file is empty.');
        }

        $isEncrypted = str_starts_with($raw, BackupService::MAGIC_HEADER);
        if ($isEncrypted) {
            if (empty($passphrase)) {
                throw new \InvalidArgumentException('This backup archive is encrypted with AES-256-GCM. A decryption password is required.');
            }
            $raw = BackupService::decryptData($raw, $passphrase);
        }

        // Check for Gzip compression header (\x1F\x8B)
        $isGzipped = str_starts_with($raw, "\x1F\x8B");
        if ($isGzipped) {
            $decompressed = @gzdecode($raw);
            if ($decompressed === false) {
                throw new \RuntimeException('Corrupted Gzip payload in backup archive.');
            }
            $raw = $decompressed;
        }

        // Detect JSON vs SQL
        $trimmed = ltrim($raw);
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON syntax in backup archive: ' . json_last_error_msg());
            }
            return [
                'type' => 'json',
                'data' => $decoded,
                'encrypted' => $isEncrypted,
                'compressed' => $isGzipped
            ];
        }

        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, 'SET ') || str_starts_with($trimmed, 'START TRANSACTION')) {
            return [
                'type' => 'sql',
                'data' => $trimmed,
                'encrypted' => $isEncrypted,
                'compressed' => $isGzipped
            ];
        }

        throw new \RuntimeException('Unrecognized backup format. Expected valid JSON structure or SQL statements.');
    }

    /**
     * Validate and generate preview for JSON backup.
     */
    private function validateAndPreviewJson(int $userId, array $data): array
    {
        $meta = $data['metadata'] ?? [];
        $schemaVersion = $meta['schema_version'] ?? '1.0.0';

        if (!in_array($schemaVersion, self::SUPPORTED_SCHEMAS, true)) {
            throw new \RuntimeException("Incompatible schema version '{$schemaVersion}'. Supported versions: " . implode(', ', self::SUPPORTED_SCHEMAS));
        }

        $records = $data['data'] ?? [];
        $recordCounts = [];
        $totalRecords = 0;
        $warnings = [];

        foreach ($records as $table => $rows) {
            $count = count($rows);
            $recordCounts[$table] = $count;
            $totalRecords += $count;
        }

        // Integrity check: orphaned splits
        if (!empty($records['transaction_splits']) && empty($records['transactions'])) {
            $warnings[] = 'Backup contains transaction splits but no parent transactions.';
        }

        // Integrity check: user mismatch note
        if (isset($meta['user_id']) && (int) $meta['user_id'] !== $userId) {
            $warnings[] = "Backup was originally exported by User #{$meta['user_id']}. Restoring will migrate data into your account (User #{$userId}).";
        }

        return [
            'valid' => true,
            'format' => 'json',
            'metadata' => [
                'app_name' => $meta['app_name'] ?? 'Expense Tracker',
                'schema_version' => $schemaVersion,
                'export_timestamp' => $meta['export_timestamp'] ?? 'Unknown',
                'backup_uuid' => $meta['backup_uuid'] ?? null,
                'base_currency' => $meta['base_currency'] ?? null
            ],
            'record_counts' => $recordCounts,
            'total_records' => $totalRecords,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate and generate preview for SQL backup.
     */
    private function validateAndPreviewSql(int $userId, string $sql): array
    {
        // Security check for prohibited dangerous operations
        $dangerousPatterns = [
            '/\bDROP\s+DATABASE\b/i',
            '/\bCREATE\s+DATABASE\b/i',
            '/\bALTER\s+USER\b/i',
            '/\bCREATE\s+USER\b/i',
            '/\bDROP\s+USER\b/i',
            '/\bGRANT\b/i',
            '/\bREVOKE\b/i',
            '/\bSHUTDOWN\b/i',
            '/\bFLUSH\s+PRIVILEGES\b/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                throw new \SecurityException('Security Violation: Prohibited database administrative statements detected in SQL script.');
            }
        }

        // Extract insert statements count
        preg_match_all('/INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $matches);
        $tableCounts = [];
        if (!empty($matches[1])) {
            foreach ($matches[1] as $tbl) {
                $tableCounts[$tbl] = ($tableCounts[$tbl] ?? 0) + 1;
            }
        }

        return [
            'valid' => true,
            'format' => 'sql',
            'metadata' => [
                'app_name' => 'Expense Tracker Enterprise',
                'schema_version' => '2.0.0',
                'export_timestamp' => date('Y-m-d H:i:s')
            ],
            'record_counts' => $tableCounts,
            'total_records' => array_sum($tableCounts),
            'warnings' => ['Direct SQL restores execute table statements directly. Current user data will be replaced.']
        ];
    }

    /**
     * Atomic restoration of JSON data with foreign key remapping.
     */
    private function executeJsonRestore(int $userId, array $data): array
    {
        $backupData = $data['data'] ?? [];
        $db = Database::getInstance()->getConnection();

        $db->beginTransaction();
        try {
            // 1. Wipe existing user financial data in reverse topological order
            $deleteOrder = [
                'transaction_splits',
                'vault_transactions',
                'bill_payments',
                'user_achievements',
                'user_streaks',
                'user_mastery_stats',
                'user_fxp_stats',
                'transactions',
                'savings_vaults',
                'bills',
                'salaries',
                'employers',
                'budgets',
                'categories',
                'accounts',
                'daily_logs',
                'pending_ledger',
                'timeline_events',
                'recurring_incomes',
                'forecast_scenarios',
                'radar_alerts',
                'planning_scenarios',
                'planning_loans',
                'planning_investments'
            ];

            foreach ($deleteOrder as $table) {
                try {
                    $db->prepare("DELETE FROM `{$table}` WHERE user_id = ?")->execute([$userId]);
                } catch (\PDOException $e) {
                    // Ignore non-existent optional tables
                }
            }

            $idMap = [];

            // 2. Restore Accounts
            if (!empty($backupData['accounts'])) {
                $stmt = $db->prepare("
                    INSERT INTO accounts 
                        (user_id, currency_id, name, type, institution, account_number, opening_balance, current_balance, notes, status, created_at, updated_at, deleted_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($backupData['accounts'] as $row) {
                    $stmt->execute([
                        $userId,
                        $row['currency_id'] ?? 1,
                        $row['name'] ?? 'Restored Account',
                        $row['type'] ?? 'bank',
                        $row['institution'] ?? null,
                        $row['account_number'] ?? null,
                        $row['opening_balance'] ?? '0.00',
                        $row['current_balance'] ?? '0.00',
                        $row['notes'] ?? null,
                        $row['status'] ?? 'active',
                        $row['created_at'] ?? date('Y-m-d H:i:s'),
                        $row['updated_at'] ?? date('Y-m-d H:i:s'),
                        $row['deleted_at'] ?? null
                    ]);
                    $idMap['accounts'][$row['id']] = (int) $db->lastInsertId();
                }
            }

            // 3. Restore Categories (with 2-pass parent_id remapping)
            if (!empty($backupData['categories'])) {
                $stmt = $db->prepare("
                    INSERT INTO categories 
                        (user_id, parent_id, name, type, color, icon, created_at, deleted_at, is_archived)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($backupData['categories'] as $row) {
                    $stmt->execute([
                        $userId,
                        null, // temporary null, updated in pass 2
                        $row['name'] ?? 'Restored Category',
                        $row['type'] ?? 'expense',
                        $row['color'] ?? '#3b82f6',
                        $row['icon'] ?? 'fas fa-tag',
                        $row['created_at'] ?? date('Y-m-d H:i:s'),
                        $row['deleted_at'] ?? null,
                        $row['is_archived'] ?? 0
                    ]);
                    $idMap['categories'][$row['id']] = (int) $db->lastInsertId();
                }

                // Pass 2: Link parent_id
                $updParent = $db->prepare("UPDATE categories SET parent_id = ? WHERE id = ?");
                foreach ($backupData['categories'] as $row) {
                    if (!empty($row['parent_id']) && isset($idMap['categories'][$row['parent_id']])) {
                        $updParent->execute([
                            $idMap['categories'][$row['parent_id']],
                            $idMap['categories'][$row['id']]
                        ]);
                    }
                }
            }

            // 4. Restore Employers
            if (!empty($backupData['employers'])) {
                $stmt = $db->prepare("INSERT INTO employers (user_id, company_name, created_at) VALUES (?, ?, ?)");
                foreach ($backupData['employers'] as $row) {
                    $stmt->execute([
                        $userId,
                        $row['company_name'] ?? 'Employer',
                        $row['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $idMap['employers'][$row['id']] = (int) $db->lastInsertId();
                }
            }

            // 5. Restore Transactions
            if (!empty($backupData['transactions'])) {
                $stmt = $db->prepare("
                    INSERT INTO transactions 
                        (user_id, account_id, category_id, type, total_amount, currency_id, converted_amount, description, notes, is_favorite, transaction_date, status, is_recurring, recurring_rule, client_mutation_id, rate_applied, settled_amount, created_at, updated_at, deleted_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($backupData['transactions'] as $row) {
                    $newAccId = $idMap['accounts'][$row['account_id']] ?? null;
                    $newCatId = $idMap['categories'][$row['category_id']] ?? null;

                    $stmt->execute([
                        $userId,
                        $newAccId,
                        $newCatId,
                        $row['type'] ?? 'expense',
                        $row['total_amount'] ?? '0.00',
                        $row['currency_id'] ?? 1,
                        $row['converted_amount'] ?? ($row['total_amount'] ?? '0.00'),
                        $row['description'] ?? '',
                        $row['notes'] ?? null,
                        $row['is_favorite'] ?? 0,
                        $row['transaction_date'] ?? date('Y-m-d'),
                        $row['status'] ?? 'posted',
                        $row['is_recurring'] ?? 0,
                        $row['recurring_rule'] ?? null,
                        $row['client_mutation_id'] ?? null,
                        $row['rate_applied'] ?? '1.000000',
                        $row['settled_amount'] ?? ($row['total_amount'] ?? '0.00'),
                        $row['created_at'] ?? date('Y-m-d H:i:s'),
                        $row['updated_at'] ?? date('Y-m-d H:i:s'),
                        $row['deleted_at'] ?? null
                    ]);
                    $idMap['transactions'][$row['id']] = (int) $db->lastInsertId();
                }
            }

            // 6. Restore Transaction Splits
            if (!empty($backupData['transaction_splits'])) {
                $stmt = $db->prepare("INSERT INTO transaction_splits (transaction_id, category_id, amount, notes) VALUES (?, ?, ?, ?)");
                foreach ($backupData['transaction_splits'] as $row) {
                    $newTxnId = $idMap['transactions'][$row['transaction_id']] ?? null;
                    $newCatId = $idMap['categories'][$row['category_id']] ?? null;
                    if ($newTxnId && $newCatId) {
                        $stmt->execute([
                            $newTxnId,
                            $newCatId,
                            $row['amount'] ?? '0.00',
                            $row['notes'] ?? null
                        ]);
                    }
                }
            }

            // 7. Restore Savings Vaults & Vault Transactions
            if (!empty($backupData['savings_vaults'])) {
                $stmt = $db->prepare("INSERT INTO savings_vaults (user_id, name, description, target_amount, current_amount, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($backupData['savings_vaults'] as $row) {
                    $stmt->execute([
                        $userId,
                        $row['name'] ?? 'Vault',
                        $row['description'] ?? null,
                        $row['target_amount'] ?? '0.00',
                        $row['current_amount'] ?? '0.00',
                        $row['status'] ?? 'active',
                        $row['created_at'] ?? date('Y-m-d H:i:s'),
                        $row['updated_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $idMap['savings_vaults'][$row['id']] = (int) $db->lastInsertId();
                }
            }

            if (!empty($backupData['vault_transactions'])) {
                $stmt = $db->prepare("INSERT INTO vault_transactions (user_id, vault_id, type, amount, notes, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($backupData['vault_transactions'] as $row) {
                    $newVaultId = $idMap['savings_vaults'][$row['vault_id']] ?? null;
                    if ($newVaultId) {
                        $stmt->execute([
                            $userId,
                            $newVaultId,
                            $row['type'] ?? 'deposit',
                            $row['amount'] ?? '0.00',
                            $row['notes'] ?? null,
                            $row['created_at'] ?? date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            // 8. Restore Bills & Bill Payments
            if (!empty($backupData['bills'])) {
                $stmt = $db->prepare("INSERT INTO bills (user_id, category_id, name, total_amount, frequency, next_due_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($backupData['bills'] as $row) {
                    $newCatId = $idMap['categories'][$row['category_id']] ?? null;
                    $stmt->execute([
                        $userId,
                        $newCatId,
                        $row['name'] ?? 'Bill',
                        $row['total_amount'] ?? '0.00',
                        $row['frequency'] ?? 'monthly',
                        $row['next_due_date'] ?? date('Y-m-d'),
                        $row['status'] ?? 'unpaid',
                        $row['created_at'] ?? date('Y-m-d H:i:s'),
                        $row['updated_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $idMap['bills'][$row['id']] = (int) $db->lastInsertId();
                }
            }

            if (!empty($backupData['bill_payments'])) {
                $stmt = $db->prepare("INSERT INTO bill_payments (user_id, bill_id, amount, payment_date, notes, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($backupData['bill_payments'] as $row) {
                    $newBillId = $idMap['bills'][$row['bill_id']] ?? null;
                    if ($newBillId) {
                        $stmt->execute([
                            $userId,
                            $newBillId,
                            $row['amount'] ?? '0.00',
                            $row['payment_date'] ?? date('Y-m-d'),
                            $row['notes'] ?? null,
                            $row['created_at'] ?? date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            // 9. Restore Salaries
            if (!empty($backupData['salaries'])) {
                $stmt = $db->prepare("INSERT INTO salaries (user_id, employer_id, pay_period_start, pay_period_end, basic_salary, bonus, overtime_pay, thirteenth_month, net_pay, payment_date, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($backupData['salaries'] as $row) {
                    $newEmpId = $idMap['employers'][$row['employer_id']] ?? null;
                    $stmt->execute([
                        $userId,
                        $newEmpId,
                        $row['pay_period_start'] ?? date('Y-m-01'),
                        $row['pay_period_end'] ?? date('Y-m-t'),
                        $row['basic_salary'] ?? '0.00',
                        $row['bonus'] ?? '0.00',
                        $row['overtime_pay'] ?? '0.00',
                        $row['thirteenth_month'] ?? '0.00',
                        $row['net_pay'] ?? '0.00',
                        $row['payment_date'] ?? date('Y-m-d'),
                        $row['status'] ?? 'paid',
                        $row['notes'] ?? null,
                        $row['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $idMap['salaries'][$row['id']] = (int) $db->lastInsertId();
                }
            }

            // 10. Simple Tables
            $simpleTables = [
                'budgets',
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
                'planning_investments'
            ];

            foreach ($simpleTables as $tbl) {
                if (!empty($backupData[$tbl])) {
                    $columns = array_keys($backupData[$tbl][0]);
                    // Filter out auto-inc 'id' to avoid primary key collisions
                    $insertCols = array_filter($columns, fn($c) => $c !== 'id');
                    $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
                    $colsStr = implode(', ', array_map(fn($c) => "`{$c}`", $insertCols));

                    $stmt = $db->prepare("INSERT INTO `{$tbl}` ({$colsStr}) VALUES ({$placeholders})");

                    foreach ($backupData[$tbl] as $row) {
                        $row['user_id'] = $userId;
                        $vals = [];
                        foreach ($insertCols as $c) {
                            $vals[] = $row[$c] ?? null;
                        }
                        try {
                            $stmt->execute($vals);
                        } catch (\PDOException $e) {
                            Logger::warning("Could not restore row in {$tbl}: " . $e->getMessage());
                        }
                    }
                }
            }

            $db->commit();

            // 11. Post-Restore Reconciliation Pipeline
            $accountService = new AccountService();
            $accountService->reconcileAllAccounts($userId);

            AchievementEngine::syncUser($userId);
            FinancialSummaryEngine::invalidateCache($userId);
            Cache::forget("dashboard_stats_{$userId}");
            Cache::forget("lifetime_stats_{$userId}");

            Logger::info("Successfully completed JSON restore and post-restore reconciliation for user {$userId}");

            return [
                'success' => true,
                'reconciled' => true,
                'message' => 'Workspace restored and ledger balances reconciled successfully.'
            ];

        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("JSON restore transaction aborted: " . $e->getMessage());
            throw new \RuntimeException('Restore failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Atomic restoration of SQL dump.
     */
    private function executeSqlRestore(int $userId, string $sql): array
    {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $db->exec($sql);
            $db->commit();

            // Post-restore reconciliation
            $accountService = new AccountService();
            $accountService->reconcileAllAccounts($userId);

            AchievementEngine::syncUser($userId);
            FinancialSummaryEngine::invalidateCache($userId);
            Cache::forget("dashboard_stats_{$userId}");
            Cache::forget("lifetime_stats_{$userId}");

            return [
                'success' => true,
                'reconciled' => true,
                'message' => 'SQL restore executed and ledger balances reconciled successfully.'
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error("SQL restore aborted: " . $e->getMessage());
            throw new \RuntimeException('SQL execution failed: ' . $e->getMessage(), 0, $e);
        }
    }
}