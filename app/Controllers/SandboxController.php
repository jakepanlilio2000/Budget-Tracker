<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\CurrencyService;
use App\Models\PlanningScenario;
use App\Models\BudgetTemplate;
use App\Core\Database;
use App\Services\LoanCalculatorService;
use App\Services\CashFlowService;
use App\Services\FinancialHealthService;
use App\Services\SmartRecommendationService;
use App\Services\MonteCarloService;
use App\Services\ScenarioComparisonService;
class SandboxController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }
    public function budget(): void
    {
        $this->studio();
    }

    public function studio(): void
    {
        $userId = Auth::id();
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);
        $scenarios = PlanningScenario::getAllByUser($userId);

        $this->view('planning.studio', [
            'baseCurrency' => $baseCurrency,
            'scenarios' => $scenarios
        ]);
    }

    public function storeScenario(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $name = trim($_POST['name'] ?? 'Untitled Scenario');
        $description = trim($_POST['description'] ?? '');

        $workspaceData = [
            'gross_income' => (float) ($_POST['gross_income'] ?? 3000),
            'tax_rate' => (float) ($_POST['tax_rate'] ?? 20),
            'buckets' => [
                'needs' => (int) ($_POST['bucket_needs'] ?? 50),
                'wants' => (int) ($_POST['bucket_wants'] ?? 30),
                'savings' => (int) ($_POST['bucket_savings'] ?? 20)
            ],

        ];

        $scenarioId = PlanningScenario::create($userId, $name, $description, $workspaceData);

        Session::set('success', 'Scenario "' . $name . '" saved successfully!');
        $this->redirect('/sandbox/budget?scenario=' . $scenarioId);
    }

    public function duplicateScenario(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $newId = PlanningScenario::duplicate($id, $userId);
        if ($newId) {
            Session::set('success', 'Scenario duplicated successfully!');
            $this->redirect('/sandbox/budget?scenario=' . $newId);
        }
        Session::set('error', 'Failed to duplicate scenario.');
        $this->redirect('/sandbox/budget');
    }

    public function toggleArchiveScenario(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        PlanningScenario::toggleArchive($id, $userId);
        Session::set('success', 'Scenario status updated.');
        $this->redirect('/sandbox/budget');
    }

    public function deleteScenario(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        PlanningScenario::delete($id, $userId);
        Session::set('success', 'Scenario deleted permanently.');
        $this->redirect('/sandbox/budget');
    }

    public function loadScenario(int $id): void
    {
        $userId = Auth::id();
        $scenario = PlanningScenario::findById($id, $userId);

        if (!$scenario) {
            $this->json(['success' => false, 'message' => 'Scenario not found'], 404);
            return;
        }

        $this->json([
            'success' => true,
            'data' => [
                'name' => $scenario['name'],
                'workspace' => json_decode($scenario['workspace_data'], true)
            ]
        ]);
    }
    public function saveTemplate(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $name = trim($_POST['name'] ?? 'My Custom Template');
        $description = trim($_POST['description'] ?? '');

        $allocations = [
            'needs' => (int) ($_POST['needs'] ?? 50),
            'wants' => (int) ($_POST['wants'] ?? 30),
            'savings' => (int) ($_POST['savings'] ?? 20)
        ];

        BudgetTemplate::create($userId, $name, $description, $allocations);

        $this->json(['success' => true, 'message' => 'Template saved successfully!']);
    }
    public function addLoan(): void
    {
        try {
            $this->validateCsrf();
            $userId = Auth::id();

            $scenarioIdRaw = $_POST['scenario_id'] ?? null;
            $scenarioId = (is_numeric($scenarioIdRaw) && $scenarioIdRaw > 0) ? (int) $scenarioIdRaw : null;

            $data = [
                'name' => trim($_POST['name'] ?? 'New Loan'),
                'principal' => (float) ($_POST['principal'] ?? 0),
                'annual_interest_rate' => (float) ($_POST['annual_interest_rate'] ?? 0),
                'term_months' => (int) ($_POST['term_months'] ?? 0),
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'extra_monthly_payment' => (float) ($_POST['extra_monthly_payment'] ?? 0),
                'loan_type' => $_POST['loan_type'] ?? 'fixed'
            ];

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO planning_loans (scenario_id, user_id, name, principal, annual_interest_rate, term_months, start_date, extra_monthly_payment, loan_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $scenarioId,
                $userId,
                $data['name'],
                $data['principal'],
                $data['annual_interest_rate'],
                $data['term_months'],
                $data['start_date'],
                $data['extra_monthly_payment'],
                $data['loan_type']
            ]);

            $this->json(['success' => true, 'id' => $db->lastInsertId()]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
    public function deleteLoan(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM planning_loans WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $this->json(['success' => true]);
    }

    public function getLoanAmortization(): void
    {
        $principal = (float) ($_GET['principal'] ?? 0);
        $rate = (float) ($_GET['rate'] ?? 0);
        $term = (int) ($_GET['term'] ?? 0);
        $extra = (float) ($_GET['extra'] ?? 0);

        $result = LoanCalculatorService::calculate($principal, $rate, $term, $extra);
        $this->json(['success' => true, 'data' => $result]);
    }

    public function listLoans(): void
    {

        if (!Auth::check()) {
            error_log("❌ DEBUG: Returning 401 Unauthorized");
            $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $userId = Auth::id();
        $scenarioId = !empty($_GET['scenario_id']) ? (int) $_GET['scenario_id'] : null;

        $db = Database::getInstance()->getConnection();

        $sql = "SELECT * FROM planning_loans WHERE user_id = ?";
        $params = [$userId];

        if ($scenarioId !== null) {
            $sql .= " AND scenario_id = ?";
            $params[] = $scenarioId;
        }

        $sql .= " ORDER BY created_at DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            error_log("✅ DEBUG: Successfully fetched loans, returning JSON.");
            $this->json(['success' => true, 'loans' => $stmt->fetchAll()]);
        } catch (\Exception $e) {
            error_log("❌ DEBUG: Database error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function addInvestment(): void
    {
        try {
            $this->validateCsrf();
            $userId = Auth::id();

            $scenarioId = $_POST['scenario_id'] ?? null;
            if ($scenarioId === '') {
                $scenarioId = null;
            }

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO planning_investments (scenario_id, user_id, name, asset_type, initial_investment, monthly_contribution, annual_return_rate, annual_fee_rate, term_months, risk_level) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $scenarioId,
                $userId,
                trim($_POST['name'] ?? 'Investment'),
                $_POST['asset_type'] ?? 'stocks',
                (float) ($_POST['initial_investment'] ?? 0),
                (float) ($_POST['monthly_contribution'] ?? 0),
                (float) ($_POST['annual_return_rate'] ?? 0),
                (float) ($_POST['annual_fee_rate'] ?? 0),
                (int) ($_POST['term_months'] ?? 120),
                $_POST['risk_level'] ?? 'medium'
            ]);

            $this->json(['success' => true, 'id' => $db->lastInsertId()]);

        } catch (\Exception $e) {
            \App\Core\Logger::error("Add Investment Error", [
                'user_id' => Auth::id() ?? 0,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->json([
                'success' => false,
                'message' => 'Failed to add investment. Ensure all database migrations are run.'
            ], 500);
        }
    }

    public function deleteInvestment(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM planning_investments WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $this->json(['success' => true]);
    }

    public function listInvestments(): void
    {
        $userId = Auth::id();
        $scenarioId = $_GET['scenario_id'] ?? null;
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT * FROM planning_investments WHERE user_id = ?";
        $params = [$userId];
        if ($scenarioId) {
            $sql .= " AND scenario_id = ?";
            $params[] = $scenarioId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $this->json(['success' => true, 'investments' => $stmt->fetchAll()]);
    }

    public function getUnifiedCashFlow(): void
    {
        try {
            $userId = Auth::id();
            $scenarioId = $_GET['scenario_id'] ?? null;
            $months = (int) ($_GET['months'] ?? 12);

            $liveForecast = [];
            if (class_exists('\App\Services\CashFlowService')) {
                $liveForecast = CashFlowService::generateForecast($userId, $months);
            }

            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM planning_loans WHERE user_id = ?";
            $params = [$userId];
            if ($scenarioId) {
                $sql .= " AND scenario_id = ?";
                $params[] = $scenarioId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $loans = $stmt->fetchAll();

            $sql = str_replace('planning_loans', 'planning_investments', $sql);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $investments = $stmt->fetchAll();

            $unified = [];

            for ($m = 1; $m <= $months; $m++) {
                $liveMonth = is_array($liveForecast) && isset($liveForecast[$m - 1]) ? $liveForecast[$m - 1] : ['net_income' => 0];

                $sandboxOutflow = 0;
                $sandboxInflow = 0;

                foreach ($loans as $loan) {
                    $calc = LoanCalculatorService::calculate(
                        (float) $loan['principal'],
                        (float) $loan['annual_interest_rate'],
                        (int) $loan['term_months'],
                        (float) $loan['extra_monthly_payment']
                    );
                    $sandboxOutflow += $calc['monthly_payment'] + (float) $loan['extra_monthly_payment'];
                }

                foreach ($investments as $inv) {
                    $monthlyReturn = (float) $inv['annual_return_rate'] / 100 / 12;
                    $sandboxInflow += ((float) $inv['initial_investment'] + ((float) $inv['monthly_contribution'] * $m)) * $monthlyReturn;
                    $sandboxOutflow += (float) $inv['monthly_contribution'];
                }

                $liveNet = (float) ($liveMonth['net_income'] ?? 0);
                $unified[] = [
                    'month' => $m,
                    'live_net_income' => $liveNet,
                    'sandbox_adjustment' => round($sandboxInflow - $sandboxOutflow, 2),
                    'simulated_net_income' => round($liveNet + $sandboxInflow - $sandboxOutflow, 2)
                ];
            }

            $this->json(['success' => true, 'unified_cash_flow' => $unified]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to calculate cash flow. Ensure all migrations are run.'
            ], 500);
        }
    }

    public function getHealthAnalysis(): void
    {
        $userId = Auth::id();


        $healthData = FinancialHealthService::calculate($userId);
        $recommendations = SmartRecommendationService::generate($healthData);

        $this->json([
            'success' => true,
            'health' => $healthData,
            'recommendations' => $recommendations
        ]);
    }

    public function runMonteCarlo(): void
    {
        $initial = (float) ($_POST['initial_balance'] ?? 0);
        $monthly = (float) ($_POST['monthly_contribution'] ?? 0);
        $return = (float) ($_POST['annual_return'] ?? 8);
        $volatility = (float) ($_POST['annual_volatility'] ?? 15);
        $months = (int) ($_POST['months'] ?? 120);
        $target = (float) ($_POST['target_goal'] ?? 0);
        $iterations = 1000;

        $results = MonteCarloService::simulate($initial, $monthly, $return, $volatility, $months, $iterations, $target);
        $this->json(['success' => true, 'data' => $results]);
    }

    public function compareScenarios(): void
    {
        $userId = Auth::id();
        $ids = array_map('intval', $_POST['scenario_ids'] ?? []);

        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'No scenarios selected'], 400);
        }

        $comparison = ScenarioComparisonService::compare($userId, $ids);
        $this->json(['success' => true, 'data' => $comparison]);
    }

}