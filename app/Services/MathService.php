<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FinancialException;

/**
 * High-Precision Financial Math Engine using BCMath.
 * Guarantees zero IEEE 754 floating-point drift, enforces strict scale standards,
 * and implements GAAP-compliant banker's rounding (Round-Half-to-Even).
 */
class MathService
{
    public const SCALE_MONEY = 2;
    public const SCALE_INTERMEDIATE = 6;
    public const SCALE_RATE = 6;

    /**
     * Sanitizes and validates a numeric string for BCMath operations.
     * Prevents scientific notation (e.g. 1e6), non-numeric chars, and malformed decimals.
     */
    public static function parseDecimal(string|int|float $value): string
    {
        $str = is_float($value) ? sprintf('%.8f', $value) : trim((string) $value);

        if (preg_match('/[eE]/', $str)) {
            throw new FinancialException("Scientific notation is forbidden in financial calculations: {$str}");
        }

        if (!preg_match('/^-?\d+(\.\d+)?$/', $str)) {
            throw new FinancialException("Invalid numeric string representation: {$str}");
        }

        // Clean leading zeros (e.g., 0012.30 -> 12.30, -005.1 -> -5.1, 00.00 -> 0.00)
        $isNegative = str_starts_with($str, '-');
        if ($isNegative) {
            $str = substr($str, 1);
        }

        $parts = explode('.', $str, 2);
        $intPart = ltrim($parts[0], '0');
        if ($intPart === '') {
            $intPart = '0';
        }

        $result = isset($parts[1]) ? $intPart . '.' . $parts[1] : $intPart;
        if ($isNegative && $result !== '0' && !preg_match('/^0(\.0+)?$/', $result)) {
            $result = '-' . $result;
        }

        return $result;
    }

    /**
     * Arbitrary-precision addition.
     */
    public static function add(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): string
    {
        $parsedA = self::parseDecimal($a);
        $parsedB = self::parseDecimal($b);
        return bcadd($parsedA, $parsedB, $scale);
    }

    /**
     * Arbitrary-precision subtraction (a - b).
     */
    public static function sub(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): string
    {
        $parsedA = self::parseDecimal($a);
        $parsedB = self::parseDecimal($b);
        return bcsub($parsedA, $parsedB, $scale);
    }

    /**
     * Arbitrary-precision multiplication.
     */
    public static function mul(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): string
    {
        $parsedA = self::parseDecimal($a);
        $parsedB = self::parseDecimal($b);
        return bcmul($parsedA, $parsedB, $scale);
    }

    /**
     * Arbitrary-precision division (a / b).
     * Automatically guards against zero divisor.
     */
    public static function div(string|int|float $a, string|int|float $b, int $scale = self::SCALE_INTERMEDIATE): string
    {
        $parsedA = self::parseDecimal($a);
        $parsedB = self::parseDecimal($b);

        if (bccomp($parsedB, '0', self::SCALE_INTERMEDIATE) === 0) {
            throw new FinancialException("Division by zero in financial computation: {$parsedA} / {$parsedB}");
        }

        return bcdiv($parsedA, $parsedB, $scale);
    }

    /**
     * Arbitrary-precision comparison.
     * Returns:
     *  0 if $a == $b
     *  1 if $a > $b
     * -1 if $a < $b
     */
    public static function comp(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): int
    {
        $parsedA = self::parseDecimal($a);
        $parsedB = self::parseDecimal($b);
        return bccomp($parsedA, $parsedB, $scale);
    }

    public static function eq(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): bool
    {
        return self::comp($a, $b, $scale) === 0;
    }

    public static function gt(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): bool
    {
        return self::comp($a, $b, $scale) === 1;
    }

    public static function gte(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): bool
    {
        return self::comp($a, $b, $scale) >= 0;
    }

    public static function lt(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): bool
    {
        return self::comp($a, $b, $scale) === -1;
    }

    public static function lte(string|int|float $a, string|int|float $b, int $scale = self::SCALE_MONEY): bool
    {
        return self::comp($a, $b, $scale) <= 0;
    }

    public static function isPositive(string|int|float $a, int $scale = self::SCALE_MONEY): bool
    {
        return self::gt($a, '0', $scale);
    }

    public static function isZero(string|int|float $a, int $scale = self::SCALE_MONEY): bool
    {
        return self::eq($a, '0', $scale);
    }

    /**
     * Returns the absolute value of a decimal string.
     */
    public static function abs(string|int|float $a): string
    {
        $parsed = self::parseDecimal($a);
        return str_starts_with($parsed, '-') ? substr($parsed, 1) : $parsed;
    }

    /**
     * Sum an array of monetary values with arbitrary precision.
     *
     * @param array<string|int|float> $values
     */
    public static function sum(array $values, int $scale = self::SCALE_MONEY): string
    {
        $total = '0.' . str_repeat('0', $scale);
        foreach ($values as $value) {
            $parsed = self::parseDecimal($value);
            $total = bcadd($total, $parsed, $scale);
        }
        return $total;
    }

    /**
     * GAAP-compliant Banker's Rounding (Round-Half-to-Even) implemented directly on decimal strings.
     * Completely eliminates standard round() floating-point representation bugs.
     */
    public static function round(string|int|float $value, int $scale = self::SCALE_MONEY, int $mode = PHP_ROUND_HALF_EVEN): string
    {
        $number = self::parseDecimal($value);

        if ($mode !== PHP_ROUND_HALF_EVEN && $mode !== PHP_ROUND_HALF_UP) {
            throw new FinancialException("Unsupported rounding mode: {$mode}");
        }

        $neg = false;
        if (str_starts_with($number, '-')) {
            $neg = true;
            $number = substr($number, 1);
        }

        $parts = explode('.', $number);
        $intPart = $parts[0] === '' ? '0' : $parts[0];
        $decPart = $parts[1] ?? '';

        if (strlen($decPart) <= $scale) {
            $decPart = str_pad($decPart, $scale, '0');
            $res = $scale > 0 ? "{$intPart}.{$decPart}" : $intPart;
            return $neg && bccomp($res, '0', $scale) !== 0 ? "-{$res}" : $res;
        }

        $keptDec = substr($decPart, 0, $scale);
        $nextDigit = (int) $decPart[$scale];
        $remainder = substr($decPart, $scale + 1);
        $hasNonZeroRemainder = strlen(rtrim($remainder, '0')) > 0;

        $roundUp = false;
        if ($nextDigit > 5) {
            $roundUp = true;
        } elseif ($nextDigit < 5) {
            $roundUp = false;
        } else {
            // Next digit is exactly 5
            if ($mode === PHP_ROUND_HALF_UP || $hasNonZeroRemainder) {
                $roundUp = true;
            } else {
                // Exact half in Banker's Rounding: round to nearest even digit
                $lastKeptDigit = $scale > 0 ? (int) ($keptDec[strlen($keptDec) - 1] ?? 0) : (int) ($intPart[strlen($intPart) - 1] ?? 0);
                $roundUp = ($lastKeptDigit % 2 !== 0);
            }
        }

        $base = $scale > 0 ? "{$intPart}.{$keptDec}" : $intPart;
        if ($roundUp) {
            $add = $scale > 0 ? '0.' . str_repeat('0', $scale - 1) . '1' : '1';
            $base = bcadd($base, $add, $scale);
        }

        return $neg && bccomp($base, '0', $scale) !== 0 ? "-{$base}" : $base;
    }

    /**
     * Converts a monetary value using an exchange rate.
     * Performs intermediate calculation at high scale (6), then applies banker's rounding.
     */
    public static function convert(string|int|float $amount, string|int|float $exchangeRate, int $targetScale = self::SCALE_MONEY): string
    {
        $parsedAmount = self::parseDecimal($amount);
        $parsedRate = self::parseDecimal($exchangeRate);

        if (self::isZero($parsedRate, self::SCALE_RATE)) {
            throw new FinancialException("Exchange rate cannot be zero");
        }

        // Intermediate calculation at Scale 6
        $rawConverted = bcmul($parsedAmount, $parsedRate, self::SCALE_INTERMEDIATE);
        return self::round($rawConverted, $targetScale, PHP_ROUND_HALF_EVEN);
    }

    /**
     * Cross-currency rate conversion: amount * (toRate / fromRate)
     * All intermediate calculations held at SCALE_INTERMEDIATE before banker's rounding.
     */
    public static function convertCross(
        string|int|float $amount,
        string|int|float $fromRate,
        string|int|float $toRate,
        int $targetScale = self::SCALE_MONEY
    ): string {
        $parsedAmount = self::parseDecimal($amount);
        $parsedFromRate = self::parseDecimal($fromRate);
        $parsedToRate = self::parseDecimal($toRate);

        if (self::isZero($parsedFromRate, self::SCALE_RATE)) {
            throw new FinancialException("Source currency exchange rate cannot be zero");
        }

        // amountInBase = amount / fromRate (Scale 6)
        $inBase = bcdiv($parsedAmount, $parsedFromRate, self::SCALE_INTERMEDIATE);
        // targetAmount = inBase * toRate (Scale 6)
        $targetAmount = bcmul($inBase, $parsedToRate, self::SCALE_INTERMEDIATE);

        return self::round($targetAmount, $targetScale, PHP_ROUND_HALF_EVEN);
    }

    /**
     * Invariant Verification: Verifies that sum(debits) = sum(credits) + fees down to the last cent.
     *
     * @param array<string|int|float> $debits
     * @param array<string|int|float> $credits
     */
    public static function verifyInvariant(array $debits, array $credits, string|int|float $fees = '0.00'): bool
    {
        $sumDebits = self::sum($debits, self::SCALE_MONEY);
        $sumCredits = self::sum($credits, self::SCALE_MONEY);
        $parsedFees = self::parseDecimal($fees);

        $totalCreditSide = bcadd($sumCredits, $parsedFees, self::SCALE_MONEY);

        return bccomp($sumDebits, $totalCreditSide, self::SCALE_MONEY) === 0;
    }

    /**
     * Formats a monetary decimal string for human display.
     */
    public static function format(string|int|float $value, int $scale = self::SCALE_MONEY, string $decPoint = '.', string $thousandsSep = ','): string
    {
        $rounded = self::round($value, $scale);
        $neg = str_starts_with($rounded, '-');
        if ($neg) {
            $rounded = substr($rounded, 1);
        }

        $parts = explode('.', $rounded);
        $intPart = number_format((float) $parts[0], 0, '', $thousandsSep);
        $decPart = $parts[1] ?? str_repeat('0', $scale);

        $result = $scale > 0 ? "{$intPart}{$decPoint}{$decPart}" : $intPart;
        return $neg ? "-{$result}" : $result;
    }
}
