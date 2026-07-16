<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;

class TimelineService
{
    public static function logEvent(
        string $module,
        string $action,
        string $description,
        float $amount = 0.0,
        ?int $currencyId = null,
        ?int $accountId = null,
        ?int $categoryId = null,
        ?int $relatedRecordId = null,
        string $icon = 'fa-circle',
        string $color = '#64748b'
    ): void {
        $userId = Auth::id();
        if (!$userId)
            return;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO timeline_events 
            (user_id, module, action, description, amount, currency_id, account_id, category_id, related_record_id, icon, color) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $module,
            $action,
            $description,
            $amount,
            $currencyId,
            $accountId,
            $categoryId,
            $relatedRecordId,
            $icon,
            $color
        ]);
    }

    public static function logSalary(int $userId, array $salary): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO timeline_events 
            (user_id, module, action, description, amount, currency_id, account_id, category_id, related_record_id, icon, color, created_at) 
            VALUES (?, 'salaries', 'salary_received', ?, ?, ?, ?, ?, ?, 'fa-briefcase', '#10b981', ?)
        ");
        $stmt->execute([
            $userId,
            'Salary from ' . ($salary['employer_name'] ?? 'Employer'),
            (float) $salary['net_pay'],
            (int) ($salary['currency_id'] ?? 1),
            null,
            null,
            $salary['id'],
            $salary['payment_date']
        ]);
    }
}