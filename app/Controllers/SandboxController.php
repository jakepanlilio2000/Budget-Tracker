<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\CurrencyService;
use App\Models\Vault;
use App\Core\Session;

class SandboxController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function budget(): void
    {
        $baseCurrency = CurrencyService::getUserBaseCurrency(Auth::id());
        $this->view('sandbox.budget', ['baseCurrency' => $baseCurrency]);
    }

    public function applyPlan(): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $targetAmount = (float) ($_POST['projected_savings'] ?? 0);
        $monthlyContribution = (float) ($_POST['monthly_savings'] ?? 0);

        if ($targetAmount <= 0) {
            Session::set('error', 'No savings projected to apply.');
            $this->redirect('/sandbox/budget');
        }

        $data = [
            'name' => 'Sandbox Plan (' . date('M Y') . ')',
            'description' => 'Auto-generated from Budget Sandbox. Target: ' . number_format($targetAmount, 2),
            'target_amount' => $targetAmount
        ];

        $vaultId = Vault::create($userId, $data);
        Session::set('success', 'Savings Goal created! Check your Vaults to start funding this plan.');
        $this->redirect('/vaults');
    }
}