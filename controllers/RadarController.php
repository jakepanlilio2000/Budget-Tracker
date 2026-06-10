<?php
namespace controllers;
use core\Controller;
use config\Database;
use models\Profile;

class RadarController extends Controller {
    public function index(int $profile_id): void {
        $db = Database::getInstance();
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile) $this->redirect('/');

        $currentMonthDate = date('Y-m') . '-01';

        $stmt = $db->prepare("
            SELECT e.*, f.frequency_type, f.total_months, c.name as category_name, c.color as category_color, c.icon as category_icon,
            (
                SELECT SUM(
                    CASE 
                        WHEN t.is_checked = 1 THEN 1 
                        WHEN e.amount > 0 AND t.amount <= 0 THEN 1
                        WHEN e.amount > 0 AND t.amount < e.amount THEN (e.amount - t.amount) / e.amount
                        ELSE 0 
                    END
                ) 
                FROM transactions t 
                WHERE t.entry_id = e.id
            ) as calculated_months_paid,
            t_curr.is_checked as curr_is_checked,
            t_curr.amount as curr_tx_amount
            FROM entries e
            JOIN entry_frequencies f ON e.id = f.entry_id
            JOIN categories c ON e.category_id = c.id
            LEFT JOIN transactions t_curr ON t_curr.entry_id = e.id AND t_curr.period_date = :c_month
            WHERE e.profile_id = :pid AND e.is_active = 1 AND e.type = 'outflow'
            AND f.frequency_type IN ('monthly', 'annual', 'custom_months')
            ORDER BY e.amount DESC
        ");
        $stmt->execute(['pid' => $profile_id, 'c_month' => $currentMonthDate]);
        $activeOutflows = $stmt->fetchAll();

        $subscriptions = [];
        $debts = [];
        $monthlyFixed = 0.00;

        foreach ($activeOutflows as &$item) {
            
            $monthsTotal = (int)$item['total_months'];
            $monthsPaidRaw = (float)($item['calculated_months_paid'] ?? 0);
            $isCleared = ($item['frequency_type'] === 'custom_months' && $monthsTotal > 0 && $monthsPaidRaw >= $monthsTotal);

            if ($isCleared) {
                $statusHtml = '<span style="font-size: 11px; background: rgba(63,185,80,0.1); color: var(--accent-green); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--accent-green);"><i class="fa-solid fa-flag-checkered"></i> Cleared</span>';
            } else {
                $currPaidFraction = 0;
                if ($item['curr_tx_amount'] !== null && $item['amount'] > 0) {
                    if ($item['curr_is_checked'] == 1 || $item['curr_tx_amount'] <= 0) {
                        $currPaidFraction = 1;
                    } elseif ($item['curr_tx_amount'] < $item['amount']) {
                        $currPaidFraction = ($item['amount'] - $item['curr_tx_amount']) / $item['amount'];
                    }
                }

                if ($item['curr_tx_amount'] === null) {
                    $statusHtml = '<span style="font-size: 11px; color: var(--text-muted);"><i class="fa-solid fa-clock"></i> Not Generated</span>';
                } elseif ($currPaidFraction >= 1) {
                    $statusHtml = '<span style="font-size: 11px; background: rgba(63,185,80,0.1); color: var(--accent-green); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--accent-green);"><i class="fa-solid fa-check-circle"></i> Paid</span>';
                } elseif ($currPaidFraction > 0) {
                    $statusHtml = '<span style="font-size: 11px; background: rgba(88,166,255,0.1); color: var(--accent-blue); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--accent-blue);"><i class="fa-solid fa-circle-half-stroke"></i> Partial</span>';
                } else {
                    $statusHtml = '<span style="font-size: 11px; background: rgba(248,81,73,0.1); color: var(--accent-red); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--accent-red);"><i class="fa-solid fa-circle-xmark"></i> Unpaid</span>';
                }
            }
            $item['status_badge'] = $statusHtml;

            if ($item['frequency_type'] === 'custom_months') {
                $debts[] = $item;
            } else {
                $subscriptions[] = $item;
                if ($item['frequency_type'] === 'monthly') {
                    $monthlyFixed += (float)$item['amount'];
                } elseif ($item['frequency_type'] === 'annual') {
                    $monthlyFixed += ((float)$item['amount'] / 12);
                }
            }
        }

        $this->view('radar/index', [
            'profile' => $profile,
            'subscriptions' => $subscriptions,
            'debts' => $debts,
            'monthlyFixed' => $monthlyFixed
        ]);
    }
}