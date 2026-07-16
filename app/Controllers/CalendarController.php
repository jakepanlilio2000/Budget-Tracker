<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\CalendarService;
use App\Models\CurrencyService;
use App\Core\Logger;

class CalendarController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $baseCurrency = CurrencyService::getUserBaseCurrency(Auth::id());
        $this->view('calendar.index', ['baseCurrency' => $baseCurrency]);
    }

    public function events(): void
    {
        $userId = Auth::id();
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');

        $start = substr($start, 0, 10);
        $end = substr($end, 0, 10);

        try {
            $events = CalendarService::getEvents($userId, $start, $end);
            $this->json($events);
        } catch (\Exception $e) {
            Logger::error("Calendar events fetch failed", ['error' => $e->getMessage()]);
            $this->json(['error' => 'Failed to load events', 'message' => $e->getMessage()], 500);
        }
    }

    public function daySummary(): void
    {
        $userId = Auth::id();
        $date = $_GET['date'] ?? date('Y-m-d');
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $summary = CalendarService::getDaySummary($userId, $date);
        $summary['baseCurrency'] = $baseCurrency;

        $this->json(['success' => true, 'summary' => $summary]);
    }
}