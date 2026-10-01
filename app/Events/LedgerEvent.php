<?php
declare(strict_types=1);

namespace App\Events;

/**
 * Domain Events for Financial Ledger Mutations.
 * Used by the decoupled event-driven pipeline for audit logging,
 * automated reconciliation triggers, and idempotent gamification progress.
 */
class LedgerEvent
{
    public const CREATED = 'ledger.created';
    public const REVERSED = 'ledger.reversed';
    public const DELETED = 'ledger.deleted';
    public const RECONCILED = 'ledger.reconciled';
}
