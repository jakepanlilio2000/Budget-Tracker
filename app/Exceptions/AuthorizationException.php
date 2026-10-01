<?php
declare(strict_types=1);

namespace App\Exceptions;

class AuthorizationException extends FinancialException
{
    public function __construct(string $message = 'Unauthorized access to resource', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
