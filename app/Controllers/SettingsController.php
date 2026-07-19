<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Core\Cache;
use App\Core\Logger;
use App\Models\User;
use App\Services\BackupService;
use \App\Services\FinancialSummaryEngine;
use \App\Services\AchievementEngine;
class SettingsController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }

    public function index(): void
    {
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM backup_history WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $backupHistory = $stmt->fetchAll();

        $this->view('settings.index', [
            'backupHistory' => $backupHistory
        ]);
    }
    public function backup(): void
    {
        $format = strtolower($_GET['format'] ?? 'json');
        $userId = Auth::id();
        $backupService = new BackupService();

        // Rate Limiting
        // $lastBackup = \App\Core\Session::get('last_backup_time');
        // if ($lastBackup && (time() - $lastBackup) < 60) {
        //     \App\Core\Session::set('error', 'Please wait a moment before generating another backup.');
        //     $this->redirect('/settings');
        // }
        Session::set('last_backup_time', time());

        try {
            if ($format === 'json') {
                $result = $backupService->generateBackup($userId, 'json');
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
                header('X-Backup-Checksum: ' . $result['checksum']);
                header('X-Backup-UUID: ' . $result['uuid']);
                readfile($result['filepath']);
                unlink($result['filepath']);
                exit;
            } elseif ($format === 'zip' || $format === 'csv') {
                $result = $backupService->generateZipCsv($userId);
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
                readfile($result['filepath']);
                unlink($result['filepath']);
                exit;
            } elseif ($format === 'xlsx') {
                $result = $backupService->generateXlsx($userId);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
                readfile($result['filepath']);
                unlink($result['filepath']);
                exit;
            } elseif ($format === 'pdf') {
                $result = $backupService->generatePdf($userId);
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
                readfile($result['filepath']);
                unlink($result['filepath']);
                exit;
            } elseif ($format === 'html') {
                $result = $backupService->generateHtml($userId);
                header('Content-Type: text/html; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
                readfile($result['filepath']);
                unlink($result['filepath']);
                exit;
            }

            Session::set('error', 'Unsupported export format.');
            $this->redirect('/settings');

        } catch (\Exception $e) {
            Logger::error('Backup generation failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            Session::set('error', 'Failed to generate backup: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    public function previewRestore(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $file = $_FILES['backup_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'error' => 'No file uploaded or upload failed.'], 400);
        }

        if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            $this->json(['success' => false, 'error' => 'Only JSON backups are supported for preview.'], 400);
        }

        try {
            $restoreService = new \App\Services\RestoreService();
            $preview = $restoreService->validateAndPreview($userId, $file['tmp_name']);
            $this->json(['success' => true, 'preview' => $preview]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function executeRestore(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $password = $_POST['confirm_password'] ?? '';
        $file = $_FILES['backup_file'] ?? null;
        $user = User::findById($userId);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::set('error', 'Incorrect password. Restore cancelled for security.');
            $this->redirect('/settings');
        }

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::set('error', 'No file uploaded or upload failed.');
            $this->redirect('/settings');
        }

        try {
            $restoreService = new \App\Services\RestoreService();
            $restoreService->executeRestore($userId, $file['tmp_name']);

            Session::set('success', 'Workspace restored successfully! All data has been updated.');
            $this->redirect('/dashboard');
        } catch (\Exception $e) {
            Session::set('error', 'Restore failed: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    public function restore(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $password = $_POST['confirm_password'] ?? '';
        $file = $_FILES['backup_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::set('error', 'No file uploaded or upload failed.');
            $this->redirect('/settings');
        }

        if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            Session::set('error', 'Invalid file type. Please upload a .json backup file.');
            $this->redirect('/settings');
        }

        $user = User::findById($userId);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::set('error', 'Incorrect password. Restore cancelled for security.');
            $this->redirect('/settings');
        }

        $jsonContent = file_get_contents($file['tmp_name']);
        $data = json_decode($jsonContent, true);

        if (!$data || !isset($data['user_id']) || $data['user_id'] !== $userId) {
            Session::set('error', 'Invalid or corrupted backup file. User ID mismatch.');
            $this->redirect('/settings');
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            $deleteOrder = [
                'user_achievements',
                'user_streaks',
                'user_mastery_stats',
                'user_fxp_stats',
                'transaction_tags',
                'transaction_splits',
                'transactions',
                'vault_transactions',
                'savings_vaults',
                'bill_payments',
                'bills',
                'salaries',
                'employers',
                'budgets',
                'categories',
                'accounts',
                'tags',
                'daily_logs',
                'pending_ledger',
                'radar_alerts',
                'timeline_events'
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

            if (!empty($data['transactions'])) {
                $stmt = $db->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, total_amount, currency_id, converted_amount, description, notes, is_favorite, transaction_date, status, is_recurring, recurring_rule, created_at, updated_at, deleted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['transactions'] as $row) {
                    $newAccountId = $idMap['accounts'][$row['account_id']] ?? null;
                    $newCategoryId = $idMap['categories'][$row['category_id']] ?? null;
                    $stmt->execute([$userId, $newAccountId, $newCategoryId, $row['type'], $row['total_amount'], $row['currency_id'], $row['converted_amount'], $row['description'], $row['notes'], $row['is_favorite'], $row['transaction_date'], $row['status'], $row['is_recurring'], $row['recurring_rule'], $row['created_at'], $row['updated_at'], $row['deleted_at']]);
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

            $simpleInserts = [
                'tags',
                'budgets',
                'employers',
                'salaries',
                'bills',
                'bill_payments',
                'savings_vaults',
                'vault_transactions',
                'daily_logs',
                'pending_ledger',
                'radar_alerts',
                'user_fxp_stats',
                'user_mastery_stats',
                'user_streaks',
                'user_achievements'
            ];

            foreach ($simpleInserts as $table) {
                if (!empty($data[$table])) {
                    $columns = array_keys($data[$table][0]);
                    $placeholders = implode(',', array_fill(0, count($columns), '?'));
                    $colsStr = implode(',', $columns);
                    $stmt = $db->prepare("INSERT INTO `$table` ($colsStr) VALUES ($placeholders)");
                    foreach ($data[$table] as $row) {
                        $row['user_id'] = $userId;
                        $values = [];
                        foreach ($columns as $col) {
                            $values[] = $row[$col];
                        }
                        $stmt->execute($values);
                        if (isset($row['id'])) {
                            $idMap[$table][$row['id']] = $db->lastInsertId();
                        }
                    }
                }
            }

            $db->commit();
            Cache::forget("dashboard_stats_{$userId}");
            Cache::forget("lifetime_stats_{$userId}");
            Logger::info("Data restored successfully", ['user_id' => $userId]);
            Session::set('success', 'Data restored successfully! All old data has been replaced.');
            $this->redirect('/settings');

        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error("Restore failed", ['user_id' => $userId, 'error' => $e->getMessage()]);
            Session::set('error', 'Restore failed: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    public function deleteAll(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $password = $_POST['confirm_password'] ?? '';

        $user = User::findById($userId);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::set('error', 'Incorrect password. Delete operation cancelled.');
            $this->redirect('/settings');
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {

            $tablesToDelete = [
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


            foreach (array_reverse($tablesToDelete) as $table) {
                $db->prepare("DELETE FROM `$table` WHERE user_id = ?")->execute([$userId]);
            }

            $db->commit();

            Cache::forget("dashboard_stats_{$userId}");
            Cache::forget("lifetime_stats_{$userId}");
            FinancialSummaryEngine::invalidateCache($userId);
            AchievementEngine::syncUser($userId);

            Logger::info("User deleted all financial data", ['user_id' => $userId]);
            Session::set('success', 'All your financial data has been safely deleted. Your preferences and backup history remain intact.');
            $this->redirect('/settings');

        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error("Safe delete failed", ['user_id' => $userId, 'error' => $e->getMessage()]);
            Session::set('error', 'Failed to delete data: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }
}