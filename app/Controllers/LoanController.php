<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\CurrencyService;

class LoanController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) $this->redirect('/login');
    }

    public function simulator(): void
    {
        $baseCurrency = CurrencyService::getUserBaseCurrency(Auth::id());
        $this->view('loans.simulator', ['baseCurrency' => $baseCurrency]);
    }
}