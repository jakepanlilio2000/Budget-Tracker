# Changelog

All notable changes to **ExpensePro** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.0] - 2026-10-01: Mathematical Hardening & Defensive Architecture Release

A milestone release overhauling the core financial arithmetic, transaction concurrency, multi-currency ledger snapshotting, input validation, and user experience.

### Added
- **Arbitrary-Precision Math Engine (`App\Services\MathService`):**
  - BCMath implementation replacing all native PHP floating-point operations.
  - Standard Scale `2` for fiat currencies/balances; Scale `6` for exchange rates and fractional splits.
  - Implemented Banker's Rounding (Round-Half-to-Even) on decimal strings without float cast bias.
  - Double-entry invariant verification: $\sum(\text{debits}) = \sum(\text{credits}) + \text{fees}$ down to the last cent.
- **Centralized Validation Layer (`App\Services\RequestValidator`):**
  - Strict monetary regex validation `/^\d+(\.\d{1,2})?$/`, rejecting exponential notation (`1e6`), negative amounts, and non-numeric inputs.
  - ISO 4217 Currency Code Whitelist.
  - ISO 8601 Date Parsing (`Y-m-d`) with deterministic boundaries using `DateTimeImmutable`.
  - Anti-IDOR session ownership assertions for `account_id`, `category_id`, `budget_id`, and `vault_id`.
  - Replay and double-click idempotency tracking via client mutation tokens (`client_mutation_id`).
- **ACID Transaction & Concurrency Controls (`App\Models\TransactionModel`, `App\Models\AccountModel`):**
  - Wrapped all balance mutations and multi-item splits in explicit PDO transactions.
  - Implemented pessimistic row-level locking (`SELECT ... FOR UPDATE`) on account balance adjustments.
  - Deadlock-free inter-account transfers via ascending lock ordering (`min($id1, $id2)` first).
  - Explicit overdraft policy enforcement (`allow_overdraft` flag).
- **Automated Ledger Reconciliation Engine (`AccountService::reconcileAccount`):**
  - Command and service routine that recalculates stored account balances directly against the historical posted ledger.
  - Self-healing balance reconciliation with automated audit timeline logging.
- **Multi-Currency Snapshotting:**
  - Active foreign exchange rates, base currency IDs, and settled amounts are snapshotted at execution time (`rate_applied`, `settled_amount`).
  - Historical multi-currency budget tracking calculates against snapshotted rates rather than volatile dynamic rates.
- **Modern Fintech UI & Design System:**
  - Modernized Landing Page featuring live interactive split calculator widget and 3-step "How It Works" architectural tour.
  - First-run Onboarding Wizard in Dashboard guiding base currency selection, primary account linking, and budget setup.
  - Dynamic multi-state budget progress bars: Safe Green (<70%), Caution Amber (70-90%), Danger Red (90-100%), and Striped Crimson Overbudget (>100%).
  - Contextual info tooltips (`?`) explaining financial terminology and accounting mechanisms.
  - Filter chips to toggle budget view by risk profile (Safe, Caution, Critical, Overbudget).
- **Domain Exceptions (`App\Exceptions`):**
  - `FinancialException`, `ValidationException`, `InsufficientFundsException`, `IdempotencyException`, and `AuthorizationException`.

### Changed
- **Database Schema Upgrades (`database/migrations/v2_hardening_patch.sql`):**
  - Standardized monetary columns across all tables to `DECIMAL(15, 2)` (or `DECIMAL(18, 6)` for exchange rates).
  - Added indexes on high-frequency query filters: `(user_id, status, transaction_date)` and `(account_id, deleted_at)`.
  - Created `idempotency_keys` table for replay protection with 24-hour expiration window.
  - Created `fxp_ledger` audit table to record gamification event history.
- **Transaction Model & Controller Refactoring:**
  - Replaced legacy `Transaction::create` with ACID `TransactionModel::createWithSplits`.
  - Replaced raw float balance increments with `AccountModel::updateBalance`.
  - Refactored `BudgetController` and `Budget` model to delegate all multi-currency aggregation and rollover calculations to `BudgetService`.
- **Currency Service (`App\Models\CurrencyService`):**
  - Corrected PDO `FETCH_KEY_PAIR` column mapping (`SELECT id, exchange_rate`).
  - Refactored cross-currency conversions to execute via `MathService::convertCross`.

### Fixed
- **Floating-Point Drift:** Eliminated rounding leaks and fractional penny drift across split transactions and salary deductions.
- **Offline Sync Tolerance Leak:** Removed the loose `$drift <= 0.01` bypass in `SyncController`, enforcing exact cent reconciliation.
- **Gamification Farming Exploit:** Fixed duplicate XP awards on bill payments in `BillController`; added atomic XP clawback on transaction reversal/deletion.
- **Insecure Direct Object Reference (IDOR) Flaws:**
  - Patched IDOR in `Vault::deposit` and `Vault::withdraw` allowing unauthorized balance adjustments across users.
  - Patched IDOR in `Bill::advanceDueDate` allowing modification of other users' bill schedules.
- **Syntax / Blade Artifact:** Resolved unparsed raw PHP template tag `<?= url(...) ?>` in `public/assets/js/app.js`.

### Security
- Centralized anti-IDOR checks enforcing that all mutated resource IDs match the active authenticated session.
- Client mutation idempotency tokens blocking double-clicks and network replay attacks.
- Row-level pessimistic locks eliminating concurrent race-condition overdrafts.
- Defense-in-depth sanitization of all incoming monetary values before database execution.

---

## [1.0.0] - 2026-07-16: Initial Public Release

The initial public release of ExpensePro, introducing the vanilla PHP MVC financial tracking architecture with gamification features.