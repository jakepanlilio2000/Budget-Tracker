<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\FinancialSummaryEngine;
use App\Services\LifetimeStatsService;

class SummaryController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
    }

    public function getCurrent(): void
    {
        $userId = Auth::id();
        $periodStart = date('Y-m-01');
        $periodEnd = date('Y-m-t');

        $summary = FinancialSummaryEngine::getSummary($userId, $periodStart, $periodEnd);
        $lifetime = FinancialSummaryEngine::getSummary($userId);

        $summary['net_worth'] = $lifetime['net_worth'];
        $summary['savings']['total_deposited'] = $lifetime['savings']['total_deposited'];

        $this->json([
            'success' => true,
            'data' => $summary,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function refresh(): void
    {
        $userId = Auth::id();

        FinancialSummaryEngine::invalidateCache($userId);
        LifetimeStatsService::clearCache($userId);

        $this->getCurrent();
    }
}