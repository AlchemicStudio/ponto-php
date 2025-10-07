<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Http;

use AlchemicStudio\Ponto\Exceptions\ApiException;
use AlchemicStudio\Ponto\Exceptions\RateLimitException;

interface HttpClientInterface
{
    /**
     * Execute GET request
     *
     * @param string $path API path (e.g., '/accounts')
     * @param array<string, mixed> $query Query parameters
     * @return array<string, mixed> JSON-decoded response
     * @throws ApiException on API errors
     * @throws RateLimitException on 429
     */
    public function get(string $path, array $query = []): array;

    /**
     * Execute POST request
     *
     * @param string $path API path
     * @param array<string, mixed> $body Request body
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> JSON-decoded response
     * @throws ApiException on API errors
     */
    public function post(string $path, array $body = [], array $headers = []): array;

    /**
     * Execute DELETE request
     *
     * @param string $path API path
     * @return void
     * @throws ApiException on API errors
     */
    public function delete(string $path): void;

    /**
     * Get last response metadata
     *
     * @return array{statusCode: int, headers: array<string, mixed>}
     */
    public function getLastResponseMeta(): array;
}
