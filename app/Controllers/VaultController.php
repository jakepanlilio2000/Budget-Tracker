<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Services\LifetimeStatsService;
use App\Models\Vault;
use App\Models\VaultTransaction;
use App\Models\CurrencyService;
use App\Services\AchievementEngine;
use App\Services\FxpEngine;
use App\Services\StreakEngine;
use App\Services\FinancialSummaryEngine;
class VaultController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();
        $activeVaults = Vault::getByStatus($userId, 'active');
        $completedVaults = Vault::getByStatus($userId, 'completed');
        $cancelledVaults = Vault::getByStatus($userId, 'cancelled');
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);
        foreach ($activeVaults as &$v) {
            $v['metrics'] = Vault::calculateMetrics($v, $userId);
        }

        $this->view('vaults.index', [
            'activeVaults' => $activeVaults,
            'completedVaults' => $completedVaults,
            'cancelledVaults' => $cancelledVaults,
            'baseCurrency' => $baseCurrency
        ]);
    }

    public function create(): void
    {
        $this->view('vaults.create');
    }

    public function store(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'target_amount' => (float) ($_POST['target_amount'] ?? 0)
        ];

        if (empty($data['name']) || $data['target_amount'] <= 0) {
            Session::set('error', 'Goal name and a valid target amount are required.');
            $this->redirect('/vaults/create');
        }

        Vault::create($userId, $data);
        $achResult = AchievementEngine::syncUser($userId);
        if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
            Session::set('achievement_notification', $achResult);
        }
        FxpEngine::award($userId, 'create_budget', 1);
        LifetimeStatsService::clearCache($userId);
        FinancialSummaryEngine::invalidateCache($userId);
        Session::set('success', 'Savings goal created successfully.');
        $this->redirect('/vaults');
    }

    public function show(int $id): void
    {
        $vault = Vault::findById($id, Auth::id());
        if (!$vault) {
            Session::set('error', 'Vault not found.');
            $this->redirect('/vaults');
        }

        $metrics = Vault::calculateMetrics($vault, Auth::id());
        $timeline = VaultTransaction::getTimeline($id);
        $baseCurrency = CurrencyService::getUserBaseCurrency(Auth::id());

        $this->view('vaults.show', [
            'vault' => $vault,
            'metrics' => $metrics,
            'timeline' => $timeline,
            'baseCurrency' => $baseCurrency
        ]);
    }

    public function transact(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();
        $vault = Vault::findById($id, Auth::id());
        if (!$vault || $vault['status'] !== 'active') {
            Session::set('error', 'Invalid or inactive vault.');
            $this->redirect('/vaults');
        }

        $type = $_POST['type'] ?? 'deposit';
        $amount = (float) ($_POST['amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($amount <= 0) {
            Session::set('error', 'Amount must be greater than zero.');
            $this->redirect('/vaults/show/' . $id);
        }

        if ($type === 'withdrawal' && $amount > (float) $vault['current_amount']) {
            Session::set('error', 'Insufficient funds in this vault for withdrawal.');
            $this->redirect('/vaults/show/' . $id);
        }

        if (VaultTransaction::record($id, Auth::id(), $type, $amount, $notes)) {
            if ($type === 'deposit') {
                FxpEngine::award(Auth::id(), 'deposit_vault', 1);
            }
            if ($type === 'deposit') {
                $achResult = AchievementEngine::syncUser($userId);
                if ($achResult['leveled_up'] || !empty($achResult['unlocks'])) {
                    Session::set('achievement_notification', $achResult);
                }
                FxpEngine::award($userId, 'deposit_vault', 1);
                StreakEngine::checkStreak($userId, 'daily_savings');
                LifetimeStatsService::clearCache($userId);
                FinancialSummaryEngine::invalidateCache($userId);
            }

            Session::set('success', ucfirst($type) . ' recorded successfully.');
        } else {
            Session::set('error', 'Failed to record transaction.');
        }

        $this->redirect('/vaults/show/' . $id);
    }

    public function updateStatus(int $id): void
    {
        $this->validateCsrf();
        $status = $_POST['status'] ?? 'cancelled';
        if (in_array($status, ['active', 'completed', 'cancelled'])) {
            Vault::updateStatus($id, $status);
            Session::set('success', 'Vault status updated.');
        }
        $this->redirect('/vaults');
    }
}