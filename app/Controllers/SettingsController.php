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
        $passphrase = $_GET['passphrase'] ?? $_POST['passphrase'] ?? null;
        if ($passphrase !== null) {
            $passphrase = trim((string) $passphrase);
            if ($passphrase === '') {
                $passphrase = null;
            }
        }

        $userId = Auth::id();
        $backupService = new BackupService();
        Session::set('last_backup_time', time());

        try {
            // Mode 2: Full System Vault Backup (.json.gz and .sql.gz)
            if ($format === 'vault_json_gz' || $format === 'json.gz') {
                $vaultService = new \App\Services\BackupVaultService();
                $vaultService->downloadVaultBackup($userId, 'json.gz');
                exit;
            } elseif ($format === 'vault_sql_gz' || $format === 'sql.gz') {
                $vaultService = new \App\Services\BackupVaultService();
                $vaultService->downloadVaultBackup($userId, 'sql.gz');
                exit;
            }

            // Direct memory-bounded streaming endpoints
            if ($format === 'stream_csv') {
                $exportService = new \App\Services\ExportService();
                $exportService->streamTransactionsCsv($userId);
                exit;
            } elseif ($format === 'stream_json') {
                $exportService = new \App\Services\ExportService();
                $exportService->streamJsonV2($userId);
                exit;
            }

            $result = $backupService->generateBackup($userId, $format, $passphrase);

            \App\Services\ExportService::cleanOutputBuffer();

            if (str_ends_with($result['filename'], '.enc')) {
                header('Content-Type: application/octet-stream');
            } elseif (str_ends_with($result['filename'], '.gz')) {
                header('Content-Type: application/gzip');
            } elseif (str_ends_with($result['filename'], '.sql')) {
                header('Content-Type: application/sql; charset=utf-8');
            } elseif (str_ends_with($result['filename'], '.json')) {
                header('Content-Type: application/json; charset=utf-8');
            } elseif (str_ends_with($result['filename'], '.zip')) {
                header('Content-Type: application/zip');
            } elseif (str_ends_with($result['filename'], '.xlsx')) {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            } elseif (str_ends_with($result['filename'], '.pdf')) {
                header('Content-Type: application/pdf');
            } elseif (str_ends_with($result['filename'], '.html')) {
                header('Content-Type: text/html; charset=utf-8');
            } else {
                header('Content-Type: application/octet-stream');
            }

            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('X-Backup-Checksum: ' . ($result['checksum'] ?? ''));
            if (!empty($result['uuid'])) {
                header('X-Backup-UUID: ' . $result['uuid']);
            }
            readfile($result['filepath']);
            @unlink($result['filepath']);
            exit;

        } catch (\Throwable $e) {
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
        $passphrase = $_POST['passphrase'] ?? null;
        if ($passphrase !== null) {
            $passphrase = trim((string) $passphrase);
            if ($passphrase === '') {
                $passphrase = null;
            }
        }

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'error' => 'No file uploaded or upload failed.'], 400);
        }

        try {
            $restoreService = new \App\Services\RestoreService();
            $preview = $restoreService->validateAndPreview($userId, $file['tmp_name'], $passphrase);
            $this->json(['success' => true, 'preview' => $preview]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function executeRestore(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $password = $_POST['confirm_password'] ?? '';
        $backupPassphrase = $_POST['passphrase'] ?? null;
        if ($backupPassphrase !== null) {
            $backupPassphrase = trim((string) $backupPassphrase);
            if ($backupPassphrase === '') {
                $backupPassphrase = null;
            }
        }

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
            $result = $restoreService->executeRestore($userId, $file['tmp_name'], $backupPassphrase);

            Session::set('success', 'Workspace restored successfully! ' . ($result['message'] ?? 'All data has been updated.'));
            $this->redirect('/settings');
        } catch (\Throwable $e) {
            Session::set('error', 'Restore failed: ' . $e->getMessage());
            $this->redirect('/settings');
        }
    }

    public function restore(): void
    {
        $this->executeRestore();
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