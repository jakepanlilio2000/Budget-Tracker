<?php
namespace controllers;
use core\Controller;
use config\Database;
use models\Profile;

class BackupController extends Controller {
    public function index(int $profile_id): void {
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile) $this->redirect('/');

        $this->view('backups/index', ['profile' => $profile]);
    }

    public function exportExcel(int $profile_id): void {
        $db = \config\Database::getInstance();
        $profileModel = new \models\Profile();
        $profile = $profileModel->find($profile_id);

        $stmt = $db->prepare("
            SELECT t.period_date, t.name, c.name as category, t.type, t.amount 
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.profile_id = :pid
            ORDER BY t.period_date DESC
        ");
        $stmt->execute(['pid' => $profile_id]);
        $data = $stmt->fetchAll();

        // 1. Process and Group Data by Month
        $months = [];
        $globalInflow = 0.0;
        $globalOutflow = 0.0;

        foreach ($data as $row) {
            $monthName = date('F Y', strtotime($row['period_date'])); // e.g., "May 2026"
            $day = (int)date('d', strtotime($row['period_date']));

            // Initialize month if it doesn't exist yet
            if (!isset($months[$monthName])) {
                $months[$monthName] = [
                    'inflow' => 0.0, 
                    'outflow' => 0.0,
                    'first_half' => 0.0, 
                    'second_half' => 0.0,
                    'items' => []
                ];
            }

            $amt = (float)$row['amount'];
            if ($row['type'] === 'inflow') {
                $months[$monthName]['inflow'] += $amt;
                $globalInflow += $amt;
            } else {
                $months[$monthName]['outflow'] += $amt;
                $globalOutflow += $amt;
                
                // Calculate 15th / 30th splits for outflows
                if ($day <= 15) {
                    $months[$monthName]['first_half'] += $amt;
                } else {
                    $months[$monthName]['second_half'] += $amt;
                }
            }
            // Store transaction for the ledger
            $months[$monthName]['items'][] = $row;
        }

        $currency = $profile['currency'] ?? '';

        // 2. Output Excel XML Headers
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Budget_Master_Report_' . date('Y-m-d') . '.xls"');

        echo '<?xml version="1.0"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // 3. Define Visual Styles for Excel
        echo '<Styles>' . "\n";
        echo '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14" ss:Color="#FFFFFF"/><Interior ss:Color="#161b22" ss:Pattern="Solid"/></Style>' . "\n";
        echo '<Style ss:ID="Subtitle"><Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF"/><Interior ss:Color="#30363d" ss:Pattern="Solid"/></Style>' . "\n";
        echo '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#4F7BF7" ss:Pattern="Solid"/></Style>' . "\n";
        echo '<Style ss:ID="Bold"><Font ss:Bold="1"/></Style>' . "\n";
        echo '<Style ss:ID="Inflow"><Font ss:Color="#1a7f37"/><NumberFormat ss:Format="&quot;' . $currency . '&quot;\ #,##0.00"/></Style>' . "\n";
        echo '<Style ss:ID="Outflow"><Font ss:Color="#d1242f"/><NumberFormat ss:Format="&quot;' . $currency . '&quot;\ #,##0.00"/></Style>' . "\n";
        echo '<Style ss:ID="Net"><Font ss:Bold="1"/><NumberFormat ss:Format="&quot;' . $currency . '&quot;\ #,##0.00"/></Style>' . "\n";
        echo '<Style ss:ID="Date"><NumberFormat ss:Format="yyyy\-mm\-dd"/></Style>' . "\n";
        echo '</Styles>' . "\n";

        echo '<Worksheet ss:Name="Master Report">' . "\n";
        echo '<Table>' . "\n";

        // Set strict column widths so it's instantly readable
        echo '<Column ss:Width="100"/>' . "\n";
        echo '<Column ss:Width="240"/>' . "\n";
        echo '<Column ss:Width="140"/>' . "\n";
        echo '<Column ss:Width="140"/>' . "\n";
        echo '<Column ss:Width="120"/>' . "\n";

        // 4. Global Summary Block (Top of Spreadsheet)
        echo '<Row><Cell ss:StyleID="Title" ss:MergeAcross="4"><Data ss:Type="String">GLOBAL FINANCIAL SUMMARY - ' . htmlspecialchars($profile['name']) . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Total Lifetime Inflow:</Data></Cell><Cell ss:StyleID="Inflow"><Data ss:Type="Number">' . $globalInflow . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Total Lifetime Outflow:</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $globalOutflow . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Cumulative Net Wealth:</Data></Cell><Cell ss:StyleID="Net"><Data ss:Type="Number">' . ($globalInflow - $globalOutflow) . '</Data></Cell></Row>' . "\n";
        echo '<Row></Row>' . "\n"; // Empty row spacing

        // 5. Monthly Breakdowns & Ledgers
        foreach ($months as $monthName => $m) {
            
            // Month Title Bar
            echo '<Row><Cell ss:StyleID="Subtitle" ss:MergeAcross="4"><Data ss:Type="String">' . strtoupper($monthName) . ' REPORT</Data></Cell></Row>' . "\n";
            
            // Subtotals & Splits (Left Side: Totals | Right Side: 15th/30th splits)
            echo '<Row>' . "\n";
            echo '<Cell ss:StyleID="Bold"><Data ss:Type="String">Total Inflow:</Data></Cell><Cell ss:StyleID="Inflow"><Data ss:Type="Number">' . $m['inflow'] . '</Data></Cell>' . "\n";
            echo '<Cell ss:Index="4" ss:StyleID="Bold"><Data ss:Type="String">1st Half Expenses (1st-15th):</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $m['first_half'] . '</Data></Cell>' . "\n";
            echo '</Row>' . "\n";
            
            echo '<Row>' . "\n";
            echo '<Cell ss:StyleID="Bold"><Data ss:Type="String">Total Outflow:</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $m['outflow'] . '</Data></Cell>' . "\n";
            echo '<Cell ss:Index="4" ss:StyleID="Bold"><Data ss:Type="String">2nd Half Expenses (16th-End):</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $m['second_half'] . '</Data></Cell>' . "\n";
            echo '</Row>' . "\n";

            echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Net Savings:</Data></Cell><Cell ss:StyleID="Net"><Data ss:Type="Number">' . ($m['inflow'] - $m['outflow']) . '</Data></Cell></Row>' . "\n";
            
            // Monthly Ledger Headers
            echo '<Row>' . "\n";
            echo '<Cell ss:StyleID="Header"><Data ss:Type="String">Date</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="Header"><Data ss:Type="String">Transaction Name</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="Header"><Data ss:Type="String">Category</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="Header"><Data ss:Type="String">Type</Data></Cell>' . "\n";
            echo '<Cell ss:StyleID="Header"><Data ss:Type="String">Amount</Data></Cell>' . "\n";
            echo '</Row>' . "\n";

            // Monthly Ledger Data Rows
            foreach ($m['items'] as $row) {
                $styleAmount = $row['type'] === 'inflow' ? 'Inflow' : 'Outflow';
                $cleanName = htmlspecialchars($row['name'], ENT_XML1, 'UTF-8');
                $cleanCat = htmlspecialchars($row['category'] ?? 'Uncategorized', ENT_XML1, 'UTF-8');
                
                echo '<Row>' . "\n";
                echo '<Cell ss:StyleID="Date"><Data ss:Type="String">' . $row['period_date'] . '</Data></Cell>' . "\n";
                echo '<Cell><Data ss:Type="String">' . $cleanName . '</Data></Cell>' . "\n";
                echo '<Cell><Data ss:Type="String">' . $cleanCat . '</Data></Cell>' . "\n";
                echo '<Cell><Data ss:Type="String">' . ucfirst($row['type']) . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $styleAmount . '"><Data ss:Type="Number">' . $row['amount'] . '</Data></Cell>' . "\n";
                echo '</Row>' . "\n";
            }
            
            echo '<Row></Row><Row></Row>' . "\n"; // Double space before the next month starts
        }

        echo '</Table>' . "\n";
        echo '</Worksheet>' . "\n";
        echo '</Workbook>' . "\n";
        exit;
    }

    public function exportJson(int $profile_id): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM transactions WHERE profile_id = :pid");
        $stmt->execute(['pid' => $profile_id]);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="budget_backup_' . date('Y-m-d') . '.json"');
        echo json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT);
        exit;
    }

    public function wipeData(int $profile_id): void {
        $this->checkCsrf();
        $db = Database::getInstance();
        $db->prepare("DELETE FROM transactions WHERE profile_id = :pid")->execute(['pid' => $profile_id]);
        $db->prepare("DELETE FROM entries WHERE profile_id = :pid")->execute(['pid' => $profile_id]);
        
        $this->redirect("/backups/{$profile_id}?wiped=1");
    }
}