<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;

class CashFlowService
{
    public static function generateForecast(int $userId, int $days = 30, array $simulations = []): array
    {
        $db = Database::getInstance()->getConnection();
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        $stmt = $db->prepare("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE user_id = ? AND deleted_at IS NULL AND type != 'credit_card'");
        $stmt->execute([$userId]);
        $currentBalance = (float) $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT DATE(due_date) as event_date, SUM(amount) as total 
            FROM pending_ledger 
            WHERE user_id = ? AND type = 'income' AND status = 'pending' AND due_date BETWEEN ? AND ?
            GROUP BY event_date
            UNION ALL
            SELECT DATE(payment_date) as event_date, SUM(net_pay) as total 
            FROM salaries 
            WHERE user_id = ? AND payment_date BETWEEN ? AND ?
            GROUP BY event_date
        ");
        $stmt->execute([$userId, $startDate, $endDate, $userId, $startDate, $endDate]);
        $incomeMap = [];
        foreach ($stmt->fetchAll() as $row) {
            $incomeMap[$row['event_date']] = ($incomeMap[$row['event_date']] ?? 0) + (float) $row['total'];
        }

        $stmt = $db->prepare("
            SELECT DATE(next_due_date) as event_date, SUM(total_amount) as total 
            FROM bills 
            WHERE user_id = ? AND status = 'active' AND next_due_date BETWEEN ? AND ?
            GROUP BY event_date
            UNION ALL
            SELECT DATE(due_date) as event_date, SUM(amount) as total 
            FROM pending_ledger 
            WHERE user_id = ? AND type = 'expense' AND status = 'pending' AND due_date BETWEEN ? AND ?
            GROUP BY event_date
        ");
        $stmt->execute([$userId, $startDate, $endDate, $userId, $startDate, $endDate]);
        $expenseMap = [];
        foreach ($stmt->fetchAll() as $row) {
            $expenseMap[$row['event_date']] = ($expenseMap[$row['event_date']] ?? 0) + (float) $row['total'];
        }

        foreach ($simulations as $sim) {
            if (isset($sim['date'], $sim['type'], $sim['amount'])) {
                if ($sim['type'] === 'income') {
                    $incomeMap[$sim['date']] = ($incomeMap[$sim['date']] ?? 0) + (float) $sim['amount'];
                } else {
                    $expenseMap[$sim['date']] = ($expenseMap[$sim['date']] ?? 0) + (float) $sim['amount'];
                }
            }
        }

        $projection = [];
        $runningBalance = $currentBalance;
        $warnings = [];
        $totalIncome = 0;
        $totalExpense = 0;

        for ($i = 0; $i <= $days; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $dayIncome = $incomeMap[$date] ?? 0;
            $dayExpense = $expenseMap[$date] ?? 0;

            $runningBalance = $runningBalance + $dayIncome - $dayExpense;
            $totalIncome += $dayIncome;
            $totalExpense += $dayExpense;

            $projection[] = [
                'date' => $date,
                'label' => date('M d', strtotime($date)),
                'balance' => round($runningBalance, 2),
                'income' => $dayIncome,
                'expense' => $dayExpense
            ];

            if ($runningBalance < 0 && !in_array('cash_shortage', $warnings)) {
                $warnings[] = 'cash_shortage';
            }
        }

        return [
            'current_balance' => $currentBalance,
            'projection' => $projection,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_flow' => $totalIncome - $totalExpense,
            'final_balance' => $runningBalance,
            'warnings' => $warnings
        ];
    }
}