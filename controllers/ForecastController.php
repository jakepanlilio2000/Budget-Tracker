<?php
namespace controllers;
use core\Controller;
use config\Database;
use models\Profile;

class ForecastController extends Controller {
    public function index(int $profile_id): void {
        $db = Database::getInstance();
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile) $this->redirect('/');

        $year = (int)date('Y');
        
        // 1. Fetch Actual Baseline Monthly Data
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(period_date, '%m') as month, type, SUM(amount) as total
            FROM transactions
            WHERE profile_id = :pid AND YEAR(period_date) = :year AND is_checked = 1
            GROUP BY month, type
        ");
        $stmt->execute(['pid' => $profile_id, 'year' => $year]);
        $rawBase = $stmt->fetchAll();

        $baseMonthlyNet = array_fill(1, 12, 0.0);
        foreach ($rawBase as $row) {
            $m = (int)$row['month'];
            if ($row['type'] === 'inflow') $baseMonthlyNet[$m] += (float)$row['total'];
            if ($row['type'] === 'outflow') $baseMonthlyNet[$m] -= (float)$row['total'];
        }

        // 2. Fetch Simulation Data from Session Sandbox
        $sandboxKey = "forecast_{$profile_id}";
        $simItems = $_SESSION[$sandboxKey] ?? [];
        
        $simMonthlyNet = $baseMonthlyNet; 
        foreach ($simItems as $item) {
            $m = (int)$item['month'];
            if ($item['type'] === 'inflow') $simMonthlyNet[$m] += (float)$item['amount'];
            if ($item['type'] === 'outflow') $simMonthlyNet[$m] -= (float)$item['amount'];
        }

        // 3. Calculate Cumulative Trajectories
        $baseCumulative = [];
        $simCumulative = [];
        $runningBase = 0.0;
        $runningSim = 0.0;

        for ($i = 1; $i <= 12; $i++) {
            $runningBase += $baseMonthlyNet[$i];
            $runningSim += $simMonthlyNet[$i];
            $baseCumulative[] = round($runningBase, 2);
            $simCumulative[] = round($runningSim, 2);
        }

        $this->view('forecast/index', [
            'profile_id' => $profile_id,
            'profile' => $profile,
            'baseCumulative' => $baseCumulative,
            'simCumulative' => $simCumulative,
            'simItems' => $simItems
        ]);
    }

    public function add(int $profile_id): void {
        $this->checkCsrf();
        $sandboxKey = "forecast_{$profile_id}";
        
        if (!isset($_SESSION[$sandboxKey])) {
            $_SESSION[$sandboxKey] = [];
        }

        $_SESSION[$sandboxKey][] = [
            'id' => uniqid(),
            'name' => htmlspecialchars($_POST['name']),
            'amount' => preg_replace('/[^0-9.]/', '', $_POST['amount']),
            'type' => $_POST['type'],
            'month' => (int)$_POST['month']
        ];

        $this->redirect("/forecast/{$profile_id}");
    }

    public function remove(int $profile_id): void {
        $this->checkCsrf();
        $sandboxKey = "forecast_{$profile_id}";
        $targetId = $_POST['id'] ?? '';

        if (isset($_SESSION[$sandboxKey])) {
            $_SESSION[$sandboxKey] = array_filter($_SESSION[$sandboxKey], fn($item) => $item['id'] !== $targetId);
            $_SESSION[$sandboxKey] = array_values($_SESSION[$sandboxKey]); // Re-index array
        }
        $this->json(['success' => true]);
    }

    public function clear(int $profile_id): void {
        $this->checkCsrf();
        unset($_SESSION["forecast_{$profile_id}"]);
        $this->redirect("/forecast/{$profile_id}");
    }
}