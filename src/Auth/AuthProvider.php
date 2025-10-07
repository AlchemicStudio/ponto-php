<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Auth;

use AlchemicStudio\Ponto\Exceptions\AuthenticationException;
use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

class AuthProvider
{
    private ?string $accessToken = null;
    private ?DateTimeImmutable $expiresAt = null;

    /** @var array<string> */
    private array $scopes = [];
    private string $storageKey;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $tokenUrl,
        private TokenStorage $tokenStorage,
        private GuzzleClient $httpClient,
    ) {
        $this->storageKey = 'ponto_token_' . md5($clientId);
        $this->loadFromStorage();
    }

    /**
     * Get valid access token (from cache or new)
     *
     * @throws AuthenticationException
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken !== null && $this->isTokenValid()) {
            return $this->accessToken;
        }

        return $this->refreshToken();
    }

    /**
     * Force token refresh
     *
     * @throws AuthenticationException
     */
    public function refreshToken(): string
    {
        try {
            $Authorization = 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret);
            $response = $this->httpClient->post($this->tokenUrl, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
                'headers' => [
                    'Authorization' => $Authorization,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
            ]);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (! is_array($data) || ! isset($data['access_token']) || ! is_string($data['access_token'])) {
                throw new AuthenticationException('Invalid token response from server');
            }

            $this->accessToken = $data['access_token'];

            if (isset($data['scope']) && is_string($data['scope'])) {
                $this->scopes = explode(' ', $data['scope']);
            } else {
                $this->scopes = [];
            }

            // Calculate expiration time (default to 3600 seconds if not provided)
            $expiresIn = is_int($data['expires_in'] ?? null) ? $data['expires_in'] : 3600;
            $this->expiresAt = (new DateTimeImmutable())->modify('+' . (string) $expiresIn . ' seconds');

            $this->saveToStorage();

            return $this->accessToken;
        } catch (GuzzleException $e) {
            throw new AuthenticationException('Failed to obtain access token: ' . $e->getMessage(), 0, null, $e);
        }
    }

    /**
     * Get token scopes
     *
     * @return array<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Check if scope is granted
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    /**
     * Get token expiration time
     */
    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Check if current token is valid
     */
    private function isTokenValid(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $now = new DateTimeImmutable();
        // Add 60 second buffer to refresh before actual expiration
        $expirationWithBuffer = $this->expiresAt->modify('-60 seconds');

        return $now < $expirationWithBuffer;
    }

    /**
     * Load token from storage
     */
    private function loadFromStorage(): void
    {
        $data = $this->tokenStorage->get($this->storageKey);

        if ($data === null) {
            return;
        }

        $decoded = json_decode($data, true);

        if (! is_array($decoded)) {
            return;
        }

        if (isset($decoded['access_token']) && is_string($decoded['access_token'])) {
            $this->accessToken = $decoded['access_token'];
        }

        if (isset($decoded['scopes']) && is_array($decoded['scopes'])) {
            $this->scopes = $decoded['scopes'];
        }

        if (isset($decoded['expires_at']) && is_string($decoded['expires_at'])) {
            try {
                $this->expiresAt = new DateTimeImmutable($decoded['expires_at']);
            } catch (\Exception $e) {
                $this->expiresAt = null;
            }
        }
    }

    /**
     * Save token to storage
     */
    private function saveToStorage(): void
    {
        $data = [
            'access_token' => $this->accessToken,
            'scopes' => $this->scopes,
            'expires_at' => $this->expiresAt?->format('Y-m-d\TH:i:s\Z'),
        ];

        $encoded = json_encode($data);

        if ($encoded === false) {
            throw new AuthenticationException('Failed to encode token data');
        }

        $this->tokenStorage->set($this->storageKey, $encoded);
    }
}
