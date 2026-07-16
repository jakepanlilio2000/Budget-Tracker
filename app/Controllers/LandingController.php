<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;

class LandingController extends Controller
{
    public function index(): void
    {
        ob_start();
        require BASE_PATH . '/app/Views/public/index.php';
        $content = ob_get_clean();

        require BASE_PATH . '/app/Views/layouts/landing.php';
    }
}