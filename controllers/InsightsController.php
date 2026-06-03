<?php
namespace controllers;
use core\Controller;
use config\Database;
use models\Profile;

class InsightsController extends Controller {
    public function index(int $profile_id): void {
        $db = Database::getInstance();
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile) $this->redirect('/');

        $year = (int)($_GET['year'] ?? date('Y'));
        
        // 1. Get monthly cashflow for the selected year
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(period_date, '%m') as month, type, SUM(amount) as total
            FROM transactions
            WHERE profile_id = :pid AND YEAR(period_date) = :year AND is_checked = 1
            GROUP BY month, type
        ");
        $stmt->execute(['pid' => $profile_id, 'year' => $year]);
        $monthlyDataRaw = $stmt->fetchAll();

        $chartInflow = array_fill(1, 12, 0);
        $chartOutflow = array_fill(1, 12, 0);
        $yearlyInflow = 0;
        $yearlyOutflow = 0;

        foreach ($monthlyDataRaw as $row) {
            $m = (int)$row['month'];
            if ($row['type'] === 'inflow') {
                $chartInflow[$m] = (float)$row['total'];
                $yearlyInflow += (float)$row['total'];
            }
            if ($row['type'] === 'outflow') {
                $chartOutflow[$m] = (float)$row['total'];
                $yearlyOutflow += (float)$row['total'];
            }
        }

        // 2. Get category breakdown for outflows
        $stmtCat = $db->prepare("
            SELECT c.name, c.color, SUM(t.amount) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.profile_id = :pid AND YEAR(t.period_date) = :year AND t.is_checked = 1 AND t.type = 'outflow'
            GROUP BY c.id
            ORDER BY total DESC
        ");
        $stmtCat->execute(['pid' => $profile_id, 'year' => $year]);
        $categoryData = $stmtCat->fetchAll();

        $savingsRate = $yearlyInflow > 0 ? (($yearlyInflow - $yearlyOutflow) / $yearlyInflow) * 100 : 0;

        $this->view('insights/index', [
            'profile' => $profile,
            'year' => $year,
            'chartInflow' => array_values($chartInflow),
            'chartOutflow' => array_values($chartOutflow),
            'categoryData' => $categoryData,
            'yearlyInflow' => $yearlyInflow,
            'yearlyOutflow' => $yearlyOutflow,
            'savingsRate' => max(0, $savingsRate)
        ]);
    }
}