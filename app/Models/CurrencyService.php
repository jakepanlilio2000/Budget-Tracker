<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

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
        return $stmt->fetch() ?: ['code' => 'USD', 'symbol' => '$', 'id' => 1];
    }

    public static function convertAmount(float $amount, int $fromCurrencyId, int $toCurrencyId): float
    {
        if ($fromCurrencyId === $toCurrencyId) return $amount;
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT exchange_rate FROM currencies WHERE id IN (?, ?)");
        $stmt->execute([$fromCurrencyId, $toCurrencyId]);
        $rates = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        $fromRate = $rates[$fromCurrencyId] ?? 1;
        $toRate = $rates[$toCurrencyId] ?? 1;

        return round(($amount / $fromRate) * $toRate, 2);
    }
}