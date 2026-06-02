<?php
namespace models;
use core\Model;
use DateTime;

class Profile extends Model {
    protected string $table = 'profiles';

    public function getActivePeriods(int $profile_id, int $year): array {
        $profile = $this->find($profile_id);
        if (!$profile) return [];

        $periods = [];
        $schedule = $profile['pay_schedule'];
        if ($schedule === 'semi_monthly') {
            $day1 = $profile['pay_day_1'];
            $day2 = $profile['pay_day_2'];
            
            for ($month = 1; $month <= 12; $month++) {
                $date1 = sprintf('%04d-%02d-%02d', $year, $month, $day1);
                $periods[] = $date1;
                $lastDay = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))->format('t');
                $actualDay2 = ($day2 > $lastDay) ? $lastDay : $day2;
                $date2 = sprintf('%04d-%02d-%02d', $year, $month, $actualDay2);
                $periods[] = $date2;
            }
        }
        return $periods;
    }

    public function calculateSummary(int $profile_id, string $period_date): array {
        $stmt = $this->db->prepare("
            SELECT type, SUM(amount) as total 
            FROM transactions 
            WHERE profile_id = :pid AND period_date = :pdate AND is_checked = 1 
            GROUP BY type
        ");
        $stmt->execute(['pid' => $profile_id, 'pdate' => $period_date]);
        $rows = $stmt->fetchAll();
        $inflow = 0.00;
        $outflow = 0.00;
        foreach ($rows as $row) {
            if ($row['type'] === 'inflow') $inflow = (float)($row['total'] ?? 0);
            if ($row['type'] === 'outflow') $outflow = (float)($row['total'] ?? 0);
        }

        $net = $inflow - $outflow;
        $year = substr($period_date, 0, 4);
        $stmtCum = $this->db->prepare("
            SELECT type, SUM(amount) as total 
            FROM transactions 
            WHERE profile_id = :pid AND period_date <= :pdate AND period_date >= :ydate AND is_checked = 1 
            GROUP BY type
        ");
        $stmtCum->execute(['pid' => $profile_id, 'pdate' => $period_date, 'ydate' => "$year-01-01"]);
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
            $stmt1 = $this->db->prepare("DELETE FROM transactions WHERE profile_id = :id");
            $stmt1->execute(['id' => $id]);

            $stmt2 = $this->db->prepare("DELETE FROM entries WHERE profile_id = :id");
            $stmt2->execute(['id' => $id]);

            $stmt3 = $this->db->prepare("DELETE FROM categories WHERE profile_id = :id");
            $stmt3->execute(['id' => $id]);

            $stmt4 = $this->db->prepare("DELETE FROM calculator_sessions WHERE profile_id = :id");
            $stmt4->execute(['id' => $id]);

            $stmt5 = $this->db->prepare("DELETE FROM profiles WHERE id = :id");
            $stmt5->execute(['id' => $id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}