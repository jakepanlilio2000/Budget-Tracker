<?php
namespace controllers;
use core\Controller;
use config\Database;
use models\Profile;

class BackupController extends Controller {
    public function index(int $profile_id): void {
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) $this->redirect('/');

        $this->view('backups/index', ['profile' => $profile]);
    }

    public function exportExcel(int $profile_id): void {
        $db = Database::getInstance();
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) $this->redirect('/');

        $stmt = $db->prepare("
            SELECT t.period_date, t.name, c.name as category, t.type, t.amount 
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.profile_id = :pid
            ORDER BY t.period_date DESC
        ");
        $stmt->execute(['pid' => $profile_id]);
        $data = $stmt->fetchAll();

        $stmtInc = $db->prepare("SELECT * FROM income_log WHERE profile_id = :pid ORDER BY date_received DESC");
        $stmtInc->execute(['pid' => $profile_id]);
        $incomeData = $stmtInc->fetchAll();

        $stmtShop = $db->prepare("SELECT * FROM shopping_log WHERE profile_id = :pid ORDER BY purchase_date DESC");
        $stmtShop->execute(['pid' => $profile_id]);
        $shoppingData = $stmtShop->fetchAll();

        $currency = $profile['currency'] ?? '';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Budget_Master_Report_' . date('Y-m-d') . '.xls"');

        echo '<?xml version="1.0"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

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

        // ==========================================
        // SHEET 1: MASTER REPORT (With Monthly Breakdown)
        // ==========================================
        echo '<Worksheet ss:Name="Master Ledger"><Table>' . "\n";
        echo '<Column ss:Width="100"/><Column ss:Width="240"/><Column ss:Width="140"/><Column ss:Width="140"/><Column ss:Width="120"/>' . "\n";

        $months = [];
        $globalInflow = 0.0;
        $globalOutflow = 0.0;

        foreach ($data as $row) {
            $monthName = date('F Y', strtotime($row['period_date']));
            $day = (int)date('d', strtotime($row['period_date']));

            if (!isset($months[$monthName])) {
                $months[$monthName] = ['inflow' => 0.0, 'outflow' => 0.0, 'first_half' => 0.0, 'second_half' => 0.0, 'items' => []];
            }

            $amt = (float)$row['amount'];
            if ($row['type'] === 'inflow') {
                $months[$monthName]['inflow'] += $amt;
                $globalInflow += $amt;
            } else {
                $months[$monthName]['outflow'] += $amt;
                $globalOutflow += $amt;
                if ($day <= 15) $months[$monthName]['first_half'] += $amt;
                else $months[$monthName]['second_half'] += $amt;
            }
            $months[$monthName]['items'][] = $row;
        }

        echo '<Row><Cell ss:StyleID="Title" ss:MergeAcross="4"><Data ss:Type="String">GLOBAL FINANCIAL SUMMARY - ' . htmlspecialchars($profile['name']) . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Total Lifetime Inflow:</Data></Cell><Cell ss:StyleID="Inflow"><Data ss:Type="Number">' . $globalInflow . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Total Lifetime Outflow:</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $globalOutflow . '</Data></Cell></Row>' . "\n";
        echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Cumulative Net Wealth:</Data></Cell><Cell ss:StyleID="Net"><Data ss:Type="Number">' . ($globalInflow - $globalOutflow) . '</Data></Cell></Row>' . "\n";
        echo '<Row></Row>' . "\n";

        foreach ($months as $monthName => $m) {
            echo '<Row><Cell ss:StyleID="Subtitle" ss:MergeAcross="4"><Data ss:Type="String">' . strtoupper($monthName) . ' REPORT</Data></Cell></Row>' . "\n";
            echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Total Inflow:</Data></Cell><Cell ss:StyleID="Inflow"><Data ss:Type="Number">' . $m['inflow'] . '</Data></Cell><Cell ss:Index="4" ss:StyleID="Bold"><Data ss:Type="String">1st Half Expenses (1st-15th):</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $m['first_half'] . '</Data></Cell></Row>' . "\n";
            echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Total Outflow:</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $m['outflow'] . '</Data></Cell><Cell ss:Index="4" ss:StyleID="Bold"><Data ss:Type="String">2nd Half Expenses (16th-End):</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $m['second_half'] . '</Data></Cell></Row>' . "\n";
            echo '<Row><Cell ss:StyleID="Bold"><Data ss:Type="String">Net Savings:</Data></Cell><Cell ss:StyleID="Net"><Data ss:Type="Number">' . ($m['inflow'] - $m['outflow']) . '</Data></Cell></Row>' . "\n";
            
            echo '<Row><Cell ss:StyleID="Header"><Data ss:Type="String">Date</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Transaction Name</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Category</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Type</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Amount</Data></Cell></Row>' . "\n";
            foreach ($m['items'] as $row) {
                $styleAmount = $row['type'] === 'inflow' ? 'Inflow' : 'Outflow';
                echo '<Row><Cell ss:StyleID="Date"><Data ss:Type="String">' . $row['period_date'] . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($row['name'], ENT_XML1) . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($row['category'] ?? 'Uncategorized', ENT_XML1) . '</Data></Cell><Cell><Data ss:Type="String">' . ucfirst($row['type']) . '</Data></Cell><Cell ss:StyleID="' . $styleAmount . '"><Data ss:Type="Number">' . $row['amount'] . '</Data></Cell></Row>' . "\n";
            }
            echo '<Row></Row><Row></Row>' . "\n"; 
        }
        echo '</Table></Worksheet>' . "\n";

        // ==========================================
        // SHEET 2: INCOME STREAMS
        // ==========================================
        echo '<Worksheet ss:Name="Income Streams"><Table>' . "\n";
        echo '<Column ss:Width="100"/><Column ss:Width="200"/><Column ss:Width="150"/><Column ss:Width="120"/><Column ss:Width="120"/>' . "\n";
        echo '<Row><Cell ss:StyleID="Header"><Data ss:Type="String">Date</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Source</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Platform/Method</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Business Type</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Amount</Data></Cell></Row>' . "\n";
        foreach ($incomeData as $row) {
            echo '<Row><Cell ss:StyleID="Date"><Data ss:Type="String">' . $row['date_received'] . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($row['source_name'], ENT_XML1) . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($row['platform'] . ' / ' . $row['payment_method'], ENT_XML1) . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($row['business_type'], ENT_XML1) . '</Data></Cell><Cell ss:StyleID="Inflow"><Data ss:Type="Number">' . $row['amount'] . '</Data></Cell></Row>' . "\n";
        }
        echo '</Table></Worksheet>' . "\n";

        // ==========================================
        // SHEET 3: DAILY SPENDS
        // ==========================================
        echo '<Worksheet ss:Name="Daily Spends"><Table>' . "\n";
        echo '<Column ss:Width="100"/><Column ss:Width="240"/><Column ss:Width="120"/><Column ss:Width="100"/><Column ss:Width="120"/>' . "\n";
        echo '<Row><Cell ss:StyleID="Header"><Data ss:Type="String">Date</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Item Name</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Payment Method</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Necessity</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Amount</Data></Cell></Row>' . "\n";
        foreach ($shoppingData as $row) {
            $isNeed = $row['is_need'] ? 'Need' : 'Want';
            echo '<Row><Cell ss:StyleID="Date"><Data ss:Type="String">' . $row['purchase_date'] . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($row['item_name'], ENT_XML1) . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($row['payment_method'], ENT_XML1) . '</Data></Cell><Cell><Data ss:Type="String">' . $isNeed . '</Data></Cell><Cell ss:StyleID="Outflow"><Data ss:Type="Number">' . $row['amount'] . '</Data></Cell></Row>' . "\n";
        }
        echo '</Table></Worksheet>' . "\n";

        echo '</Workbook>' . "\n";
        exit;
    }

    public function exportJson(int $profile_id): void {
        $db = Database::getInstance();
        
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $this->redirect('/');
        }

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

        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            $this->redirect('/');
        }

        $db->prepare("DELETE FROM transactions WHERE profile_id = :pid")->execute(['pid' => $profile_id]);
        $db->prepare("DELETE FROM entries WHERE profile_id = :pid")->execute(['pid' => $profile_id]);
        $db->prepare("DELETE FROM income_log WHERE profile_id = :pid")->execute(['pid' => $profile_id]);
        $db->prepare("DELETE FROM shopping_log WHERE profile_id = :pid")->execute(['pid' => $profile_id]);
        
        $this->redirect("/backups/{$profile_id}?wiped=1");
    }
}