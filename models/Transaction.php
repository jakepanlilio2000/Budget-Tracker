<?php
namespace models;
use core\Model;
use PDO;

class Transaction extends Model {
    protected string $table = 'transactions';

    public function getForMonth(int $profile_id, string $period_month): array {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as category_name, c.color as category_color, c.type as category_type,
                   e.amount as master_amount
            FROM {$this->table} t
            JOIN categories c ON t.category_id = c.id
            JOIN entries e ON t.entry_id = e.id
            WHERE t.profile_id = :pid AND DATE_FORMAT(t.period_date, '%Y-%m') = :pdate
            ORDER BY c.sort_order ASC, t.id ASC
        ");
        $stmt->execute(['pid' => $profile_id, 'pdate' => $period_month]);
        return $stmt->fetchAll();
    }

    public function syncMonth(int $profile_id, string $period_month, array $due_entries): void {
        $existing = $this->getForMonth($profile_id, $period_month);
        $existing_entry_ids = array_column($existing, 'entry_id');

        $to_insert = [];
        foreach ($due_entries as $entry) {
            if (!in_array($entry['id'], $existing_entry_ids)) {
                $to_insert[] = $entry;
            }
        }

        if (empty($to_insert)) return;

        $period_date_db = $period_month . '-01';
        $sql = "INSERT INTO {$this->table} (profile_id, entry_id, category_id, name, amount, type, period_date, is_checked) VALUES ";
        $values = [];
        $params = [];
        $i = 0;

        foreach ($to_insert as $entry) {
            $values[] = "(:pid{$i}, :eid{$i}, :cid{$i}, :name{$i}, :amt{$i}, :type{$i}, :pdate{$i}, :chk{$i})";
            $params["pid{$i}"] = $profile_id;
            $params["eid{$i}"] = $entry['id'];
            $params["cid{$i}"] = $entry['category_id'];
            $params["name{$i}"] = $entry['name'];
            $params["amt{$i}"] = $entry['amount'];
            $params["type{$i}"] = $entry['type'];
            $params["pdate{$i}"] = $period_date_db;
            
            // FIXED: Do NOT check future transactions by default!
            $params["chk{$i}"] = 0; 
            $i++;
        }

        $stmt = $this->db->prepare($sql . implode(', ', $values));
        $stmt->execute($params);
    }

    public function toggleCheck(int $id, bool $state): bool {
        return $this->update($id, ['is_checked' => $state ? 1 : 0]);
    }

    public function updateAmount(int $id, string $amount): bool {
        return $this->update($id, ['amount' => $amount]);
    }
}