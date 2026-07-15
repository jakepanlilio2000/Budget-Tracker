<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\InsightService;
use App\Models\RadarService;
use App\Core\Database;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();
        $health = InsightService::getFinancialHealthScore($userId);
        $recommendations = InsightService::getRecommendations($userId);
        $subscriptions = RadarService::detectSubscriptions($userId);
        $duplicates = RadarService::detectDuplicates($userId);
        $alerts = RadarService::getActiveAlerts($userId);
        foreach ($duplicates as $dup) {
            RadarService::createAlert(
                $userId, 'duplicate', 'high', 
                'Potential Duplicate Transaction', 
                "Found multiple transactions of {$dup['total_amount']} on {$dup['transaction_date']} with description '{$dup['description']}'.",
                'transaction', $dup['id']
            );
        }

        $this->view('analytics.index', [
            'health' => $health,
            'recommendations' => $recommendations,
            'subscriptions' => $subscriptions,
            'duplicates' => $duplicates,
            'alerts' => $alerts
        ]);
    }

    public function resolveAlert(int $id): void
    {
        $this->validateCsrf();
        RadarService::resolveAlert($id, Auth::id());
        $this->json(['success' => true]);
    }
}