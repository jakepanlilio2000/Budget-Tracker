<?php
namespace models;
use core\Model;
use PDO;

class BudgetEntry extends Model {
    protected string $table = 'entries';

    public function getWithFrequencies(int $profile_id): array {
        $stmt = $this->db->prepare("
            SELECT e.*, f.frequency_type, f.is_first_half, f.specific_day, f.start_date, f.end_date, f.total_months, f.months_paid, c.name as category_name 
            FROM {$this->table} e
            LEFT JOIN entry_frequencies f ON e.id = f.entry_id
            JOIN categories c ON e.category_id = c.id
            WHERE e.profile_id = :pid
            ORDER BY c.sort_order ASC, e.name ASC
        ");
        $stmt->execute(['pid' => $profile_id]);
        return $stmt->fetchAll();
    }

    public function getActiveForPeriod(int $profile_id, string $period_date, array $profile): array {
        $all_active = $this->findAll(['profile_id' => $profile_id, 'is_active' => 1]);
        $due = [];
        $freqModel = new Frequency();

        foreach ($all_active as $entry) {
            $stmt = $this->db->prepare("SELECT * FROM entry_frequencies WHERE entry_id = :eid");
            $stmt->execute(['eid' => $entry['id']]);
            $freqs = $stmt->fetchAll();

            foreach ($freqs as $f) {
                if ($freqModel->isDueInPeriod($f, $period_date, $profile)) {
                    if ($f['frequency_type'] === 'monthly' && $profile['pay_schedule'] === 'semi_monthly') {
                        $entry['amount'] = number_format((float)$entry['amount'] / 2, 2, '.', '');
                    }
                    
                    $due[] = $entry;
                    break;
                }
            }
        }
        return $due;
    }
    
    public function toggleActive(int $id): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_active = NOT is_active WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}