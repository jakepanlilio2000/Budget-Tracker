<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

class ScenarioComparisonService
{
    public static function compare(int $userId, array $scenarioIds): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, name, workspace_data FROM planning_scenarios WHERE id IN (" . implode(',', array_fill(0, count($scenarioIds), '?')) . ") AND user_id = ?");
        $stmt->execute(array_merge($scenarioIds, [$userId]));
        $scenarios = $stmt->fetchAll();

        $comparison = [];
        foreach ($scenarios as $s) {
            $data = json_decode($s['workspace_data'], true) ?? [];

            $income = (float) ($data['gross_income'] ?? 0);
            $tax = (float) ($data['tax_rate'] ?? 0);
            $netIncome = $income * (1 - ($tax / 100));

            $buckets = $data['buckets'] ?? ['needs' => 50, 'wants' => 30, 'savings' => 20];
            $monthlySavings = $netIncome * (($buckets['savings'] ?? 20) / 100);

            $comparison[] = [
                'id' => $s['id'],
                'name' => $s['name'],
                'gross_income' => $income,
                'net_income' => round($netIncome, 2),
                'monthly_savings' => round($monthlySavings, 2),
                'annual_savings' => round($monthlySavings * 12, 2),
                '10_year_projection' => round($monthlySavings * 12 * 10, 2)
            ];
        }

        return $comparison;
    }
}