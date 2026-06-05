<?php
namespace core;

class View {
    public static function render(string $template, array $data = []): void {
        extract($data, EXTR_SKIP);
        $viewFile = "views/{$template}.php";
        
        if (file_exists($viewFile)) {
            if (str_starts_with($template, 'partials/')) {
                require $viewFile;
            } else {
                require "views/layout/header.php";
                require "views/layout/nav.php";
                require $viewFile;
                require "views/layout/footer.php";
            }
        } else {
            die("View {$template} not found.");
        }
    }

    public static function json(array $data, int $status = 200): void {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}