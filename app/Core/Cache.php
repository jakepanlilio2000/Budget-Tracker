<?php
declare(strict_types=1);

namespace App\Core;

class Cache
{
    private static string $cacheDir;

    private static function init(): void
    {
        self::$cacheDir = BASE_PATH . '/storage/cache';
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        self::init();
        $filePath = self::$cacheDir . '/' . md5($key) . '.cache';

        // Check if valid cache exists
        if (file_exists($filePath) && (time() - filemtime($filePath)) < $ttlSeconds) {
            $data = file_get_contents($filePath);
            $decoded = json_decode($data, true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Cache miss: execute callback and save
        $result = $callback();
        file_put_contents($filePath, json_encode($result), LOCK_EX);
        
        return $result;
    }

    public static function forget(string $key): void
    {
        self::init();
        $filePath = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}