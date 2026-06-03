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

        $stmt = $db->prepare("
            SELECT e.*, f.frequency_type, f.total_months, f.months_paid, c.name as category_name, c.color as category_color, c.icon as category_icon
            FROM entries e
            JOIN entry_frequencies f ON e.id = f.entry_id
            JOIN categories c ON e.category_id = c.id
            WHERE e.profile_id = :pid AND e.is_active = 1 AND e.type = 'outflow'
            AND f.frequency_type IN ('monthly', 'annual', 'custom_months')
            ORDER BY e.amount DESC
        ");
        $stmt->execute(['pid' => $profile_id]);
        $activeOutflows = $stmt->fetchAll();

        $subscriptions = [];
        $debts = [];
        $monthlyFixed = 0.00;

        foreach ($activeOutflows as $item) {
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