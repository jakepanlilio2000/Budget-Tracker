<?php
namespace models;
use core\Model;
use DateTime;

class Frequency extends Model {
    protected string $table = 'entry_frequencies';

    public function isDueInMonth(array $freq, string $period_month): bool {
        $periodStart = new DateTime($period_month . '-01');
        $periodEnd = new DateTime($period_month . '-' . $periodStart->format('t'));
        
        if (!empty($freq['start_date'])) {
            $startDate = new DateTime($freq['start_date']);
            if ($startDate > $periodEnd) return false;
        }
        
        if (!empty($freq['end_date'])) {
            $endDate = new DateTime($freq['end_date']);
            if ($endDate < $periodStart) return false;
        }

        return match($freq['frequency_type']) {
            'one_time' => substr($freq['specific_date'] ?? '', 0, 7) === $period_month,
            'custom_months' => $this->checkInstallmentMonth($freq, $period_month),
            default => true
        };
    }

    private function checkInstallmentMonth(array $freq, string $period_month): bool {
        
        if (isset($freq['months_paid']) && $freq['total_months'] > 0 && $freq['months_paid'] >= $freq['total_months']) {
            return false;
        }
        
     
        $start_raw = !empty($freq['start_date']) ? $freq['start_date'] : ($freq['created_at'] ?? null);
        
        
        if (empty($start_raw)) return true; 
        $startY = (int)substr($start_raw, 0, 4);
        $startM = (int)substr($start_raw, 5, 2);
        
        $currY = (int)substr($period_month, 0, 4);
        $currM = (int)substr($period_month, 5, 2);

        $monthsElapsed = (($currY - $startY) * 12) + ($currM - $startM);

        if ($monthsElapsed < 0) {
            return false; 
        }
        
        if (!empty($freq['total_months']) && $monthsElapsed >= (int)$freq['total_months']) {
            return false; 
        }
        
        return true;
    }
}