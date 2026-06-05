<?php
namespace models;
use core\Model;
use PDO;

class BudgetEntry extends Model {
    protected string $table = 'entries';

    public function getWithFrequencies(int $profile_id): array {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   MAX(f.frequency_type) as frequency_type, 
                   GROUP_CONCAT(f.is_first_half) as sm_halves, 
                   MAX(f.specific_day) as specific_day, 
                   MAX(f.specific_date) as specific_date, 
                   MAX(f.start_date) as start_date, 
                   MAX(f.end_date) as end_date, 
                   MAX(f.total_months) as total_months, 
                   MAX(f.months_paid) as months_paid, 
                   c.name as category_name 
            FROM {$this->table} e
            LEFT JOIN entry_frequencies f ON e.id = f.entry_id
            JOIN categories c ON e.category_id = c.id
            WHERE e.profile_id = :pid
            GROUP BY e.id
            ORDER BY c.sort_order ASC, e.name ASC
        ");
        $stmt->execute(['pid' => $profile_id]);
        return $stmt->fetchAll();
    }

    public function getActiveForMonth(int $profile_id, string $period_month): array {
        $all_active = $this->findAll(['profile_id' => $profile_id, 'is_active' => 1]);
        if (empty($all_active)) return [];
        
        $active_ids = array_column($all_active, 'id');
        $placeholders = implode(',', array_fill(0, count($active_ids), '?'));
        
        $stmt = $this->db->prepare("SELECT * FROM entry_frequencies WHERE entry_id IN ($placeholders)");
        $stmt->execute($active_ids);
        $all_freqs = $stmt->fetchAll();
        
        $freq_map = [];
        foreach ($all_freqs as $f) {
            $freq_map[$f['entry_id']][] = $f;
        }

        $due = [];
        $freqModel = new Frequency();

        foreach ($all_active as $entry) {
            if (!isset($freq_map[$entry['id']])) continue;
            
            foreach ($freq_map[$entry['id']] as $f) {
                if ($freqModel->isDueInMonth($f, $period_month)) {
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