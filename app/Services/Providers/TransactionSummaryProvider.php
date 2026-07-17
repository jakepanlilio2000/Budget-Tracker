<?php
declare(strict_types=1);
namespace App\Services\Providers;

use App\Core\Database;

class TransactionSummaryProvider implements SummaryProviderInterface
{
    public function getMetrics(int $userId, ?string $periodStart, ?string $periodEnd): array
    {
        $db = Database::getInstance()->getConnection();
        $dateCondition = "";
        $params = [$userId];

        if ($periodStart && $periodEnd) {
            $dateCondition = "AND transaction_date BETWEEN ? AND ?";
            $params[] = $periodStart;
            $params[] = $periodEnd;
        }

        $stmt = $db->prepare("
            SELECT COUNT(*) as count, 
                   SUM(CASE WHEN type = 'income' THEN total_amount ELSE 0 END) as income,
                   SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END) as expense
            FROM transactions WHERE user_id = ? AND status = 'posted' AND deleted_at IS NULL " . $dateCondition
        );
        $stmt->execute($params);
        $data = $stmt->fetch();

        return [
            'transaction_count' => (int) $data['count'],
            'avg_transaction_size' => (int) $data['count'] > 0 ? round(((float) $data['income'] + (float) $data['expense']) / (int) $data['count'], 2) : 0
        ];
    }
}