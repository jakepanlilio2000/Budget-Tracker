<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Cache;
use App\Services\AchievementEngine;
use App\Services\FinancialSummaryEngine;
class RestoreService
{
    public function validateAndPreview(int $userId, string $filePath): array
    {
        $jsonContent = file_get_contents($filePath);
        if ($jsonContent === false)
            throw new \Exception('Failed to read backup file.');

        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE)
            throw new \Exception('Backup file is corrupted or not valid JSON.');

        if (!isset($data['metadata']) || !isset($data['data'])) {
            throw new \Exception('Invalid backup structure: missing metadata or data.');
        }

        $meta = $data['metadata'];
        if ($meta['schema_version'] !== '1.0.0') {
            throw new \Exception('Incompatible schema version: ' . $meta['schema_version']);
        }

        $recordCounts = [];
        $totalRecords = 0;
        foreach ($data['data'] as $table => $rows) {
            $count = count($rows);
            $recordCounts[$table] = $count;
            $totalRecords += $count;
        }

        return [
            'valid' => true,
            'metadata' => $meta,
            'record_counts' => $recordCounts,
            'total_records' => $totalRecords,
            'warnings' => []
        ];
    }

    public function executeRestore(int $userId, string $filePath): void
    {
        $jsonContent = file_get_contents($filePath);
        $backup = json_decode($jsonContent, true);
        $data = $backup['data'];
        $db = Database::getInstance()->getConnection();

        $db->beginTransaction();
        try {
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
                'planning_investments',
                'user_preferences'
            ];
            foreach ($deleteOrder as $table) {
                $db->prepare("DELETE FROM `$table` WHERE user_id = ?")->execute([$userId]);
            }

            $idMap = [];

            if (!empty($data['accounts'])) {
                $stmt = $db->prepare("INSERT INTO accounts (user_id, currency_id, name, type, institution, account_number, opening_balance, current_balance, notes, status, created_at, updated_at, deleted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['accounts'] as $row) {
                    $stmt->execute([$userId, $row['currency_id'], $row['name'], $row['type'], $row['institution'], $row['account_number'], $row['opening_balance'], $row['current_balance'], $row['notes'], $row['status'], $row['created_at'], $row['updated_at'], $row['deleted_at']]);
                    $idMap['accounts'][$row['id']] = $db->lastInsertId();
                }
            }

            if (!empty($data['categories'])) {
                $stmt = $db->prepare("INSERT INTO categories (user_id, parent_id, name, type, color, icon, created_at, deleted_at, is_archived) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['categories'] as $row) {
                    $stmt->execute([$userId, $row['parent_id'], $row['name'], $row['type'], $row['color'], $row['icon'], $row['created_at'], $row['deleted_at'], $row['is_archived'] ?? 0]);
                    $idMap['categories'][$row['id']] = $db->lastInsertId();
                }
                foreach ($data['categories'] as $row) {
                    if (!empty($row['parent_id']) && isset($idMap['categories'][$row['parent_id']])) {
                        $db->prepare("UPDATE categories SET parent_id = ? WHERE id = ?")->execute([$idMap['categories'][$row['parent_id']], $idMap['categories'][$row['id']]]);
                    }
                }
            }

            if (!empty($data['employers'])) {
                $stmt = $db->prepare("INSERT INTO employers (user_id, company_name, created_at) VALUES (?, ?, ?)");
                foreach ($data['employers'] as $row) {
                    $stmt->execute([$userId, $row['company_name'], $row['created_at']]);
                    $idMap['employers'][$row['id']] = $db->lastInsertId();
                }
            }

            if (!empty($data['transactions'])) {
                $stmt = $db->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, total_amount, currency_id, converted_amount, description, notes, is_favorite, transaction_date, status, is_recurring, recurring_rule, created_at, updated_at, deleted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['transactions'] as $row) {
                    $newAccountId = $idMap['accounts'][$row['account_id']] ?? null;
                    $newCategoryId = $idMap['categories'][$row['category_id']] ?? null;
                    $stmt->execute([$userId, $newAccountId, $newCategoryId, $row['type'], $row['total_amount'], $row['currency_id'], $row['converted_amount'] ?? 0, $row['description'], $row['notes'], $row['is_favorite'] ?? 0, $row['transaction_date'], $row['status'], $row['is_recurring'] ?? 0, $row['recurring_rule'] ?? null, $row['created_at'], $row['updated_at'], $row['deleted_at']]);
                    $idMap['transactions'][$row['id']] = $db->lastInsertId();
                }
            }

            if (!empty($data['transaction_splits'])) {
                $stmt = $db->prepare("INSERT INTO transaction_splits (transaction_id, category_id, amount, notes) VALUES (?, ?, ?, ?)");
                foreach ($data['transaction_splits'] as $row) {
                    $newTxnId = $idMap['transactions'][$row['transaction_id']] ?? null;
                    $newCatId = $idMap['categories'][$row['category_id']] ?? null;
                    if ($newTxnId && $newCatId) {
                        $stmt->execute([$newTxnId, $newCatId, $row['amount'], $row['notes']]);
                    }
                }
            }

            if (!empty($data['savings_vaults'])) {
                $stmt = $db->prepare("INSERT INTO savings_vaults (user_id, name, description, target_amount, current_amount, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['savings_vaults'] as $row) {
                    $stmt->execute([$userId, $row['name'], $row['description'], $row['target_amount'], $row['current_amount'], $row['status'], $row['created_at'], $row['updated_at']]);
                    $idMap['savings_vaults'][$row['id']] = $db->lastInsertId();
                }
            }

            if (!empty($data['vault_transactions'])) {
                $stmt = $db->prepare("INSERT INTO vault_transactions (user_id, vault_id, type, amount, notes, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['vault_transactions'] as $row) {
                    $newVaultId = $idMap['savings_vaults'][$row['vault_id']] ?? null;
                    if ($newVaultId) {
                        $stmt->execute([$userId, $newVaultId, $row['type'], $row['amount'], $row['notes'], $row['created_at']]);
                    }
                }
            }

            if (!empty($data['bills'])) {
                $stmt = $db->prepare("INSERT INTO bills (user_id, category_id, name, total_amount, frequency, next_due_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['bills'] as $row) {
                    $newCatId = $idMap['categories'][$row['category_id']] ?? null;
                    $stmt->execute([$userId, $newCatId, $row['name'], $row['total_amount'], $row['frequency'], $row['next_due_date'], $row['status'], $row['created_at'], $row['updated_at']]);
                    $idMap['bills'][$row['id']] = $db->lastInsertId();
                }
            }

            if (!empty($data['bill_payments'])) {
                $stmt = $db->prepare("INSERT INTO bill_payments (user_id, bill_id, amount, payment_date, notes, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['bill_payments'] as $row) {
                    $newBillId = $idMap['bills'][$row['bill_id']] ?? null;
                    if ($newBillId) {
                        $stmt->execute([$userId, $newBillId, $row['amount'], $row['payment_date'], $row['notes'], $row['created_at']]);
                    }
                }
            }

            if (!empty($data['salaries'])) {
                $stmt = $db->prepare("INSERT INTO salaries (user_id, employer_id, pay_period_start, pay_period_end, basic_salary, bonus, overtime_pay, thirteenth_month, net_pay, payment_date, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['salaries'] as $row) {
                    $newEmployerId = $idMap['employers'][$row['employer_id']] ?? null;
                    $stmt->execute([$userId, $newEmployerId, $row['pay_period_start'], $row['pay_period_end'], $row['basic_salary'], $row['bonus'], $row['overtime_pay'], $row['thirteenth_month'], $row['net_pay'], $row['payment_date'], $row['status'], $row['notes'], $row['created_at']]);
                    $idMap['salaries'][$row['id']] = $db->lastInsertId();
                }
            }

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
                'planning_investments',
                'user_preferences'
            ];

            foreach ($simpleTables as $table) {
                if (!empty($data[$table])) {
                    $columns = array_keys($data[$table][0]);
                    $placeholders = implode(',', array_fill(0, count($columns), '?'));
                    $colsStr = implode(',', $columns);
                    $stmt = $db->prepare("INSERT INTO `$table` ($colsStr) VALUES ($placeholders)");

                    foreach ($data[$table] as $row) {
                        $row['user_id'] = $userId;

                        $values = [];
                        foreach ($columns as $col) {
                            $values[] = $row[$col] ?? null;
                        }
                        $stmt->execute($values);
                    }
                }
            }

            $uuid = $backup['metadata']['backup_uuid'] ?? null;
            if ($uuid) {
                $db->prepare("UPDATE backup_history SET restore_status = 'success', restored_at = NOW() WHERE backup_uuid = ? AND user_id = ?")
                    ->execute([$uuid, $userId]);
            }

            $db->commit();
            $this->rebuildUserState($userId);

        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error('Restore failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw new \Exception('Restore failed: ' . $e->getMessage());
        }
    }

    private function rebuildUserState(int $userId): void
    {
        Cache::forget("dashboard_stats_{$userId}");
        Cache::forget("lifetime_stats_{$userId}");
        AchievementEngine::syncUser($userId);
        FinancialSummaryEngine::invalidateCache($userId);
    }
}