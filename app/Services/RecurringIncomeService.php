<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Models\RecurringIncome;
use App\Services\TimelineService;
use App\Services\FinancialSummaryEngine;

class RecurringIncomeService
{
    public static function calculateNextDate(string $frequency, ?int $customDays, string $currentDate): string
    {
        $date = new \DateTime($currentDate);
        switch ($frequency) {
            case 'daily':
                $date->modify('+1 day');
                break;
            case 'weekly':
                $date->modify('+1 week');
                break;
            case 'bi-weekly':
                $date->modify('+2 weeks');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'quarterly':
                $date->modify('+3 months');
                break;
            case 'yearly':
                $date->modify('+1 year');
                break;
            case 'custom':
                if ($customDays && $customDays > 0) {
                    $date->modify("+{$customDays} days");
                } else {
                    $date->modify('+1 month');
                }
                break;
        }
        return $date->format('Y-m-d');
    }

    public static function postOccurrence(int $id, int $userId, bool $isAuto = false): bool
    {
        $db = Database::getInstance()->getConnection();
        $income = RecurringIncome::findById($id, $userId);
        if (!$income || $income['status'] !== 'active')
            return false;

        if ($income['end_date'] && $income['next_post_date'] > $income['end_date']) {
            RecurringIncome::toggleStatus($id, $userId, 'completed');
            return false;
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO transactions (user_id, account_id, category_id, type, total_amount, currency_id, transaction_date, status, description, notes)
                VALUES (?, ?, ?, 'income', ?, ?, ?, 'posted', ?, ?)
            ");
            $description = "Recurring Income: {$income['name']}";
            $stmt->execute([
                $userId,
                $income['account_id'],
                $income['category_id'],
                $income['amount'],
                $income['currency_id'],
                $income['next_post_date'],
                $description,
                $income['notes']
            ]);
            $txnId = (int) $db->lastInsertId();

            $db->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?")
                ->execute([$income['amount'], $income['account_id']]);

            $newNextDate = self::calculateNextDate($income['frequency'], $income['custom_interval_days'], $income['next_post_date']);
            $db->prepare("
                UPDATE recurring_incomes SET 
                next_post_date = ?, last_posted_date = CURRENT_DATE, total_posted_count = total_posted_count + 1 
                WHERE id = ?
            ")->execute([$newNextDate, $id]);

            $db->commit();
            TimelineService::logEvent(
                'recurring_income',
                'posted',
                $description,
                (float) $income['amount'],
                (int) $income['currency_id'],
                (int) $income['account_id'],
                $income['category_id'],
                $txnId,
                'fa-sync-alt',
                '#10b981'
            );

            FinancialSummaryEngine::invalidateCache($userId);
            \App\Services\AchievementEngine::syncUser($userId);
            \App\Services\FxpEngine::award($userId, 'receive_recurring_income', 1);

            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function autoPostDueIncomes(int $userId): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT id FROM recurring_incomes 
            WHERE user_id = ? AND status = 'active' AND next_post_date <= CURRENT_DATE
        ");
        $stmt->execute([$userId]);
        $dueIncomes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $postedCount = 0;
        foreach ($dueIncomes as $id) {
            if (self::postOccurrence((int) $id, $userId, true)) {
                $postedCount++;
            }
        }
        return $postedCount;
    }

    public static function skipNextOccurrence(int $id, int $userId): bool
    {
        $income = RecurringIncome::findById($id, $userId);
        if (!$income)
            return false;

        $newDate = self::calculateNextDate($income['frequency'], $income['custom_interval_days'], $income['next_post_date']);
        $db = Database::getInstance()->getConnection();
        return $db->prepare("UPDATE recurring_incomes SET next_post_date = ? WHERE id = ? AND user_id = ?")
            ->execute([$newDate, $id, $userId]);
    }
}