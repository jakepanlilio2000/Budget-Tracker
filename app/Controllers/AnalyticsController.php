<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\InsightService;
use App\Models\RadarService;
use App\Services\AnalyticsService;
use App\Models\CurrencyService;
class AnalyticsController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();

        $to = $_GET['to'] ?? date('Y-m-d');
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-6 months'));

        if (strtotime($from) === false || strtotime($to) === false) {
            $from = date('Y-m-d', strtotime('-6 months'));
            $to = date('Y-m-d');
        }

        $health = InsightService::getFinancialHealthScore($userId);
        $recommendations = InsightService::getRecommendations($userId);
        $subscriptions = RadarService::detectSubscriptions($userId);
        $alerts = RadarService::getActiveAlerts($userId);
        $financial = AnalyticsService::getFinancialPerformance($userId, $from, $to);
        $behavioral = AnalyticsService::getBehavioralAnalysis($userId, $from, $to);
        $category = AnalyticsService::getCategoryIntelligence($userId, $from, $to);
        $accounts = AnalyticsService::getAccountAnalysis($userId);

        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $this->view('analytics.index', [
            'health' => $health,
            'recommendations' => $recommendations,
            'subscriptions' => $subscriptions,
            'alerts' => $alerts,
            'financial' => $financial,
            'behavioral' => $behavioral,
            'category' => $category,
            'accounts' => $accounts,
            'baseCurrency' => $baseCurrency,
            'from' => $from,
            'to' => $to
        ]);
    }

    public function resolveAlert(int $id): void
    {
        $this->validateCsrf();
        RadarService::resolveAlert($id, Auth::id());
        $this->json(['success' => true]);
    }
}