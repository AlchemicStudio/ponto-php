<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Http;

use AlchemicStudio\Ponto\Auth\AuthProvider;
use AlchemicStudio\Ponto\Exceptions\ApiException;
use AlchemicStudio\Ponto\Exceptions\AuthenticationException;
use AlchemicStudio\Ponto\Exceptions\NotFoundException;
use AlchemicStudio\Ponto\Exceptions\RateLimitException;
use AlchemicStudio\Ponto\Exceptions\ValidationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class HttpClient implements HttpClientInterface
{
    private array $lastResponseMeta = ['statusCode' => 0, 'headers' => []];

    public function __construct(
        private GuzzleClient $guzzle,
        private ?AuthProvider $authProvider = null,
        private int $maxRetries = 3,
        private int $retryDelayMs = 1000,
    ) {
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $body = [], array $headers = []): array
    {
        $options = [
            'json' => $body,
            'headers' => $headers,
        ];

        return $this->request('POST', $path, $options);
    }

    public function delete(string $path): void
    {
        $this->request('DELETE', $path);
    }

    public function getLastResponseMeta(): array
    {
        return $this->lastResponseMeta;
    }

    /**
     * Execute HTTP request with retry logic
     *
     * @param string $method HTTP method
     * @param string $path API path
     * @param array<string, mixed> $options Guzzle options
     * @return array<string, mixed> JSON-decoded response
     * @throws ApiException
     * @throws AuthenticationException
     * @throws NotFoundException
     * @throws RateLimitException
     * @throws ValidationException
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $attempt = 0;

        while ($attempt <= $this->maxRetries) {
            try {
                // Add authentication header if auth provider is available
                if ($this->authProvider !== null) {
                    $options['headers']['Authorization'] = 'Bearer ' . $this->authProvider->getAccessToken();
                }

                // Set default headers
                $options['headers']['Accept'] = 'application/json';
                $options['headers']['Content-Type'] = 'application/json';

                $response = $this->guzzle->request($method, $path, $options);

                // Store response metadata
                $this->lastResponseMeta = [
                    'statusCode' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                ];

                $body = (string) $response->getBody();

                if ($body === '') {
                    return [];
                }

                $decoded = json_decode($body, true);

                if (! is_array($decoded)) {
                    throw new ApiException('Invalid JSON response', $response->getStatusCode());
                }

                return $decoded;
            } catch (RequestException $e) {
                $response = $e->getResponse();

                if ($response === null) {
                    // Network error - retry
                    if ($attempt < $this->maxRetries) {
                        $attempt++;
                        $this->sleep($attempt);

                        continue;
                    }

                    throw new ApiException('Network error: ' . $e->getMessage(), 0, null, null);
                }

                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();
                $errorData = json_decode($body, true);

                $errorMessage = $this->extractErrorMessage($errorData) ?? $e->getMessage();
                $errorDetails = is_array($errorData) ? $errorData : null;

                // Store response metadata
                $this->lastResponseMeta = [
                    'statusCode' => $statusCode,
                    'headers' => $response->getHeaders(),
                ];

                // Handle specific error codes
                if ($statusCode === 401) {
                    throw new AuthenticationException($errorMessage, $statusCode, $errorDetails);
                }

                if ($statusCode === 404) {
                    throw new NotFoundException($errorMessage, $statusCode, $errorDetails);
                }

                if ($statusCode === 400) {
                    throw new ValidationException($errorMessage, $statusCode, $errorDetails);
                }

                if ($statusCode === 429) {
                    $this->handleRateLimit($response, $errorMessage, $errorDetails);
                }

                // Server errors (5xx) - retry
                if ($statusCode >= 500) {
                    if ($attempt < $this->maxRetries) {
                        $attempt++;
                        $this->sleep($attempt);

                        continue;
                    }

                    throw new ApiException($errorMessage, $statusCode, null, $errorDetails);
                }

                // Other client errors
                throw new ApiException(
                    $errorMessage,
                    $statusCode,
                    $this->extractErrorCode($errorData),
                    $errorDetails
                );
            } catch (GuzzleException $e) {
                // Other Guzzle exceptions
                if ($attempt < $this->maxRetries) {
                    $attempt++;
                    $this->sleep($attempt);

                    continue;
                }

                throw new ApiException('HTTP error: ' . $e->getMessage(), 0, null, null);
            }
        }

        throw new ApiException('Max retries exceeded', 0);
    }

    /**
     * Handle rate limit (429) responses
     */
    private function handleRateLimit(
        $response,
        string $errorMessage,
        ?array $errorDetails
    ): never {
        $retryAfter = null;

        if ($response->hasHeader('Retry-After')) {
            $retryAfterHeader = $response->getHeader('Retry-After')[0];
            $retryAfter = is_numeric($retryAfterHeader) ? (int) $retryAfterHeader : null;
        }

        throw new RateLimitException($errorMessage, $retryAfter, $errorDetails);
    }

    /**
     * Extract error message from API response
     */
    private function extractErrorMessage(?array $errorData): ?string
    {
        if ($errorData === null) {
            return null;
        }

        // Check for JSON:API error format
        if (isset($errorData['errors'][0]['detail'])) {
            return $errorData['errors'][0]['detail'];
        }

        if (isset($errorData['errors'][0]['title'])) {
            return $errorData['errors'][0]['title'];
        }

        if (isset($errorData['message'])) {
            return $errorData['message'];
        }

        if (isset($errorData['error'])) {
            return $errorData['error'];
        }

        return null;
    }

    /**
     * Extract error code from API response
     */
    private function extractErrorCode(?array $errorData): ?string
    {
        if ($errorData === null) {
            return null;
        }

        if (isset($errorData['errors'][0]['code'])) {
            return $errorData['errors'][0]['code'];
        }

        if (isset($errorData['code'])) {
            return $errorData['code'];
        }

        return null;
    }

    /**
     * Sleep with exponential backoff
     */
    private function sleep(int $attempt): void
    {
        $delay = $this->retryDelayMs * (2 ** ($attempt - 1));
        usleep($delay * 1000);
    }
}
