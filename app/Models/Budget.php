<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\BudgetService;

class Budget
{
    public static function getMonthlyByUser(int $userId, string $month): array
    {
        $overview = BudgetService::getMonthlyBudgetOverview($userId, $month);
        return $overview['budgets'];
    }

    public static function upsert(int $userId, int $categoryId, string $month, float|string $amount, ?int $currencyId = null): bool
    {
        return BudgetService::upsertBudget($userId, $categoryId, $month, $amount, $currencyId);
    }
}