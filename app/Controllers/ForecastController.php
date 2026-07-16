<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Services\CashFlowService;
use App\Models\CurrencyService;

class ForecastController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();
        $days = (int) ($_GET['days'] ?? 30);
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $forecast = CashFlowService::generateForecast($userId, $days);

        $this->view('forecasts.index', [
            'forecast' => $forecast,
            'days' => $days,
            'baseCurrency' => $baseCurrency
        ]);
    }

    public function sandbox(): void
    {
        $userId = Auth::id();
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM forecast_scenarios WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $scenarios = $stmt->fetchAll();

        $this->view('forecasts.sandbox', [
            'scenarios' => $scenarios,
            'baseCurrency' => $baseCurrency
        ]);
    }

    public function runSandbox(): void
    {
        $userId = Auth::id();
        $days = (int) ($_POST['days'] ?? 30);
        $simulations = [];
        if (!empty($_POST['sim_date'])) {
            foreach ($_POST['sim_date'] as $i => $date) {
                if (!empty($date) && !empty($_POST['sim_amount'][$i])) {
                    $simulations[] = [
                        'date' => $date,
                        'type' => $_POST['sim_type'][$i] ?? 'expense',
                        'amount' => (float) $_POST['sim_amount'][$i]
                    ];
                }
            }
        }

        $forecast = CashFlowService::generateForecast($userId, $days, $simulations);
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $this->json([
            'success' => true,
            'forecast' => $forecast,
            'baseCurrency' => $baseCurrency
        ]);
    }

    public function saveScenario(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $name = trim($_POST['name'] ?? 'Untitled Scenario');
        $data = json_encode($_POST['scenario_data'] ?? []);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO forecast_scenarios (user_id, name, scenario_data) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $name, $data]);

        Session::set('success', 'Scenario saved successfully.');
        $this->redirect('/forecast/sandbox');
    }
}