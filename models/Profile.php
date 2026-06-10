<?php
namespace models;
use core\Model;
use DateTime;

class Profile extends Model {
    protected string $table = 'profiles';

    public function getActivePeriods(int $profile_id, int $year): array {
        $periods = [];
        for ($month = 1; $month <= 12; $month++) {
            $periods[] = sprintf('%04d-%02d', $year, $month);
        }
        return $periods;
    }

    public function calculateSummary(int $profile_id, string $period_month): array {
        $stmt = $this->db->prepare("
            SELECT t.id, t.type, t.is_checked, t.amount as current_amount, e.amount as master_amount, c.type as cat_type
            FROM transactions t
            JOIN entries e ON t.entry_id = e.id
            JOIN categories c ON t.category_id = c.id
            WHERE t.profile_id = :pid AND DATE_FORMAT(t.period_date, '%Y-%m') = :pdate
        ");
        $stmt->execute(['pid' => $profile_id, 'pdate' => $period_month]);
        $rows = $stmt->fetchAll();
        
        $planned_in = 0.0; $actual_in = 0.0;
        $planned_out = 0.0; $actual_out = 0.0;
        $planned_save = 0.0; $actual_save = 0.0;

        foreach ($rows as $r) {
            $c_amt = (float)$r['current_amount'];
            $m_amt = (float)$r['master_amount'];
            $totality = max($m_amt, $c_amt);

            $paid = 0.0;
            if ($r['is_checked']) {
                $paid = $totality;
            } else if ($c_amt < $m_amt) {
                $paid = $m_amt - $c_amt;
            }

            if ($r['type'] === 'inflow') {
                $planned_in += $totality;
                $actual_in += $paid;
            } else {
                if ($r['cat_type'] === 'savings') {
                    $planned_save += $totality;
                    $actual_save += $paid;
                } else {
                    $planned_out += $totality;
                    $actual_out += $paid;
                }
            }
        }

        // FIXED: Cumulative now strictly relies on Executed Realized Math
        $year = substr($period_month, 0, 4);
        $stmtCum = $this->db->prepare("
            SELECT t.type, t.is_checked, t.amount as current_amount, e.amount as master_amount
            FROM transactions t
            JOIN entries e ON t.entry_id = e.id
            WHERE t.profile_id = :pid AND DATE_FORMAT(t.period_date, '%Y-%m') <= :pdate AND DATE_FORMAT(t.period_date, '%Y') = :ydate
        ");
        $stmtCum->execute(['pid' => $profile_id, 'pdate' => $period_month, 'ydate' => $year]);
        $cumRows = $stmtCum->fetchAll();
        
        $cumIn = 0.0; $cumOut = 0.0;
        foreach ($cumRows as $r) {
            $totality = max((float)$r['master_amount'], (float)$r['current_amount']);
            
            // Only add to cumulative if it's checked or partially paid
            $paid = $r['is_checked'] ? $totality : ($r['current_amount'] < $totality ? $totality - $r['current_amount'] : 0);
            
            if ($r['type'] === 'inflow') $cumIn += $paid;
            if ($r['type'] === 'outflow') $cumOut += $paid;
        }

        return [
            'total_inflow' => number_format($planned_in, 2, '.', ''),
            'total_outflow' => number_format($planned_out + $planned_save, 2, '.', ''),
            'net' => number_format($planned_in - ($planned_out + $planned_save), 2, '.', ''),
            'cumulative' => number_format($cumIn - $cumOut, 2, '.', ''),
            'actual_in' => number_format($actual_in, 2, '.', ''),
            'actual_out' => number_format($actual_out, 2, '.', ''),
            'actual_save' => number_format($actual_save, 2, '.', ''),
            'planned_save' => number_format($planned_save, 2, '.', '')
        ];
    }

    public function deleteProfileFull(int $id): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM entries WHERE profile_id = :id");
            $stmt->execute(['id' => $id]);
            $this->delete($id);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}