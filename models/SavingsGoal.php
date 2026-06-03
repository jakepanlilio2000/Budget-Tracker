<?php
namespace models;
use core\Model;

class SavingsGoal extends Model {
    protected string $table = 'savings_goals';

    public function addFunds(int $id, float $amount): bool {
        $goal = $this->find($id);
        if (!$goal) return false;

        $newAmount = (float)$goal['current_amount'] + $amount;
        if ($newAmount < 0) $newAmount = 0;

        return $this->update($id, ['current_amount' => $newAmount]);
    }
}