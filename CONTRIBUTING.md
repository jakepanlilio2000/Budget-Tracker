# Contributing to ExpensePro

Thank you for your interest in contributing to **ExpensePro**! As a financial operating system managing mission-critical monetary ledgers, we hold our codebase to institutional-grade software engineering, mathematical precision, and defensive security standards.

---

## Table of Contents
- [Code of Conduct](#code-of-conduct)
- [Core Engineering Principles](#core-engineering-principles)
- [Coding Standards & Guidelines](#coding-standards--guidelines)
  - [PHP 8.x Strict Typing](#php-8x-strict-typing)
  - [Zero-Float Monetary Calculations (`bcmath`)](#zero-float-monetary-calculations-bcmath)
  - [Concurrency & Database Mutations](#concurrency--database-mutations)
  - [Authorization & Anti-IDOR Hygiene](#authorization--anti-idor-hygiene)
- [Development Workflow](#development-workflow)
- [Branch Naming Conventions](#branch-naming-conventions)
- [Commit Message Specification](#commit-message-specification)
- [Testing & Quality Assurance](#testing--quality-assurance)
- [Pull Request Checklist](#pull-request-checklist)

---

## Code of Conduct
All contributors and maintainers are expected to uphold a professional, respectful, and harassment-free environment. See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for details.

---

## Core Engineering Principles

1. **Mathematical Invariance:** Double-entry ledger invariants are non-negotiable. $\sum(\text{debits})$ must equal $\sum(\text{credits}) + \text{fees}$ down to the last cent.
2. **Deterministic Time Boundaries:** Date arithmetic must use `DateTimeImmutable` to prevent leap-year or month-end rollover bugs (e.g. Jan 31 rolling into March).
3. **Pessimistic Concurrency:** Read-modify-write ledger operations must acquire row-level locks (`FOR UPDATE`) inside explicit transactions to eliminate race conditions.
4. **Zero Trust Session Scope:** No entity mutation may proceed without asserting that `account_id`, `category_id`, `budget_id`, and `vault_id` belong to the authenticated session user.

---

## Coding Standards & Guidelines

### PHP 8.x Strict Typing
Every PHP source file MUST begin with strict type declarations:
```php
<?php
declare(strict_types=1);

namespace App\Services;

class ExampleService
{
    public function calculateFee(string $amount, string $rate): string
    {
        // ...
    }
}
```
- Declare explicit parameter types and explicit return types (`void`, `array`, `string`, `bool`, etc.).
- Never use untyped variables or mixed returns when specific types are known.

### Zero-Float Monetary Calculations (`bcmath`)
- **Prohibited:** `+`, `-`, `*`, `/`, `round()`, `number_format()`, `floatval()`, `(float)`.
- **Mandatory:** Use `App\Services\MathService` wrappers around PHP's `bcmath` extension:
  - Standard balances and transactions: **Scale `2`**.
  - Foreign exchange rates and splits: **Scale `6`**.
  - Banker's Rounding: Use `MathService::roundHalfToEven()` on decimal strings.

### Concurrency & Database Mutations
- All multi-step ledger mutations must be wrapped in `try { $db->beginTransaction(); ... $db->commit(); } catch (\Throwable $e) { $db->rollBack(); throw $e; }`.
- Always lock account rows with `SELECT ... FOR UPDATE` before applying balance adjustments.
- When locking multiple accounts (e.g., transfers), always sort account IDs in ascending order (`min($id1, $id2)` first) to prevent distributed deadlocks.

### Authorization & Anti-IDOR Hygiene
- Always validate incoming IDs against the authenticated user's session:
```php
RequestValidator::verifyAccountOwnership($db, $accountId, $userId);
RequestValidator::verifyCategoryOwnership($db, $categoryId, $userId);
RequestValidator::verifyBudgetOwnership($db, $budgetId, $userId);
RequestValidator::verifyVaultOwnership($db, $vaultId, $userId);
```
- Validate all monetary inputs via `RequestValidator::validateAmount()`, which enforces `/^\d+(\.\d{1,2})?$/` and rejects negative values or scientific notation (`1e6`).

---

## Development Workflow

1. **Fork & Clone:**
   ```bash
   git clone https://github.com/your-username/Budget-Tracker.git
   cd Budget-Tracker
   ```

2. **Branch Creation:**
   ```bash
   git checkout -b feature/hardened-tax-engine
   ```

3. **Local Testing:**
   Ensure syntax validity across modified files:
   ```bash
   php -l app/Services/MathService.php
   php -l app/Models/TransactionModel.php
   ```

4. **Run Verification Test Suite:**
   ```bash
   php scratch/test_suite.php
   ```

---

## Branch Naming Conventions

Use category-prefixed, hyphen-separated branch names:
- `feature/` : New features or major capabilities (e.g., `feature/recurring-salary-splits`)
- `fix/` : Bug fixes or vulnerability remediation (e.g., `fix/idor-vault-withdrawal`)
- `refactor/` : Code cleanup without behavior change (e.g., `refactor/bcmath-analytics`)
- `security/` : Critical security hardening (e.g., `security/idempotency-token-lock`)
- `docs/` : Documentation improvements (e.g., `docs/scale-precision-matrix`)

---

## Commit Message Specification

Follow Conventional Commits format:
```
<type>(<scope>): <short summary>

[optional body explaining rationale and mathematical/security impact]

[optional footer(s) referencing issue number]
```

**Types:**
- `feat`: A new feature or endpoint
- `fix`: A bug fix or precision remediation
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `perf`: Performance improvement (e.g., query indexing)
- `test`: Adding or correcting tests
- `docs`: Documentation updates

**Example:**
```
fix(ledger): enforce ascending lock order to eliminate transfer deadlocks

When transferring funds between account #14 and #5 concurrently, threads 
could deadlock acquiring row-level locks in inverted order. This sorts 
account IDs before invoking findByIdForUpdate().

Resolves #142
```

---

## Testing & Quality Assurance

Before submitting a pull request, verify:
1. **Precision Boundary Tests:**
   - Verify splitting `$100.00` three ways yields `$33.34 + $33.33 + $33.33 = $100.00` with zero cent loss.
   - Verify currency cross-rates using Scale 6 without intermediary float casts.
2. **Concurrency & Rollback Tests:**
   - Assert that an exception thrown inside a transaction triggers complete rollback.
   - Assert that `allow_overdraft = 0` rejects transactions exceeding available funds.
3. **Idempotency Replay Protection:**
   - Assert that submitting duplicate `client_mutation_id` within 24 hours does not double-charge an account.

---

## Pull Request Checklist

- [ ] All new and modified PHP files declare `declare(strict_types=1);`.
- [ ] No native floating-point arithmetic is performed on financial fields.
- [ ] Database mutations use explicit transactions and pessimistic row locks.
- [ ] Anti-IDOR session ownership assertions are in place for all mutated entities.
- [ ] Code passes `php -l` linting without warnings or errors.
- [ ] Database schema changes include idempotent migration scripts in `database/migrations/`.
- [ ] Documentation has been updated to reflect API or UI alterations.