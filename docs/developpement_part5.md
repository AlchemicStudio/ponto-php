## Configuration and Environment Variables

### Environment Variables

Create a `.env` file in your project root:

```env
# Ponto API Credentials
PONTO_CLIENT_ID=8f0e9068-48f5-4b45-84ee-1e8adb386c0d
PONTO_CLIENT_SECRET=a9e726f9-8078-4f70-8570-e3c8600d7816

# Environment (production or sandbox)
PONTO_BASE_URL=https://api.myponto.com

# Optional: Token storage
PONTO_TOKEN_STORAGE_PATH=/tmp/ponto_tokens

# Optional: HTTP Client Configuration
PONTO_HTTP_TIMEOUT=30
PONTO_HTTP_CONNECT_TIMEOUT=10
PONTO_MAX_RETRIES=3
PONTO_RETRY_DELAY_MS=1000
```

### Configuration Class

**`src/Config.php`:**

```php
<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto;

final readonly class Config
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $baseUrl = 'https://api.myponto.com',
        public int $httpTimeout = 30,
        public int $connectTimeout = 10,
        public int $maxRetries = 3,
        public int $retryDelayMs = 1000,
        public ?string $tokenStoragePath = null,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            clientId: self::getEnv('PONTO_CLIENT_ID'),
            clientSecret: self::getEnv('PONTO_CLIENT_SECRET'),
            baseUrl: self::getEnv('PONTO_BASE_URL', 'https://api.myponto.com'),
            httpTimeout: (int) self::getEnv('PONTO_HTTP_TIMEOUT', '30'),
            connectTimeout: (int) self::getEnv('PONTO_HTTP_CONNECT_TIMEOUT', '10'),
            maxRetries: (int) self::getEnv('PONTO_MAX_RETRIES', '3'),
            retryDelayMs: (int) self::getEnv('PONTO_RETRY_DELAY_MS', '1000'),
            tokenStoragePath: self::getEnv('PONTO_TOKEN_STORAGE_PATH'),
        );
    }

    private static function getEnv(string $key, ?string $default = null): string
    {
        $value = getenv($key) ?: ($_ENV[$key] ?? $default);
        
        if ($value === null) {
            throw new \RuntimeException("Environment variable {$key} is required");
        }
        
        return $value;
    }

    public function isSandbox(): bool
    {
        return str_contains($this->clientId, 'sandbox_');
    }

    public function getTokenUrl(): string
    {
        return $this->baseUrl . '/oauth2/token';
    }
}
```

**Usage:**

```php
<?php

use AlchemicStudio\Ponto\Client;
use AlchemicStudio\Ponto\Config;

// Load from environment variables
$config = Config::fromEnv();
$client = new Client(
    clientId: $config->clientId,
    clientSecret: $config->clientSecret,
    baseUrl: $config->baseUrl,
    httpOptions: [
        'timeout' => $config->httpTimeout,
        'connect_timeout' => $config->connectTimeout,
    ]
);

// Or use Config directly in Client (alternative design)
// $client = Client::fromConfig($config);
```

---

## Authentication Strategy and Token Refresh

### OAuth2 Flow

Ponto uses the **Client Credentials Grant** (OAuth2):

1. **Initial Authentication:**
   ```
   POST /oauth2/token
   Authorization: Basic base64(client_id:client_secret)
   Content-Type: application/x-www-form-urlencoded
   
   grant_type=client_credentials
   ```

2. **Response:**
   ```json
   {
       "access_token": "sandbox_CSmOh54ps-...",
       "expires_in": 1799,
       "scope": "ai pi",
       "token_type": "bearer"
   }
   ```

3. **Token Usage:**
   ```
   GET /accounts
   Authorization: Bearer sandbox_CSmOh54ps-...
   ```

### Token Lifecycle

- **Expiration:** Tokens expire after ~30 minutes (1799 seconds)
- **Caching:** Store tokens to avoid unnecessary OAuth requests
- **Refresh Strategy:** Refresh 5 minutes before expiration
- **Automatic Refresh:** On 401 responses, attempt token refresh once

**Implementation in AuthProvider:**

```php
<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Auth;

use AlchemicStudio\Ponto\Exceptions\AuthenticationException;
use AlchemicStudio\Ponto\Http\HttpClient;

final class AuthProvider
{
    private ?string $cachedToken = null;
    private ?\DateTimeImmutable $expiresAt = null;
    private array $scopes = [];

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $tokenUrl,
        private readonly TokenStorage $tokenStorage,
        private readonly HttpClient $httpClient,
    ) {}

    public function getAccessToken(): string
    {
        // Try cached token first
        if ($this->cachedToken && $this->isTokenValid()) {
            return $this->cachedToken;
        }

        // Try token from storage
        $storedToken = $this->tokenStorage->get($this->getStorageKey());
        if ($storedToken) {
            $tokenData = json_decode($storedToken, true);
            $expiresAt = new \DateTimeImmutable($tokenData['expires_at']);
            
            if ($expiresAt > new \DateTimeImmutable('+5 minutes')) {
                $this->cachedToken = $tokenData['access_token'];
                $this->expiresAt = $expiresAt;
                $this->scopes = $tokenData['scopes'] ?? [];
                return $this->cachedToken;
            }
        }

        // Fetch new token
        return $this->refreshToken();
    }

    public function refreshToken(): string
    {
        $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");

        try {
            $response = $this->httpClient->postRaw(
                url: $this->tokenUrl,
                body: ['grant_type' => 'client_credentials'],
                headers: [
                    'Authorization' => "Basic {$credentials}",
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            );

            $this->cachedToken = $response['access_token'];
            $this->expiresAt = (new \DateTimeImmutable())->modify("+{$response['expires_in']} seconds");
            $this->scopes = explode(' ', $response['scope'] ?? '');

            // Store token
            $this->tokenStorage->set(
                key: $this->getStorageKey(),
                value: json_encode([
                    'access_token' => $this->cachedToken,
                    'expires_at' => $this->expiresAt->format(\DateTimeInterface::ATOM),
                    'scopes' => $this->scopes,
                ]),
                ttl: $response['expires_in']
            );

            return $this->cachedToken;

        } catch (\Exception $e) {
            throw new AuthenticationException(
                "Failed to obtain access token: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    private function isTokenValid(): bool
    {
        if (/home/sebastien/Codes/ponto-phpthis->expiresAt) {
            return false;
        }

        // Consider token invalid 5 minutes before expiration
        return $this->expiresAt > new \DateTimeImmutable('+5 minutes');
    }

    private function getStorageKey(): string
    {
        return 'access_token_' . substr(hash('sha256', $this->clientId), 0, 16);
    }
}
```

---

## Security Best Practices

### 1. Credential Management

**❌ Don't:**
```php
// Hardcoded credentials in source code
$client = new Client('my-client-id', 'my-secret');
```

**✅ Do:**
```php
// Use environment variables
$client = new Client(
    clientId: $_ENV['PONTO_CLIENT_ID'],
    clientSecret: $_ENV['PONTO_CLIENT_SECRET']
);
```

### 2. Secure Token Storage

**File Storage Permissions:**
```php
<?php

namespace AlchemicStudio\Ponto\Auth;

final class FileTokenStorage implements TokenStorage
{
    public function __construct(
        private readonly string $storagePath = '/tmp/ponto_tokens'
    ) {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0700, true); // Owner-only permissions
        }
    }

    public function set(string $key, string $value, int $ttl): void
    {
        $filePath = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];
        
        file_put_contents($filePath, json_encode($data), LOCK_EX);
        chmod($filePath, 0600); // Owner read/write only
    }

    public function get(string $key): ?string
    {
        $filePath = $this->getFilePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }

        $data = json_decode(file_get_contents($filePath), true);
        
        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    public function delete(string $key): void
    {
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function getFilePath(string $key): string
    {
        return $this->storagePath . '/' . hash('sha256', $key) . '.json';
    }
}
```

### 3. No Credential Logging

**❌ Don't:**
```php
// Never log credentials or tokens
error_log("Token: " . $accessToken);
$logger->info("Secret: {$clientSecret}");
```

**✅ Do:**
```php
// Log operations without sensitive data
$logger->info('Authentication successful', [
    'client_id' => substr($clientId, 0, 8) . '...',
    'scopes' => $scopes,
]);
```

### 4. TLS/HTTPS Only

```php
// Enforce HTTPS
if (parse_url($baseUrl, PHP_URL_SCHEME) !== 'https') {
    throw new \InvalidArgumentException('Ponto API requires HTTPS');
}

// Guzzle HTTP client with TLS verification
$guzzle = new \GuzzleHttp\Client([
    'base_uri' => $baseUrl,
    'verify' => true, // Verify SSL certificates
    'timeout' => 30,
]);
```

### 5. Idempotency Keys

Always use `endToEndId` for payments to prevent duplicate transactions:

```php
// Generate unique but deterministic idempotency key
$idempotencyKey = hash('sha256', implode('|', [
    $invoiceId,
    $amount,
    $currency,
    $creditorIban,
    date('Y-m-d'),
]));

$payment = $client->payments()->create($accountId, [
    'endToEndId' => $idempotencyKey,
    // ... other fields
]);
```

### 6. Input Validation

**Validation Helper:**

```php
<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Utils;

use AlchemicStudio\Ponto\Exceptions\ValidationException;

final class Validator
{
    public static function validateIban(string $iban): string
    {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban));
        
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            throw new ValidationException("Invalid IBAN format: {$iban}");
        }
        
        return $iban;
    }

    public static function validateBic(string $bic): string
    {
        $bic = strtoupper(preg_replace('/\s+/', '', $bic));
        
        if (!preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $bic)) {
            throw new ValidationException("Invalid BIC format: {$bic}");
        }
        
        return $bic;
    }

    public static function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new ValidationException("Amount must be positive: {$amount}");
        }
        
        if ($amount > 999999999.99) {
            throw new ValidationException("Amount too large: {$amount}");
        }
    }

    public static function validateCurrency(string $currency): string
    {
        $currency = strtoupper($currency);
        
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new ValidationException("Invalid currency code: {$currency}");
        }
        
        return $currency;
    }

    public static function validateRemittanceInfo(string $info): string
    {
        $maxLength = 140;
        $allowedChars = '/^[a-zA-Z0-9\\/\\-\\?:\\(\\)\\.\\,\'\\+ ]+$/';
        
        if (strlen($info) > $maxLength) {
            throw new ValidationException("Remittance information too long (max {$maxLength} chars)");
        }
        
        if (!preg_match($allowedChars, $info)) {
            throw new ValidationException("Remittance information contains invalid characters");
        }
        
        return $info;
    }
}
```

---
