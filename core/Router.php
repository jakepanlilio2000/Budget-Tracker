<?php
namespace core;

class Router {
    private array $routes = [];

    public function get(string $path, string $handler): void {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, string $handler): void {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $uri, string $method): void {
        $uri = parse_url($uri, PHP_URL_PATH);
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && $basePath !== '\\') {
            $uri = str_replace($basePath, '', $uri);
        }
        
        $uri = rtrim($uri, '/') ?: '/';

        if (!isset($this->routes[$method])) {
            $this->abort(404);
        }

        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $route);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$controllerName, $action] = explode('@', $handler);
                $controllerClass = "controllers\\" . $controllerName;

                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    if (method_exists($controller, $action)) {
                        call_user_func_array([$controller, $action], $params);
                        return;
                    }
                }
            }
        }
        $this->abort(404);
    }

    private function abort(int $code): void {
        http_response_code($code);
        $errorFile = "views/errors/{$code}.php";
        
        if (file_exists($errorFile)) {
            require_once $errorFile;
        } else {
            echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
            echo "<h2>Error {$code}</h2>";
            echo "<p>The requested page could not be found or an internal error occurred.</p>";
            echo "<a href='/'>Return to Home</a>";
            echo "</div>";
        }
        exit;
    }
}