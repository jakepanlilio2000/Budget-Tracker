<?php
declare(strict_types=1);

namespace App\Events;

/**
 * Domain Events for Budget Lifecycle and Spending Limits.
 */
class BudgetEvent
{
    public const ALLOCATED = 'budget.allocated';
    public const CYCLE_COMPLETED = 'budget.cycle_completed';
    public const OVERRUN = 'budget.overrun';
    public const ROLLED_OVER = 'budget.rolled_over';
}
