<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Core\Database;
use App\Services\MathService;

class CurrencyService
{
    public static function getUserBaseCurrency(int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT c.* FROM currencies c 
            JOIN user_preferences up ON c.id = up.base_currency_id 
            WHERE up.user_id = ?
        ");
        $stmt->execute([$userId]);
        $userCurrency = $stmt->fetch();

        if ($userCurrency) {
            return $userCurrency;
        }

        $stmt = $db->query("SELECT * FROM currencies WHERE is_base = 1 LIMIT 1");
        return $stmt->fetch() ?: ['code' => 'USD', 'symbol' => '$', 'id' => 1, 'exchange_rate' => '1.000000'];
    }

    public static function convertAmount(float|string $amount, int $fromCurrencyId, int $toCurrencyId): float
    {
        if ($fromCurrencyId === $toCurrencyId) {
            return (float) MathService::round($amount, 2);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, exchange_rate FROM currencies WHERE id IN (?, ?)");
        $stmt->execute([$fromCurrencyId, $toCurrencyId]);
        $rates = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $fromRate = (string) ($rates[$fromCurrencyId] ?? '1.000000');
        $toRate = (string) ($rates[$toCurrencyId] ?? '1.000000');

        $converted = MathService::convertCross($amount, $fromRate, $toRate, MathService::SCALE_MONEY);
        return (float) $converted;
    }

    public static function getAllCurrencies(): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT id, code, name, symbol, exchange_rate, is_base FROM currencies ORDER BY code ASC");
        return $stmt->fetchAll();
    }
}