<?php
declare(strict_types=1);

namespace App\Exceptions;

class InsufficientFundsException extends FinancialException
{
    public function __construct(string $message = 'Insufficient funds for transaction', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
