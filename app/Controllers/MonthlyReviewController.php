<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\MonthlyReview;
use App\Models\CurrencyService;

class MonthlyReviewController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) $this->redirect('/login');
    }

    public function index(): void
    {
        $userId = Auth::id();
        $month = $_GET['month'] ?? date('Y-m'); 
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $reviewData = MonthlyReview::generate($userId, $month);
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);

        $this->view('reviews.monthly', [
            'review' => $reviewData,
            'currentMonth' => $month,
            'baseCurrency' => $baseCurrency
        ]);
    }
}