<?php
declare(strict_types=1);

namespace App\Exceptions;

class IdempotencyException extends FinancialException
{
    private ?array $cachedResponse;

    public function __construct(string $message = 'Duplicate mutation detected', ?array $cachedResponse = null, int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->cachedResponse = $cachedResponse;
    }

    public function getCachedResponse(): ?array
    {
        return $this->cachedResponse;
    }
}
