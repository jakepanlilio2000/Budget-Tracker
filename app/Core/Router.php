<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $controllerAction): void
    {
        $this->addRoute('GET', $path, $controllerAction);
    }

    public function post(string $path, array $controllerAction): void
    {
        $this->addRoute('POST', $path, $controllerAction);
    }

    private function addRoute(string $method, string $path, array $controllerAction): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controllerAction[0],
            'action' => $controllerAction[1],
        ];
    }

    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $basePath = '/expenses';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');
        if ($uri === '//') {
            $uri = '/';
        }

        $method = $_SERVER['REQUEST_METHOD'];


        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches) && $route['method'] === $method) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $controllerClass = "App\\Controllers\\" . $route['controller'];
                if (!class_exists($controllerClass)) {
                    http_response_code(500);
                    die("Controller not found: {$controllerClass}");
                }

                $controller = new $controllerClass();
                call_user_func_array([$controller, $route['action']], $params);
                return;
            }
        }

        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
    }
}