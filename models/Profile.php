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
            SELECT type, SUM(amount) as total 
            FROM transactions 
            WHERE profile_id = :pid AND DATE_FORMAT(period_date, '%Y-%m') = :pdate AND is_checked = 1 
            GROUP BY type
        ");
        $stmt->execute(['pid' => $profile_id, 'pdate' => $period_month]);
        $rows = $stmt->fetchAll();
        $inflow = 0.00;
        $outflow = 0.00;
        foreach ($rows as $row) {
            if ($row['type'] === 'inflow') $inflow = (float)($row['total'] ?? 0);
            if ($row['type'] === 'outflow') $outflow = (float)($row['total'] ?? 0);
        }

        $net = $inflow - $outflow;
        $year = substr($period_month, 0, 4);
        
        $stmtCum = $this->db->prepare("
            SELECT type, SUM(amount) as total 
            FROM transactions 
            WHERE profile_id = :pid AND DATE_FORMAT(period_date, '%Y-%m') <= :pdate AND DATE_FORMAT(period_date, '%Y') = :ydate AND is_checked = 1 
            GROUP BY type
        ");
        $stmtCum->execute(['pid' => $profile_id, 'pdate' => $period_month, 'ydate' => $year]);
        $cumRows = $stmtCum->fetchAll();
        
        $cumIn = 0.00;
        $cumOut = 0.00;
        foreach ($cumRows as $row) {
            if ($row['type'] === 'inflow') $cumIn = (float)($row['total'] ?? 0);
            if ($row['type'] === 'outflow') $cumOut = (float)($row['total'] ?? 0);
        }
        $cumulative = $cumIn - $cumOut;
        return [
            'total_inflow' => number_format($inflow, 2, '.', ''),
            'total_outflow' => number_format($outflow, 2, '.', ''),
            'net' => number_format($net, 2, '.', ''),
            'cumulative' => number_format($cumulative, 2, '.', '')
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