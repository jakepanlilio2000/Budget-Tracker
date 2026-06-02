<?php
namespace models;
use core\Model;

class Category extends Model {
    protected string $table = 'categories';

    public function reorder(int $profile_id, array $category_ids): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET sort_order = :order WHERE id = :id AND profile_id = :pid");
            foreach ($category_ids as $index => $id) {
                $stmt->execute([
                    'order' => $index + 1,
                    'id' => (int)$id,
                    'pid' => $profile_id
                ]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}