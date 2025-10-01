<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Exceptions;

use Throwable;

class ApiException extends PontoException
{
    private ?string $errorCode;

    public function __construct(
        string $message,
        int $code,
        ?string $errorCode = null,
        ?array $errorDetails = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $errorDetails, $previous);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
