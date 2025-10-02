<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto;

use AlchemicStudio\Ponto\Auth\AuthProvider;
use AlchemicStudio\Ponto\Auth\FileTokenStorage;
use AlchemicStudio\Ponto\Auth\TokenStorage;
use AlchemicStudio\Ponto\Exceptions\AuthenticationException;
use AlchemicStudio\Ponto\Http\HttpClient;
use AlchemicStudio\Ponto\Services\AccountService;
use AlchemicStudio\Ponto\Services\PaymentService;
use AlchemicStudio\Ponto\Services\SynchronizationService;
use AlchemicStudio\Ponto\Services\TransactionService;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    private AuthProvider $authProvider;
    private HttpClient $httpClient;
    private ?AccountService $accountService = null;
    private ?TransactionService $transactionService = null;
    private ?PaymentService $paymentService = null;
    private ?SynchronizationService $synchronizationService = null;

    /**
     * @param array<string, mixed> $httpOptions
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $baseUrl = 'https://api.myponto.com',
        ?TokenStorage $tokenStorage = null,
        array $httpOptions = []
    ) {
        // Initialize token storage
        $tokenStorage = $tokenStorage ?? new FileTokenStorage();

        // Initialize Guzzle client for token requests (without auth)
        $tokenGuzzle = new GuzzleClient(array_merge([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'http_errors' => true,
        ], $httpOptions));

        // Initialize auth provider
        $this->authProvider = new AuthProvider(
            clientId: $clientId,
            clientSecret: $clientSecret,
            tokenUrl: $baseUrl . '/oauth2/token',
            tokenStorage: $tokenStorage,
            httpClient: $tokenGuzzle
        );

        // Initialize Guzzle client for API requests
        $apiGuzzle = new GuzzleClient(array_merge([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'http_errors' => false, // Let HttpClient handle errors
        ], $httpOptions));

        // Initialize HTTP client with auth provider
        $maxRetries = isset($httpOptions['max_retries']) && is_int($httpOptions['max_retries'])
            ? $httpOptions['max_retries']
            : 3;
        $retryDelayMs = isset($httpOptions['retry_delay_ms']) && is_int($httpOptions['retry_delay_ms'])
            ? $httpOptions['retry_delay_ms']
            : 1000;

        $this->httpClient = new HttpClient(
            guzzle: $apiGuzzle,
            authProvider: $this->authProvider,
            maxRetries: $maxRetries,
            retryDelayMs: $retryDelayMs
        );
    }

    /**
     * Get account service for account operations
     */
    public function accounts(): AccountService
    {
        if ($this->accountService === null) {
            $this->accountService = new AccountService($this->httpClient);
        }

        return $this->accountService;
    }

    /**
     * Get transaction service for transaction operations
     */
    public function transactions(): TransactionService
    {
        if ($this->transactionService === null) {
            $this->transactionService = new TransactionService($this->httpClient);
        }

        return $this->transactionService;
    }

    /**
     * Get payment service for payment operations
     *
     * @throws AuthenticationException if 'pi' scope not granted
     */
    public function payments(): PaymentService
    {
        if (! $this->hasPaymentScope()) {
            throw new AuthenticationException('Payment initiation scope (pi) not granted');
        }

        if ($this->paymentService === null) {
            $this->paymentService = new PaymentService($this->httpClient);
        }

        return $this->paymentService;
    }

    /**
     * Get synchronization service
     */
    public function synchronizations(): SynchronizationService
    {
        if ($this->synchronizationService === null) {
            $this->synchronizationService = new SynchronizationService($this->httpClient);
        }

        return $this->synchronizationService;
    }

    /**
     * Force token refresh
     *
     * @throws AuthenticationException on auth failure
     */
    public function refreshToken(): void
    {
        $this->authProvider->refreshToken();
    }

    /**
     * Check if payment initiation is enabled
     */
    public function hasPaymentScope(): bool
    {
        return $this->authProvider->hasScope('pi');
    }

    /**
     * Get auth provider (for advanced usage)
     */
    public function getAuthProvider(): AuthProvider
    {
        return $this->authProvider;
    }

    /**
     * Get HTTP client (for advanced usage)
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }
}
