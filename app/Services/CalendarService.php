<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Models\CurrencyService;

class CalendarService
{
    public static function getEvents(int $userId, string $start, string $end): array
    {
        $db = Database::getInstance()->getConnection();
        $events = [];
        $baseCurrency = CurrencyService::getUserBaseCurrency($userId);
        $defaultSymbol = $baseCurrency['symbol'] ?? '$';

        $stmt = $db->prepare("
            SELECT t.transaction_date as date, t.type, t.description, t.total_amount, c.symbol
            FROM transactions t
            LEFT JOIN currencies c ON t.currency_id = c.id
            WHERE t.user_id = ? AND t.status = 'posted' AND t.deleted_at IS NULL 
            AND t.transaction_date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $start, $end]);
        foreach ($stmt->fetchAll() as $row) {
            $events[] = [
                'title' => ($row['type'] === 'income' ? '+ ' : '- ') . ($row['description'] ?: ucfirst($row['type'])),
                'start' => $row['date'],
                'color' => $row['type'] === 'income' ? '#10b981' : '#ef4444',
                'icon' => $row['type'] === 'income' ? 'fa-arrow-down' : 'fa-arrow-up',
                'module' => 'transactions',
                'amount' => (float) $row['total_amount'],
                'currency_symbol' => $row['symbol'] ?? $defaultSymbol
            ];
        }
        $stmt = $db->prepare("
            SELECT next_due_date as date, name, total_amount
            FROM bills 
            WHERE user_id = ? AND status = 'active' AND next_due_date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $start, $end]);
        foreach ($stmt->fetchAll() as $row) {
            $events[] = [
                'title' => 'Bill: ' . $row['name'],
                'start' => $row['date'],
                'color' => '#f59e0b',
                'icon' => 'fa-file-invoice',
                'module' => 'bills',
                'amount' => (float) $row['total_amount'],
                'currency_symbol' => $defaultSymbol
            ];
        }

        $stmt = $db->prepare("
            SELECT payment_date as date, e.company_name, s.net_pay
            FROM salaries s
            JOIN employers e ON s.employer_id = e.id
            WHERE s.user_id = ? AND s.payment_date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $start, $end]);
        foreach ($stmt->fetchAll() as $row) {
            $events[] = [
                'title' => 'Salary: ' . $row['company_name'],
                'start' => $row['date'],
                'color' => '#3b82f6',
                'icon' => 'fa-briefcase',
                'module' => 'salaries',
                'amount' => (float) $row['net_pay'],
                'currency_symbol' => $defaultSymbol
            ];
        }
        $stmt = $db->prepare("
            SELECT due_date as date, description, amount, type, c.symbol 
            FROM pending_ledger pl
            LEFT JOIN currencies c ON pl.currency_id = c.id
            WHERE pl.user_id = ? AND pl.status = 'pending' AND pl.due_date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $start, $end]);
        foreach ($stmt->fetchAll() as $row) {
            $events[] = [
                'title' => 'Pending: ' . $row['description'],
                'start' => $row['date'],
                'color' => $row['type'] === 'income' ? '#10b981' : '#8b5cf6',
                'icon' => 'fa-hourglass-half',
                'module' => 'pending_ledger',
                'amount' => (float) $row['amount'],
                'currency_symbol' => $row['symbol'] ?? $defaultSymbol
            ];
        }
        $stmt = $db->prepare("
            SELECT DATE(vt.created_at) as date, vt.type, vt.amount, sv.name as vault_name
            FROM vault_transactions vt
            JOIN savings_vaults sv ON vt.vault_id = sv.id
            WHERE vt.user_id = ? AND DATE(vt.created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $start, $end]);
        foreach ($stmt->fetchAll() as $row) {
            $events[] = [
                'title' => 'Vault ' . ucfirst($row['type']) . ': ' . $row['vault_name'],
                'start' => $row['date'],
                'color' => '#14b8a6',
                'icon' => 'fa-vault',
                'module' => 'vaults',
                'amount' => (float) $row['amount'],
                'currency_symbol' => $defaultSymbol
            ];
        }

        return $events;
    }

    public static function getDaySummary(int $userId, string $date): array
    {
        $events = self::getEvents($userId, $date, $date);

        $income = 0;
        $expense = 0;
        $modules = [];

        foreach ($events as $e) {
            if (in_array($e['module'], ['transactions', 'salaries']) && strpos($e['title'], '+') !== false || $e['module'] === 'salaries') {
                $income += $e['amount'];
            } else {
                $expense += $e['amount'];
            }
            $modules[$e['module']] = ($modules[$e['module']] ?? 0) + 1;
        }

        return [
            'date' => $date,
            'events' => $events,
            'total_income' => $income,
            'total_expense' => $expense,
            'net_flow' => $income - $expense,
            'activity_count' => count($events)
        ];
    }
}