<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\DailyLog;

class DailyLogController extends Controller
{
    public function __construct() { if (!Auth::check()) $this->redirect('/login'); }

    public function index(): void
    {
        $logs = DailyLog::getRecent(Auth::id(), 30);
        $this->view('daily_logs.index', ['logs' => $logs]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $date = $_POST['log_date'] ?? date('Y-m-d');
        $totalSpent = (float)($_POST['total_spent'] ?? 0);
        $mood = trim($_POST['mood_context'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        DailyLog::upsert(Auth::id(), $date, $totalSpent, $mood, $notes);
        Session::set('success', 'Daily log saved.');
        $this->redirect('/daily-logs');
    }
}