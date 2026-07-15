<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }

        require $viewPath;
    }

    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }

    protected function redirect(string $url): void
    {

        $targetUrl = url($url);
        header("Location: {$targetUrl}");
        exit;
    }

    protected function validateCsrf(): void
    {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            die("Invalid CSRF token.");
        }
    }
}