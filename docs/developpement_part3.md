### 6. Model Classes

#### Account Model

**Namespace:** `AlchemicStudio\Ponto\Models`

**Properties:**
```php
public readonly string $id;
public readonly string $type; // 'account'
public readonly string $reference; // IBAN
public readonly string $referenceType; // 'IBAN'
public readonly string $currency; // ISO 4217
public readonly string $subtype; // 'checking', 'savings', etc.
public readonly float $availableBalance;
public readonly float $currentBalance;
public readonly string $holderName;
public readonly string $product;
public readonly ?string $description;
public readonly bool $deprecated;
public readonly \DateTimeImmutable $availableBalanceChangedAt;
public readonly \DateTimeImmutable $currentBalanceChangedAt;
public readonly \DateTimeImmutable $authorizedAt;
public readonly ?\DateTimeImmutable $authorizationExpirationExpectedAt;
public readonly string $internalReference;
public readonly array $meta; // Sync metadata
public readonly array $relationships; // Related resources
```

**Methods:**
```php
public static function fromArray(array $data): self
public function isDeprecated(): bool
public function needsReauthorization(): bool
public function toArray(): array
```

---

#### Transaction Model

**Namespace:** `AlchemicStudio\Ponto\Models`

**Properties:**
```php
public readonly string $id;
public readonly string $type; // 'transaction'
public readonly string $accountId; // Parent account
public readonly float $amount;
public readonly string $currency;
public readonly string $description;
public readonly string $digest;
public readonly ?\DateTimeImmutable $executionDate;
public readonly ?\DateTimeImmutable $valueDate;
public readonly \DateTimeImmutable $createdAt;
public readonly \DateTimeImmutable $updatedAt;
public readonly ?string $counterpartName;
public readonly ?string $counterpartReference;
public readonly ?string $remittanceInformation;
public readonly ?string $remittanceInformationType;
public readonly ?string $bankTransactionCode;
public readonly ?string $endToEndId;
public readonly ?string $mandateId;
public readonly ?string $creditorId;
public readonly ?string $purposeCode;
public readonly ?float $fee;
public readonly ?string $additionalInformation;
public readonly string $internalReference;
```

**Methods:**
```php
public static function fromArray(array $data): self
public function isCredit(): bool // amount > 0
public function isDebit(): bool // amount < 0
public function getAbsoluteAmount(): float
public function toArray(): array
```

---

#### Payment Model

**Namespace:** `AlchemicStudio\Ponto\Models`

**Properties:**
```php
public readonly string $id;
public readonly string $type; // 'payment'
public readonly string $status; // 'unsigned', 'authorized', 'executed', 'cancelled', 'rejected'
public readonly float $amount;
public readonly string $currency;
public readonly string $creditorName;
public readonly string $creditorAccountReference;
public readonly string $creditorAccountReferenceType;
public readonly string $creditorAgent;
public readonly string $creditorAgentType;
public readonly string $remittanceInformation;
public readonly string $remittanceInformationType;
public readonly ?string $endToEndId;
public readonly ?\DateTimeImmutable $requestedExecutionDate;
public readonly ?string $redirectUrl; // Authorization URL
```

**Methods:**
```php
public static function fromArray(array $data): self
public function isUnsigned(): bool
public function isAuthorized(): bool
public function isExecuted(): bool
public function isCancelled(): bool
public function isRejected(): bool
public function requiresAuthorization(): bool
public function toArray(): array
```

---

#### Synchronization Model

**Namespace:** `AlchemicStudio\Ponto\Models`

**Properties:**
```php
public readonly string $id;
public readonly string $type; // 'synchronization'
public readonly string $status; // 'pending', 'running', 'success', 'error'
public readonly string $resourceType; // 'account'
public readonly string $resourceId;
public readonly string $subtype; // 'accountDetails', 'accountTransactions'
public readonly array $errors;
public readonly \DateTimeImmutable $createdAt;
public readonly \DateTimeImmutable $updatedAt;
```

**Methods:**
```php
public static function fromArray(array $data): self
public function isPending(): bool
public function isRunning(): bool
public function isSuccessful(): bool
public function hasErrors(): bool
public function isComplete(): bool // success or error
public function toArray(): array
```

---

#### PaginatedCollection Model

**Namespace:** `AlchemicStudio\Ponto\Models`

**Type Parameters:** `@template T`

**Properties:**
```php
/** @var array<T> */
public readonly array $data;
public readonly int $limit;
public readonly ?string $beforeCursor;
public readonly ?string $afterCursor;
public readonly ?string $firstUrl;
public readonly ?string $prevUrl;
public readonly ?string $nextUrl;
```

**Methods:**
```php
/**
 * @param array<T> $data
 */
public static function fromArray(array $data, array $meta, array $links): self
public function hasNextPage(): bool
public function hasPrevPage(): bool
public function isEmpty(): bool
public function count(): int
public function toArray(): array
```

---

### 7. AuthProvider Class

**Namespace:** `AlchemicStudio\Ponto\Auth`  
**Purpose:** OAuth2 token management

#### Constructor
```php
public function __construct(
    private string $clientId,
    private string $clientSecret,
    private string $tokenUrl,
    private TokenStorage $tokenStorage,
    private HttpClient $httpClient
)
```

#### Public Methods

```php
/**
 * Get valid access token (from cache or new)
 * 
 * @return string Access token
 * @throws AuthenticationException on auth failure
 */
public function getAccessToken(): string

/**
 * Force token refresh
 * 
 * @return string New access token
 * @throws AuthenticationException on auth failure
 */
public function refreshToken(): string

/**
 * Get token scopes
 * 
 * @return array<string> Scopes (e.g., ['ai', 'pi'])
 */
public function getScopes(): array

/**
 * Check if scope is granted
 * 
 * @param string $scope Scope to check (e.g., 'pi')
 * @return bool
 */
public function hasScope(string $scope): bool

/**
 * Get token expiration time
 * 
 * @return \DateTimeImmutable|null
 */
public function getExpiresAt(): ?\DateTimeImmutable
```

**Thrown Exceptions:**
- `AuthenticationException`: OAuth failures, invalid credentials

---

### 8. HttpClient Class

**Namespace:** `AlchemicStudio\Ponto\Http`  
**Purpose:** HTTP communication with retry logic

#### Constructor
```php
public function __construct(
    private \GuzzleHttp\Client $guzzle,
    private AuthProvider $authProvider,
    private int $maxRetries = 3,
    private int $retryDelayMs = 1000
)
```

#### Public Methods

```php
/**
 * Execute GET request
 * 
 * @param string $path API path (e.g., '/accounts')
 * @param array<string, mixed> $query Query parameters
 * @return array JSON-decoded response
 * @throws ApiException on API errors
 * @throws RateLimitException on 429
 */
public function get(string $path, array $query = []): array

/**
 * Execute POST request
 * 
 * @param string $path API path
 * @param array<string, mixed> $body Request body
 * @param array<string, string> $headers Additional headers
 * @return array JSON-decoded response
 * @throws ApiException on API errors
 */
public function post(string $path, array $body = [], array $headers = []): array

/**
 * Execute DELETE request
 * 
 * @param string $path API path
 * @return void
 * @throws ApiException on API errors
 */
public function delete(string $path): void

/**
 * Get last response metadata
 * 
 * @return array{statusCode: int, headers: array}
 */
public function getLastResponseMeta(): array
```

**Retry Logic:**
- Retries on network errors and 5xx responses
- Exponential backoff: 1s, 2s, 4s
- Does NOT retry 4xx errors (except 429 with Retry-After)
- Respects Retry-After header for 429 responses

**Thrown Exceptions:**
- `AuthenticationException`: 401 responses
- `ValidationException`: 400 responses
- `NotFoundException`: 404 responses
- `RateLimitException`: 429 responses
- `ApiException`: Other 4xx/5xx responses

---

### 9. Exception Hierarchy

**Base Exception:**
```php
namespace AlchemicStudio\Ponto\Exceptions;

class PontoException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        private ?array $errorDetails = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }
}
```

**Specific Exceptions:**

```php
// Authentication failures (401, invalid tokens)
class AuthenticationException extends PontoException {}

// Validation errors (400, invalid input)
class ValidationException extends PontoException {}

// Resource not found (404)
class NotFoundException extends PontoException {}

// Rate limit exceeded (429)
class RateLimitException extends PontoException
{
    public function __construct(
        string $message,
        private ?int $retryAfterSeconds = null,
        ?array $errorDetails = null
    ) {
        parent::__construct($message, 429, $errorDetails);
    }
    
    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}

// General API errors (4xx/5xx)
class ApiException extends PontoException
{
    public function __construct(
        string $message,
        int $statusCode,
        private ?string $errorCode = null,
        ?array $errorDetails = null
    ) {
        parent::__construct($message, $statusCode, $errorDetails);
    }
    
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
```

---
