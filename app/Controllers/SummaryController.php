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
            exit; // CRITICAL: Stop execution to prevent HTML output
        }
    }

    public function getCurrent(): void
    {
        try {
            $userId = Auth::id();

            $currentStart = date('Y-m-01');
            $currentEnd = date('Y-m-t');
            $prevStart = date('Y-m-01', strtotime('first day of last month'));
            $prevEnd = date('Y-m-t', strtotime('last day of last month'));

            $summary = FinancialSummaryEngine::getSummary($userId, $currentStart, $currentEnd);
            $lifetime = FinancialSummaryEngine::getSummary($userId);

            $summary['net_worth'] = $lifetime['net_worth'];
            $summary['savings']['total_deposited'] = $lifetime['savings']['total_deposited'];

            $insightsData = \App\Services\FinancialInsightsService::generateInsights(
                $userId,
                $currentStart,
                $currentEnd,
                $prevStart,
                $prevEnd
            );

            $this->json([
                'success' => true,
                'data' => array_merge($summary, $insightsData),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function refresh(): void
    {
        try {
            $userId = Auth::id();

            FinancialSummaryEngine::invalidateCache($userId);
            LifetimeStatsService::clearCache($userId);

            $this->getCurrent();
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}