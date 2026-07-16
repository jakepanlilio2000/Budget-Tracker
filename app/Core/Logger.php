<?php
declare(strict_types=1);

namespace App\Core;

use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

class Logger
{
    private static ?Monolog $instance = null;

    public static function getInstance(): Monolog
    {
        if (self::$instance === null) {
            $log = new Monolog('app');
            // Rotating files: keeps logs for 14 days
            $log->pushHandler(new RotatingFileHandler(BASE_PATH . '/storage/logs/app.log', 14, Monolog::DEBUG));
            self::$instance = $log;
        }
        return self::$instance;
    }

    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::getInstance()->warning("SECURITY: " . $message, $context);
    }
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }
}