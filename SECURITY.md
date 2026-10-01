# Security Policy & Defensive Architecture

## Supported Versions

ExpensePro is an institutional-grade financial operating system. We maintain active security patches for supported versions:

| Version | Status | Security Maintenance |
| :--- | :--- | :--- |
| **2.x.x** | :white_check_mark: Active Production | Full Security Support & Hardening Patches |
| **1.x.x** | :x: Deprecated | End of Life (Upgrade to 2.0.0 Recommended) |

---

## Vulnerability Disclosure & Responsible Reporting

If you identify a security flaw, vulnerability, or precision bypass in ExpensePro, please report it privately:

- **Security Team Contact:** `security@expensepro.local`
- **PGP Fingerprint:** `4A9F B821 73C0 E158 9204  F281 99E2 314A 88B1 C990`
- **Please DO NOT open public GitHub issues for security vulnerabilities.**

### Disclosure Timeline
1. **Initial Acknowledgment:** Within **24 hours**.
2. **Triaging & Confirmation:** Within **72 hours**.
3. **Patch Release & Security Advisory:** Target resolution within **10 business days**.
4. **Public Recognition:** You will be credited in our Release Advisories upon publication (unless you request anonymity).

---

## Threat Model & Attack Vectors

ExpensePro protects financial ledgers against specific failure modes and adversarial threats:

### 1. Arithmetic & Precision Exploitation
- **Threat:** Submitting scientific notation strings (`1e6`), negative signs, or micro-cents to induce fractional float truncation or negative balance corruption.
- **Defense:** Strict regex enforcement `/^\d+(\.\d{1,2})?$/` via `RequestValidator::validateAmount()`. All arithmetic executes via arbitrary-precision strings in `MathService` with Scale 2 / Scale 6 bounds.

### 2. Concurrency & Race-Condition Overdrafts
- **Threat:** Firing rapid simultaneous requests to withdraw funds from an account faster than balance updates can persist (Time-of-Check to Time-of-Use).
- **Defense:** Pessimistic row-level locking (`SELECT balance FROM accounts WHERE id = :id FOR UPDATE`) inside explicit PDO transactions. Transactions only commit when balance constraints are satisfied.

### 3. Insecure Direct Object References (IDOR)
- **Threat:** Manipulating `account_id`, `category_id`, `budget_id`, or `vault_id` parameters in HTTP requests to tamper with another user's accounts, budgets, or vaults.
- **Defense:** Mandatory session-scoped assertions prior to every state mutation:
  ```php
  RequestValidator::verifyAccountOwnership($db, $accountId, $userId);
  RequestValidator::verifyVaultOwnership($db, $vaultId, $userId);
  ```

### 4. Network Retry & Replay Double-Charging
- **Threat:** Unstable network connections causing clients to re-send transaction submissions, leading to duplicate debits.
- **Defense:** Client-supplied `client_mutation_id` (UUID v4) stored in the `idempotency_keys` table. Duplicate attempts within 24 hours return the cached response without re-executing balance mutations.

### 5. Gamification / XP Farming
- **Threat:** Continuously creating and deleting transactions or paying fake bills to artificially boost user level, streaks, and badges.
- **Defense:** Unique composite keys in `fxp_ledger` (`user_id`, `event_type`, `reference_id`) preventing repeated rewards. Atomic reward rollbacks occur on transaction reversal.

---

## Native Defense-in-Depth Controls

- **SQL Injection Prevention:** 100% of database interactions execute through PDO prepared statements with strongly typed parameter bindings.
- **Cross-Site Request Forgery (CSRF):** Cryptographically secure, session-bound CSRF tokens validate all state-modifying requests (`POST`, `PUT`, `DELETE`).
- **Cross-Site Scripting (XSS):** Universal sanitization via `htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` wrapped in the `e()` helper function.
- **Password Hashing:** Passwords utilize PHP's `password_hash()` utilizing Argon2id or Bcrypt with dynamic cost scaling.
- **Cookie & Session Hygiene:** Session cookies enforce `HttpOnly`, `SameSite=Lax` (or `Strict`), and `Secure` attributes in production environments.
- **Pessimistic Inter-Account Deadlock Ordering:** Accounts are locked in ascending primary key order (`min($id1, $id2)`) to eliminate multi-threaded database deadlocks.

---

## Security Audit Checklist for Contributors

Before submitting pull requests:
- [ ] No monetary calculation uses float operators (`+`, `-`, `*`, `/`).
- [ ] Every balance mutation runs inside `beginTransaction()` / `commit()` / `rollBack()`.
- [ ] Every account query for balance deduction executes with `FOR UPDATE`.
- [ ] All resource IDs are asserted to belong to `Auth::id()`.
- [ ] No unescaped variables are rendered in views.