<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Exceptions;

use Exception;
use Throwable;

class PontoException extends Exception
{
    /** @var array<string, mixed>|null */
    private ?array $errorDetails;

    /**
     * @param array<string, mixed>|null $errorDetails
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?array $errorDetails = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorDetails = $errorDetails;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }
}
