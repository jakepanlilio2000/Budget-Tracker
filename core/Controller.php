<?php
namespace core;

abstract class Controller {
    protected function view(string $template, array $data = []): void {
        View::render($template, $data);
    }
    
    protected function json(array $data, int $status = 200): void {
        View::json($data, $status);
    }
    
    protected function redirect(string $url): void {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        if ($basePath !== '' && strpos($url, $basePath) !== 0) {
            $url = $basePath . '/' . ltrim($url, '/');
        }
        
        header("Location: {$url}");
        exit;
    }
    
    protected function checkCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            if (empty($token) && str_contains($contentType, 'application/json')) {
                $rawInput = file_get_contents('php://input');
                $jsonData = json_decode($rawInput, true);
                $token = $jsonData['csrf_token'] ?? '';
            }

            if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
                $this->json(['error' => 'Invalid CSRF token'], 403);
                exit; // Fix: Stop execution immediately
            }
        }
    }
}