<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use DateTimeImmutable;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\IdempotencyException;

/**
 * Enterprise Request Validation & Input Hygiene Component.
 * Enforces strict financial input boundaries, ISO standards, cross-tenant isolation,
 * and ACID-backed idempotency guards.
 */
class RequestValidator
{
    /**
     * ISO 4217 Whitelist of standard international currencies.
     */
    public const ISO_4217_WHITELIST = [
        'USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'PHP', 'SGD',
        'HKD', 'NZD', 'SEK', 'KRW', 'INR', 'BRL', 'ZAR', 'MXN', 'NOK', 'DKK',
        'PLN', 'THB', 'MYR', 'IDR', 'CZK', 'ILS', 'CLP', 'AED', 'SAR', 'TWD'
    ];

    /**
     * Validates a monetary amount string:
     * - Must be positive (> 0.00)
     * - Must match decimal structure (/^\d+(\.\d{1,2})?$/)
     * - Rejects scientific notation, negative numbers, zeroes, strings, and integer overflows.
     */
    public static function validateAmount(mixed $amount, string $fieldName = 'amount', int $maxScale = 2): string
    {
        if ($amount === null || $amount === '') {
            throw new ValidationException("{$fieldName} is required", [$fieldName => 'Field is required']);
        }

        $strAmount = trim((string) $amount);

        // Disallow scientific notation
        if (preg_match('/[eE]/', $strAmount)) {
            throw new ValidationException("Scientific notation is forbidden for {$fieldName}", [$fieldName => 'Scientific notation disallowed']);
        }

        // Regex enforce strictly positive decimal structure
        $pattern = '/^\d+(\.\d{1,' . $maxScale . '})?$/';
        if (!preg_match($pattern, $strAmount)) {
            throw new ValidationException(
                "{$fieldName} must be a valid positive decimal with up to {$maxScale} decimal places",
                [$fieldName => "Invalid format. Expected up to {$maxScale} decimal digits"]
            );
        }

        // Must be strictly greater than zero
        if (MathService::lte($strAmount, '0', $maxScale)) {
            throw new ValidationException("{$fieldName} must be strictly greater than zero", [$fieldName => 'Must be > 0.00']);
        }

        // Upper sanity boundary check (prevent astronomical numbers: max 1 trillion)
        if (MathService::gt($strAmount, '1000000000000', $maxScale)) {
            throw new ValidationException("{$fieldName} exceeds maximum allowable boundary", [$fieldName => 'Value exceeds limit']);
        }

        return MathService::round($strAmount, $maxScale);
    }

    /**
     * Validates an ISO 4217 currency code.
     */
    public static function validateCurrencyCode(string $code): string
    {
        $normalized = strtoupper(trim($code));
        if (!in_array($normalized, self::ISO_4217_WHITELIST, true)) {
            throw new ValidationException("Unsupported or invalid currency code: {$code}", ['currency' => 'Invalid ISO 4217 code']);
        }
        return $normalized;
    }

    /**
     * Validates a date string against ISO 8601 (Y-m-d) format and business boundaries.
     *
     * @param bool $allowFuture If false, rejects dates past today (or today + $maxFutureDays)
     * @param int $maxPastYears Maximum years in past allowed
     * @param int $maxFutureDays Maximum days in future allowed (e.g. For scheduled drafts)
     */
    public static function validateDate(
        string $dateStr,
        string $fieldName = 'date',
        bool $allowFuture = false,
        int $maxPastYears = 5,
        int $maxFutureDays = 30
    ): string {
        $trimmed = trim($dateStr);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $trimmed)) {
            throw new ValidationException("{$fieldName} must follow ISO 8601 format (YYYY-MM-DD)", [$fieldName => 'Invalid date format']);
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $trimmed);
        if (!$date || $date->format('Y-m-d') !== $trimmed) {
            throw new ValidationException("{$fieldName} is not a logically valid calendar date", [$fieldName => 'Invalid calendar date']);
        }

        $today = new DateTimeImmutable('today');
        $minPastDate = $today->modify("-{$maxPastYears} years");

        if ($date < $minPastDate) {
            throw new ValidationException("{$fieldName} cannot be older than {$maxPastYears} years", [$fieldName => 'Date is too far in the past']);
        }

        if (!$allowFuture && $date > $today) {
            throw new ValidationException("{$fieldName} cannot be a future date for posted records", [$fieldName => 'Future dates disallowed for posted records']);
        }

        if ($allowFuture) {
            $maxFutureDate = $today->modify("+{$maxFutureDays} days");
            if ($date > $maxFutureDate) {
                throw new ValidationException("{$fieldName} exceeds maximum future window of {$maxFutureDays} days", [$fieldName => 'Date exceeds future window']);
            }
        }

        return $trimmed;
    }

    /**
     * Validates a monthly budget period format (YYYY-MM).
     */
    public static function validateMonthPeriod(string $monthStr): string
    {
        $trimmed = trim($monthStr);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $trimmed)) {
            throw new ValidationException("Budget month must follow YYYY-MM format", ['month' => 'Invalid month format']);
        }
        return $trimmed;
    }

    /**
     * Mandatory Ownership Check: Asserts account belongs to the authenticated user.
     * Prevents Insecure Direct Object References (IDOR).
     */
    public static function verifyAccountOwnership(PDO $db, int $accountId, int $userId): array
    {
        if ($accountId <= 0) {
            throw new ValidationException("A valid account ID is required", ['account_id' => 'Invalid account']);
        }

        $stmt = $db->prepare("
            SELECT a.*, c.code as currency_code, c.symbol as currency_symbol
            FROM accounts a
            JOIN currencies c ON a.currency_id = c.id
            WHERE a.id = ? AND a.user_id = ? AND a.deleted_at IS NULL
        ");
        $stmt->execute([$accountId, $userId]);
        $account = $stmt->fetch();

        if (!$account) {
            throw new AuthorizationException("Account ID {$accountId} does not exist or access is forbidden");
        }

        if (($account['status'] ?? 'active') !== 'active') {
            throw new ValidationException("Account {$account['name']} is archived or inactive", ['account_id' => 'Account is inactive']);
        }

        return $account;
    }

    /**
     * Mandatory Ownership Check: Asserts category belongs to user or is an active system category.
     */
    public static function verifyCategoryOwnership(PDO $db, int $categoryId, int $userId): array
    {
        if ($categoryId <= 0) {
            throw new ValidationException("A valid category ID is required", ['category_id' => 'Invalid category']);
        }

        $stmt = $db->prepare("
            SELECT * FROM categories 
            WHERE id = ? AND (user_id = ? OR user_id IS NULL) AND deleted_at IS NULL
        ");
        $stmt->execute([$categoryId, $userId]);
        $category = $stmt->fetch();

        if (!$category) {
            throw new AuthorizationException("Category ID {$categoryId} does not exist or access is forbidden");
        }

        return $category;
    }

    /**
     * Mandatory Ownership Check: Asserts budget belongs to the authenticated user.
     */
    public static function verifyBudgetOwnership(PDO $db, int $budgetId, int $userId): array
    {
        if ($budgetId <= 0) {
            throw new ValidationException("A valid budget ID is required", ['budget_id' => 'Invalid budget']);
        }

        $stmt = $db->prepare("SELECT * FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->execute([$budgetId, $userId]);
        $budget = $stmt->fetch();

        if (!$budget) {
            throw new AuthorizationException("Budget ID {$budgetId} does not exist or access is forbidden");
        }

        return $budget;
    }

    /**
     * Mandatory Ownership Check: Asserts savings vault belongs to the authenticated user.
     */
    public static function verifyVaultOwnership(PDO $db, int $vaultId, int $userId): array
    {
        if ($vaultId <= 0) {
            throw new ValidationException("A valid vault ID is required", ['vault_id' => 'Invalid vault']);
        }

        $stmt = $db->prepare("SELECT * FROM savings_vaults WHERE id = ? AND user_id = ?");
        $stmt->execute([$vaultId, $userId]);
        $vault = $stmt->fetch();

        if (!$vault) {
            throw new AuthorizationException("Savings Vault ID {$vaultId} does not exist or access is forbidden");
        }

        return $vault;
    }

    /**
     * Checks and locks an idempotency token to prevent double submissions.
     * If already completed, throws IdempotencyException with cached response.
     *
     * @throws IdempotencyException
     */
    public static function checkIdempotency(PDO $db, int $userId, ?string $clientMutationId, string $endpoint, array $payload): ?array
    {
        if (empty($clientMutationId)) {
            return null; // Idempotency key omitted, skip check
        }

        $keyHash = hash('sha256', $clientMutationId);
        $requestHash = hash('sha256', json_encode($payload));

        $stmt = $db->prepare("
            SELECT response_code, response_body, expires_at 
            FROM idempotency_keys 
            WHERE user_id = ? AND key_hash = ?
        ");
        $stmt->execute([$userId, $keyHash]);
        $record = $stmt->fetch();

        if ($record) {
            // Already processed
            $body = !empty($record['response_body']) ? json_decode($record['response_body'], true) : [];
            throw new IdempotencyException(
                "Duplicate submission blocked by idempotency engine",
                [
                    'code' => (int) $record['response_code'],
                    'body' => $body
                ]
            );
        }

        // Reserve token for 24 hours
        $expiresAt = (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s');
        $insert = $db->prepare("
            INSERT INTO idempotency_keys (user_id, key_hash, endpoint, request_hash, response_code, expires_at)
            VALUES (?, ?, ?, ?, 102, ?)
        ");
        $insert->execute([$userId, $keyHash, $endpoint, $requestHash, $expiresAt]);

        return null;
    }

    /**
     * Records the finalized response for an idempotency token.
     */
    public static function storeIdempotencyResponse(PDO $db, int $userId, ?string $clientMutationId, int $responseCode, array $responseBody): void
    {
        if (empty($clientMutationId)) {
            return;
        }

        $keyHash = hash('sha256', $clientMutationId);
        $stmt = $db->prepare("
            UPDATE idempotency_keys 
            SET response_code = ?, response_body = ? 
            WHERE user_id = ? AND key_hash = ?
        ");
        $stmt->execute([$responseCode, json_encode($responseBody), $userId, $keyHash]);
    }
}
