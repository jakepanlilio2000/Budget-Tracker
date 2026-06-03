<?php
namespace controllers;
use core\Controller;
use config\Database;

class GlobalController extends Controller {
    
    // Serve the Compound Interest View
    public function compound(): void {
        $this->view('tools/compound');
    }

    // Serve the Loan Amortization View
    public function loan(): void {
        $this->view('tools/loan');
    }

    // Export the ENTIRE database across all profiles
    public function masterBackup(): void {
        $db = \config\Database::getInstance();
        $userId = $_SESSION['user_id'];
        
        $profiles = $db->query("SELECT * FROM profiles WHERE user_id = $userId")->fetchAll();
        
        if (empty($profiles)) {
            $this->redirect('/system/security');
            return;
        }

        $pids = implode(',', array_column($profiles, 'id'));
        
        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'profiles' => $profiles,
            'categories' => $db->query("SELECT * FROM categories WHERE profile_id IN ($pids)")->fetchAll(),
            'entries' => $db->query("SELECT * FROM entries WHERE profile_id IN ($pids)")->fetchAll(),
            'transactions' => $db->query("SELECT * FROM transactions WHERE profile_id IN ($pids)")->fetchAll(),
            'shopping_log' => $db->query("SELECT * FROM shopping_log WHERE profile_id IN ($pids)")->fetchAll(),
            'income_log' => $db->query("SELECT * FROM income_log WHERE profile_id IN ($pids)")->fetchAll()
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="BudgetSuite_MyData_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    public function masterRestore(): void {
        $db = \config\Database::getInstance();
        $userId = $_SESSION['user_id'];

        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $json = file_get_contents($_FILES['backup_file']['tmp_name']);
            $data = json_decode($json, true);

            if ($data && isset($data['profiles'])) {
                try {
                    $db->beginTransaction();
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $db->exec("DELETE FROM profiles WHERE user_id = $userId");

                    $insertTables = ['profiles', 'categories', 'entries', 'transactions', 'shopping_log', 'income_log'];
                    foreach ($insertTables as $table) {
                        if (!empty($data[$table])) {
                            foreach ($data[$table] as $row) {
                                if ($table === 'profiles') $row['user_id'] = $userId;
                                
                                $columns = implode('`, `', array_keys($row));
                                $placeholders = implode(', ', array_fill(0, count($row), '?'));
                                
                                $stmt = $db->prepare("INSERT INTO `$table` (`$columns`) VALUES ($placeholders)");
                                $stmt->execute(array_values($row));
                            }
                        }
                    }

                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $db->commit();
                } catch (\Exception $e) {
                    $db->rollBack();
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                    die("Restore Error: " . $e->getMessage());
                }
            }
        }
        $this->redirect('/');
    }
    public function security(): void {
        $this->view('system/security');
    }
}