<?php
namespace models;
use core\Model;
use DateTime;

class Frequency extends Model {
    protected string $table = 'entry_frequencies';

    public function isDueInPeriod(array $freq, string $period_date, array $profile): bool {
        $period = new DateTime($period_date);
        
        if ($freq['start_date'] && $period_date < $freq['start_date']) return false;
        if ($freq['end_date'] && $period_date > $freq['end_date']) return false;

        
        if ($freq['frequency_type'] === 'monthly' && $profile['pay_schedule'] === 'semi_monthly') {
            $day = (int)$period->format('d');
            $lastDay = (int)$period->format('t');
            $payDay2 = (int)$profile['pay_day_2'];
            $actualDay2 = ($payDay2 > $lastDay) ? $lastDay : $payDay2;
            
            return ($day === (int)$profile['pay_day_1'] || $day === $actualDay2);
        }

        return match($freq['frequency_type']) {
            'one_time' => $freq['specific_date'] === $period_date,
            'monthly' => (int)$period->format('d') === (int)$freq['specific_day'],
            'semi_monthly' => $this->checkSemiMonthly($freq, $period, $profile),
            'custom_months' => $this->checkInstallment($freq, $period),
            default => false
        };
    }

    private function checkSemiMonthly(array $freq, DateTime $period, array $profile): bool {
        $day = (int)$period->format('d');
        if ($freq['is_first_half'] && $day === (int)$profile['pay_day_1']) return true;
        
        $lastDay = (int)$period->format('t');
        $payDay2 = (int)$profile['pay_day_2'];
        $actualDay2 = ($payDay2 > $lastDay) ? $lastDay : $payDay2;
        
        if (!$freq['is_first_half'] && $day === $actualDay2) return true;
        
        return false;
    }

    private function checkInstallment(array $freq, DateTime $period): bool {
        if ($freq['months_paid'] >= $freq['total_months']) return false;
        return (int)$period->format('d') === (int)$freq['specific_day'];
    }
}