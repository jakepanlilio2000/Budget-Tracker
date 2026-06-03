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
        $db = Database::getInstance();
        
        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'profiles' => $db->query("SELECT * FROM profiles")->fetchAll(),
            'categories' => $db->query("SELECT * FROM categories")->fetchAll(),
            'entries' => $db->query("SELECT * FROM entries")->fetchAll(),
            'entry_frequencies' => $db->query("SELECT * FROM entry_frequencies")->fetchAll(),
            'transactions' => $db->query("SELECT * FROM transactions")->fetchAll(),
            'savings_goals' => $db->query("SELECT * FROM savings_goals")->fetchAll()
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="BudgetSuite_Master_Backup_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    public function masterRestore(): void {
        $db = Database::getInstance();

        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $json = file_get_contents($_FILES['backup_file']['tmp_name']);
            $data = json_decode($json, true);

            if ($data && isset($data['profiles'])) {
                try {
                    $db->beginTransaction();
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $tables = ['transactions', 'entry_frequencies', 'entries', 'savings_goals', 'categories', 'calculator_sessions', 'profiles'];
                    foreach ($tables as $table) {
                        try { $db->exec("DELETE FROM `$table`"); } catch (\Exception $e) { }
                    }

                    $insertTables = ['profiles', 'categories', 'savings_goals', 'entries', 'entry_frequencies', 'transactions'];
                    foreach ($insertTables as $table) {
                        if (!empty($data[$table])) {
                            foreach ($data[$table] as $row) {
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
                    die("Database Error during Restore: " . $e->getMessage());
                }
            }
        }
        
        $this->redirect('/');
    }
    public function security(): void {
        $this->view('system/security');
    }
}