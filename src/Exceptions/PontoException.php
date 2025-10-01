<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Exceptions;

use Exception;
use Throwable;

class PontoException extends Exception
{
    private ?array $errorDetails;

    public function __construct(
        string $message,
        int $code = 0,
        ?array $errorDetails = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorDetails = $errorDetails;
    }

    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }
}
