<?php
declare(strict_types=1);
namespace App\Services\Providers;

interface SummaryProviderInterface
{
    public function getMetrics(int $userId, ?string $periodStart, ?string $periodEnd): array;
}