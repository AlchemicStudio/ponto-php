<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Exceptions;

use Throwable;

class RateLimitException extends PontoException
{
    private ?int $retryAfterSeconds;

    public function __construct(
        string $message,
        ?int $retryAfterSeconds = null,
        ?array $errorDetails = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 429, $errorDetails, $previous);
        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
