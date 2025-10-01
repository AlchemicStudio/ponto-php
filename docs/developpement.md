# Ponto PHP Library - Development Documentation

## Overview and Goals

The **ponto-php** library is a modern PHP SDK for integrating with the Ponto API, which provides:
- **Account Information Services (AIS)**: Retrieve account details, balances, and transaction history
- **Payment Initiation Services (PIS)**: Create and manage single payments, bulk payments, and payment requests

### Key Features
- Full OAuth2 client credentials authentication with automatic token management
- Type-safe models and responses (PHP 8.4+)
- PSR-compliant architecture (PSR-4 autoloading, PSR-12 code style)
- Comprehensive error handling with custom exceptions
- Built-in retry logic with exponential backoff
- Support for pagination, filtering, and synchronization
- Idempotency support for payment operations
- Sandbox and production environment support

### Design Principles
- **Developer Experience**: Intuitive API with fluent interfaces
- **Type Safety**: Full PHP 8.4 type declarations
- **Standards Compliance**: PSR-4, PSR-12, and REST best practices
- **Security First**: No credential logging, secure token storage
- **Testability**: Dependency injection and mockable interfaces

---

## Prerequisites

### System Requirements
- **PHP**: 8.4 or higher
- **Extensions**: 
  - `ext-json` (JSON encoding/decoding)
  - `ext-mbstring` (String manipulation)
  - `ext-curl` or `ext-openssl` (HTTPS requests)
  
### Dependencies
- **Guzzle HTTP Client**: ^7.8 (HTTP requests with retry support)
- **PSR-7/PSR-18**: HTTP message interfaces

### Development Tools
- **Composer**: 2.x for dependency management
- **Pest**: 4.1 for testing
- **PHPStan**: 2.1 for static analysis (level max)
- **PHP CS Fixer**: 3.21 for code style

---

## Installation

### Via Composer

```bash
composer require alchemic-studio/ponto-php
```

### Manual Installation

Add to your `composer.json`:

```json
{
    "require": {
        "php": "^8.4",
        "alchemic-studio/ponto-php": "^1.0",
        "guzzlehttp/guzzle": "^7.8"
    },
    "require-dev": {
        "pestphp/pest": "^4.1",
        "phpstan/phpstan": "^2.1",
        "friendsofphp/php-cs-fixer": "^3.21"
    },
    "autoload": {
        "psr-4": {
            "AlchemicStudio\\Ponto\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "AlchemicStudio\\Ponto\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "pest",
        "test-coverage": "pest --coverage",
        "stan": "phpstan analyse src tests --level=max",
        "format": "php-cs-fixer fix"
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then run:

```bash
composer install
```

---

## Project Layout

### Recommended Directory Structure

```
your-project/
├── src/
│   ├── Client.php                      # Main client entry point
│   ├── Config.php                      # Configuration container
│   ├── Auth/
│   │   ├── AuthProvider.php            # OAuth2 token management
│   │   ├── TokenStorage.php            # Token storage interface
│   │   └── FileTokenStorage.php        # File-based token cache
│   ├── Http/
│   │   ├── HttpClient.php              # HTTP client wrapper
│   │   ├── HttpClientInterface.php     # Client contract
│   │   └── Response.php                # HTTP response wrapper
│   ├── Services/
│   │   ├── AccountService.php          # Account operations
│   │   ├── TransactionService.php      # Transaction operations
│   │   ├── PaymentService.php          # Payment operations
│   │   └── SynchronizationService.php  # Sync operations
│   ├── Models/
│   │   ├── Account.php                 # Account resource
│   │   ├── Transaction.php             # Transaction resource
│   │   ├── Payment.php                 # Payment resource
│   │   ├── Synchronization.php         # Synchronization resource
│   │   ├── FinancialInstitution.php    # Financial institution
│   │   └── PaginatedCollection.php     # Paginated results
│   ├── Exceptions/
│   │   ├── PontoException.php          # Base exception
│   │   ├── AuthenticationException.php # Auth failures
│   │   ├── ValidationException.php     # Validation errors
│   │   ├── NotFoundException.php       # 404 errors
│   │   ├── RateLimitException.php      # Rate limit errors
│   │   └── ApiException.php            # API errors
│   └── Utils/
│       ├── Validator.php               # Input validation
│       └── DateHelper.php              # Date formatting
├── tests/
│   ├── Unit/                           # Unit tests
│   ├── Integration/                    # Integration tests
│   └── Pest.php                        # Pest configuration
├── examples/
│   ├── authenticate.php
│   ├── list-accounts.php
│   ├── list-transactions.php
│   ├── create-payment.php
│   └── handle-synchronization.php
├── docs/
    └── developpement.md               # This file
```

---
## Module Breakdown

### 1. Authentication Module (`Auth/`)
Handles OAuth2 client credentials flow, token lifecycle management, and secure storage.

**Components:**
- `AuthProvider`: Manages token acquisition and refresh
- `TokenStorage`: Interface for token persistence
- `FileTokenStorage`: File-based token cache implementation

**Responsibilities:**
- Obtain access tokens via OAuth2
- Cache valid tokens to minimize API calls
- Automatically refresh expired tokens
- Secure token storage

### 2. HTTP Client Module (`Http/`)
Abstracts HTTP communication with retry logic, error handling, and rate limiting.

**Components:**
- `HttpClient`: Guzzle-based HTTP client wrapper
- `HttpClientInterface`: Contract for HTTP operations
- `Response`: Normalized response object

**Responsibilities:**
- Execute HTTP requests (GET, POST, DELETE)
- Handle authentication headers
- Implement retry with exponential backoff
- Parse JSON:API responses
- Rate limit handling

### 3. Services Module (`Services/`)
High-level business logic for interacting with Ponto API resources.

**Components:**
- `AccountService`: Account listing and retrieval
- `TransactionService`: Transaction querying with filters
- `PaymentService`: Payment creation and management
- `SynchronizationService`: Data synchronization orchestration

**Responsibilities:**
- Resource-specific operations
- Parameter validation
- Result mapping to models
- Pagination handling

### 4. Models Module (`Models/`)
Immutable value objects representing API resources.

**Components:**
- `Account`: Bank account with balances
- `Transaction`: Financial transaction
- `Payment`: Payment instruction
- `Synchronization`: Sync job status
- `FinancialInstitution`: Bank details
- `PaginatedCollection`: Collection with pagination metadata

**Responsibilities:**
- Type-safe data containers
- JSON deserialization
- Business logic methods (e.g., `isSuccessful()`, `isPending()`)

### 5. Exceptions Module (`Exceptions/`)
Hierarchy of exceptions for comprehensive error handling.

**Components:**
- `PontoException`: Base exception
- `AuthenticationException`: OAuth/token errors
- `ValidationException`: Input validation failures
- `NotFoundException`: Resource not found (404)
- `RateLimitException`: Rate limit exceeded (429)
- `ApiException`: General API errors (4xx/5xx)

### 6. Utilities Module (`Utils/`)
Helper classes for common operations.

**Components:**
- `Validator`: Input validation (IBAN, BIC, amounts)
- `DateHelper`: Date formatting for API requests

---

## Core Classes - Detailed Specifications

### 1. Client Class

**Namespace:** `AlchemicStudio\Ponto`  
**Purpose:** Main entry point for the Ponto SDK

#### Properties
```php
private Config $config;
private AuthProvider $authProvider;
private HttpClient $httpClient;
private ?AccountService $accountService = null;
private ?TransactionService $transactionService = null;
private ?PaymentService $paymentService = null;
private ?SynchronizationService $synchronizationService = null;
```

#### Constructor
```php
public function __construct(
    string $clientId,
    string $clientSecret,
    string $baseUrl = 'https://api.myponto.com',
    ?TokenStorage $tokenStorage = null,
    array $httpOptions = []
)
```

**Parameters:**
- `$clientId`: OAuth2 client ID from Ponto dashboard
- `$clientSecret`: OAuth2 client secret
- `$baseUrl`: API base URL (production or sandbox)
- `$tokenStorage`: Optional custom token storage (defaults to FileTokenStorage)
- `$httpOptions`: Guzzle HTTP client options

#### Public Methods

```php
/**
 * Get account service for account operations
 * 
 * @return AccountService
 */
public function accounts(): AccountService

/**
 * Get transaction service for transaction operations
 * 
 * @return TransactionService
 */
public function transactions(): TransactionService

/**
 * Get payment service for payment operations
 * 
 * @return PaymentService
 * @throws AuthenticationException if 'pi' scope not granted
 */
public function payments(): PaymentService

/**
 * Get synchronization service
 * 
 * @return SynchronizationService
 */
public function synchronizations(): SynchronizationService

/**
 * Force token refresh
 * 
 * @return void
 * @throws AuthenticationException on auth failure
 */
public function refreshToken(): void

/**
 * Check if payment initiation is enabled
 * 
 * @return bool
 */
public function hasPaymentScope(): bool
```

**Thrown Exceptions:**
- `AuthenticationException`: Authentication failures
- `ApiException`: API communication errors

---

### 2. AccountService Class

**Namespace:** `AlchemicStudio\Ponto\Services`  
**Purpose:** Manage account operations

#### Constructor
```php
public function __construct(
    private HttpClient $httpClient
)
```

#### Public Methods

```php
/**
 * List all accounts
 * 
 * @param int $limit Number of results per page (1-100)
 * @param string|null $after Cursor for next page
 * @param string|null $before Cursor for previous page
 * @return PaginatedCollection<Account>
 * @throws ValidationException if limit out of range
 * @throws ApiException on API errors
 */
public function list(
    int $limit = 20,
    ?string $after = null,
    ?string $before = null
): PaginatedCollection

/**
 * Get single account by ID
 * 
 * @param string $accountId UUID of the account
 * @return Account
 * @throws NotFoundException if account not found
 * @throws ApiException on API errors
 */
public function get(string $accountId): Account

/**
 * Get synchronization metadata for an account
 * 
 * @param string $accountId
 * @return array{synchronizedAt: string, latestSynchronization: Synchronization}
 * @throws NotFoundException if account not found
 */
public function getSyncMetadata(string $accountId): array
```

**Thrown Exceptions:**
- `ValidationException`: Invalid parameters
- `NotFoundException`: Account not found
- `ApiException`: API errors

---

### 3. TransactionService Class

**Namespace:** `AlchemicStudio\Ponto\Services`  
**Purpose:** Query transactions with filtering and pagination

#### Constructor
```php
public function __construct(
    private HttpClient $httpClient
)
```

#### Public Methods

```php
/**
 * List transactions for an account
 * 
 * @param string $accountId Account UUID
 * @param array{
 *     limit?: int,
 *     after?: string,
 *     before?: string,
 *     since?: string,
 *     until?: string
 * } $filters Optional filters
 * @return PaginatedCollection<Transaction>
 * @throws NotFoundException if account not found
 * @throws ValidationException if filters invalid
 * @throws ApiException on API errors
 */
public function list(string $accountId, array $filters = []): PaginatedCollection

/**
 * Get single transaction
 * 
 * @param string $accountId Account UUID
 * @param string $transactionId Transaction UUID
 * @return Transaction
 * @throws NotFoundException if not found
 * @throws ApiException on API errors
 */
public function get(string $accountId, string $transactionId): Transaction

/**
 * List pending transactions
 * 
 * @param string $accountId Account UUID
 * @param int $limit Results per page (1-100)
 * @param string|null $after Cursor for next page
 * @param string|null $before Cursor for previous page
 * @return PaginatedCollection<Transaction>
 * @throws NotFoundException if account not found
 * @throws ApiException on API errors
 */
public function listPending(
    string $accountId,
    int $limit = 20,
    ?string $after = null,
    ?string $before = null
): PaginatedCollection

/**
 * Get updated transactions from a synchronization
 * 
 * @param string $synchronizationId Synchronization UUID
 * @return array<Transaction>
 * @throws NotFoundException if sync not found
 * @throws ApiException on API errors
 */
public function getUpdatedFromSync(string $synchronizationId): array
```

**Thrown Exceptions:**
- `ValidationException`: Invalid filters
- `NotFoundException`: Resource not found
- `ApiException`: API errors

---

### 4. PaymentService Class

**Namespace:** `AlchemicStudio\Ponto\Services`  
**Purpose:** Create and manage payments

#### Constructor
```php
public function __construct(
    private HttpClient $httpClient
)
```

#### Public Methods

```php
/**
 * Create a payment
 * 
 * @param string $accountId Debtor account UUID
 * @param array{
 *     amount: float,
 *     currency: string,
 *     creditorName: string,
 *     creditorAccountReference: string,
 *     creditorAccountReferenceType: string,
 *     creditorAgent: string,
 *     creditorAgentType: string,
 *     remittanceInformation: string,
 *     remittanceInformationType?: string,
 *     endToEndId?: string,
 *     requestedExecutionDate?: string,
 *     redirectUri?: string
 * } $paymentData Payment details
 * @return Payment
 * @throws ValidationException if data invalid
 * @throws AuthenticationException if 'pi' scope missing
 * @throws ApiException on API errors
 */
public function create(string $accountId, array $paymentData): Payment

/**
 * Get payment details
 * 
 * @param string $accountId Account UUID
 * @param string $paymentId Payment UUID
 * @return Payment
 * @throws NotFoundException if not found
 * @throws ApiException on API errors
 */
public function get(string $accountId, string $paymentId): Payment

/**
 * Delete (cancel) a payment
 * 
 * @param string $accountId Account UUID
 * @param string $paymentId Payment UUID
 * @return void
 * @throws NotFoundException if not found
 * @throws ApiException on API errors
 */
public function delete(string $accountId, string $paymentId): void
```

**Thrown Exceptions:**
- `ValidationException`: Invalid payment data
- `AuthenticationException`: Missing payment scope
- `NotFoundException`: Resource not found
- `ApiException`: API errors

---

### 5. SynchronizationService Class

**Namespace:** `AlchemicStudio\Ponto\Services`  
**Purpose:** Manage data synchronization

#### Constructor
```php
public function __construct(
    private HttpClient $httpClient
)
```

#### Public Methods

```php
/**
 * Create synchronization job
 * 
 * @param string $resourceType 'account' or 'transaction'
 * @param string $resourceId UUID of resource to sync
 * @param string $subtype 'accountDetails', 'accountTransactions', etc.
 * @param string|null $customerIpAddress Client IP for audit
 * @return Synchronization
 * @throws ValidationException if invalid parameters
 * @throws ApiException on API errors (e.g., sync too soon)
 */
public function create(
    string $resourceType,
    string $resourceId,
    string $subtype,
    ?string $customerIpAddress = null
): Synchronization

/**
 * Get synchronization status
 * 
 * @param string $synchronizationId Sync UUID
 * @return Synchronization
 * @throws NotFoundException if not found
 * @throws ApiException on API errors
 */
public function get(string $synchronizationId): Synchronization

/**
 * Poll synchronization until complete or timeout
 * 
 * @param string $synchronizationId Sync UUID
 * @param int $maxAttempts Maximum polling attempts
 * @param int $intervalSeconds Seconds between polls
 * @return Synchronization
 * @throws ApiException if timeout or sync fails
 */
public function pollUntilComplete(
    string $synchronizationId,
    int $maxAttempts = 20,
    int $intervalSeconds = 3
): Synchronization
```

**Thrown Exceptions:**
- `ValidationException`: Invalid parameters
- `NotFoundException`: Sync not found
- `ApiException`: API errors or sync failures

---
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
## Complete Usage Examples

### 1. Authentication

**Basic Client Initialization:**

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use AlchemicStudio\Ponto\Client;
use AlchemicStudio\Ponto\Exceptions\AuthenticationException;

// Production environment
$client = new Client(
    clientId: '8f0e9068-48f5-4b45-84ee-1e8adb386c0d',
    clientSecret: 'a9e726f9-8078-4f70-8570-e3c8600d7816',
    baseUrl: 'https://api.myponto.com'
);

// Sandbox environment
$sandboxClient = new Client(
    clientId: 'sandbox_client_id',
    clientSecret: 'sandbox_secret',
    baseUrl: 'https://api.myponto.com' // Same URL, credentials determine mode
);

try {
    // Token is automatically fetched on first API call
    $accounts = $client->accounts()->list();
    echo "Authenticated successfully!\n";
} catch (AuthenticationException $e) {
    echo "Authentication failed: " . $e->getMessage() . "\n";
    exit(1);
}
```

**Custom Token Storage:**

```php
<?php

use AlchemicStudio\Ponto\Auth\TokenStorage;

class RedisTokenStorage implements TokenStorage
{
    public function __construct(private \Redis $redis) {}
    
    public function get(string $key): ?string
    {
        $value = $this->redis->get("ponto:token:{$key}");
        return $value !== false ? $value : null;
    }
    
    public function set(string $key, string $value, int $ttl): void
    {
        $this->redis->setex("ponto:token:{$key}", $ttl, $value);
    }
    
    public function delete(string $key): void
    {
        $this->redis->del("ponto:token:{$key}");
    }
}

// Use custom storage
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

$client = new Client(
    clientId: $_ENV['PONTO_CLIENT_ID'],
    clientSecret: $_ENV['PONTO_CLIENT_SECRET'],
    tokenStorage: new RedisTokenStorage($redis)
);
```

---

### 2. Fetching Accounts

**List All Accounts:**

```php
<?php

use AlchemicStudio\Ponto\Client;

$client = new Client(
    clientId: $_ENV['PONTO_CLIENT_ID'],
    clientSecret: $_ENV['PONTO_CLIENT_SECRET']
);

// Get first page of accounts
$accountsPage = $client->accounts()->list(limit: 20);

foreach ($accountsPage->data as $account) {
    echo sprintf(
        "Account: %s (%s)\n",
        $account->reference,
        $account->holderName
    );
    echo sprintf(
        "  Available Balance: %.2f %s\n",
        $account->availableBalance,
        $account->currency
    );
    echo sprintf(
        "  Current Balance: %.2f %s\n",
        $account->currentBalance,
        $account->currency
    );
    echo "\n";
}

// Pagination
if ($accountsPage->hasNextPage()) {
    $nextPage = $client->accounts()->list(
        limit: 20,
        after: $accountsPage->afterCursor
    );
    echo "Loaded next page with " . count($nextPage->data) . " accounts\n";
}
```

**Get Single Account:**

```php
<?php

use AlchemicStudio\Ponto\Exceptions\NotFoundException;

$accountId = '2ca42e3a-357b-4dc4-a22f-197d049cd2fa';

try {
    $account = $client->accounts()->get($accountId);
    
    echo "Account Details:\n";
    echo "  IBAN: {$account->reference}\n";
    echo "  Holder: {$account->holderName}\n";
    echo "  Product: {$account->product}\n";
    echo "  Available: {$account->availableBalance} {$account->currency}\n";
    
    if ($account->needsReauthorization()) {
        echo "  ⚠️  Authorization expires soon!\n";
    }
} catch (NotFoundException $e) {
    echo "Account not found\n";
}
```

---

### 3. Fetching Transactions

**List Transactions with Filters:**

```php
<?php

use AlchemicStudio\Ponto\Client;

$client = new Client(
    clientId: $_ENV['PONTO_CLIENT_ID'],
    clientSecret: $_ENV['PONTO_CLIENT_SECRET']
);

$accountId = '2ca42e3a-357b-4dc4-a22f-197d049cd2fa';

// Fetch transactions from the last 30 days
$since = (new \DateTimeImmutable('-30 days'))->format('Y-m-d');
$until = (new \DateTimeImmutable())->format('Y-m-d');

$transactions = $client->transactions()->list(
    accountId: $accountId,
    filters: [
        'limit' => 50,
        'since' => $since,
        'until' => $until,
    ]
);

echo "Found {$transactions->count()} transactions:\n\n";

foreach ($transactions->data as $tx) {
    $type = $tx->isCredit() ? '💰 Credit' : '💸 Debit';
    echo sprintf(
        "%s | %s | %.2f %s | %s\n",
        $tx->executionDate?->format('Y-m-d') ?? 'N/A',
        $type,
        $tx->getAbsoluteAmount(),
        $tx->currency,
        $tx->description
    );
    
    if ($tx->counterpartName) {
        echo "     Counterpart: {$tx->counterpartName} ({$tx->counterpartReference})\n";
    }
    if ($tx->remittanceInformation) {
        echo "     Reference: {$tx->remittanceInformation}\n";
    }
    echo "\n";
}

// Pagination through all transactions
$allTransactions = [];
$currentPage = $transactions;

do {
    $allTransactions = array_merge($allTransactions, $currentPage->data);
    
    if ($currentPage->hasNextPage()) {
        $currentPage = $client->transactions()->list(
            accountId: $accountId,
            filters: [
                'limit' => 50,
                'after' => $currentPage->afterCursor,
                'since' => $since,
                'until' => $until,
            ]
        );
    } else {
        break;
    }
} while (true);

echo "Total transactions loaded: " . count($allTransactions) . "\n";
```

**Get Single Transaction:**

```php
<?php

$transactionId = '14b8c6a8-f447-4c70-98ce-cc734c588370';

try {
    $tx = $client->transactions()->get($accountId, $transactionId);
    
    echo "Transaction Details:\n";
    echo "  ID: {$tx->id}\n";
    echo "  Amount: {$tx->amount} {$tx->currency}\n";
    echo "  Description: {$tx->description}\n";
    echo "  Execution Date: " . $tx->executionDate?->format('Y-m-d H:i:s') . "\n";
    echo "  Value Date: " . $tx->valueDate?->format('Y-m-d H:i:s') . "\n";
    
    if ($tx->endToEndId) {
        echo "  End-to-End ID: {$tx->endToEndId}\n";
    }
} catch (NotFoundException $e) {
    echo "Transaction not found\n";
}
```

---

### 4. Creating Payments

**Create Simple Payment:**

```php
<?php

use AlchemicStudio\Ponto\Client;
use AlchemicStudio\Ponto\Exceptions\ValidationException;
use AlchemicStudio\Ponto\Exceptions\AuthenticationException;

$client = new Client(
    clientId: $_ENV['PONTO_CLIENT_ID'],
    clientSecret: $_ENV['PONTO_CLIENT_SECRET']
);

// Check if payment scope is available
if (/home/sebastien/Codes/ponto-phpclient->hasPaymentScope()) {
    echo "Payment initiation not enabled for this integration\n";
    exit(1);
}

$accountId = '2ca42e3a-357b-4dc4-a22f-197d049cd2fa';

try {
    $payment = $client->payments()->create(
        accountId: $accountId,
        paymentData: [
            'amount' => 59.00,
            'currency' => 'EUR',
            'creditorName' => 'Alex Creditor',
            'creditorAccountReference' => 'BE55732022998044',
            'creditorAccountReferenceType' => 'IBAN',
            'creditorAgent' => 'NBBEBEBB203',
            'creditorAgentType' => 'BIC',
            'remittanceInformation' => 'Invoice 2024-001',
            'remittanceInformationType' => 'unstructured',
            'endToEndId' => uniqid('payment_'), // Idempotency key
            'redirectUri' => 'https://your-app.com/payment-callback',
        ]
    );
    
    echo "Payment created successfully!\n";
    echo "  Payment ID: {$payment->id}\n";
    echo "  Status: {$payment->status}\n";
    echo "  Amount: {$payment->amount} {$payment->currency}\n";
    
    if ($payment->requiresAuthorization()) {
        echo "\n⚠️  Payment requires authorization\n";
        echo "Redirect user to: {$payment->redirectUrl}\n";
    }
    
} catch (ValidationException $e) {
    echo "Invalid payment data: " . $e->getMessage() . "\n";
    print_r($e->getErrorDetails());
} catch (AuthenticationException $e) {
    echo "Authentication error: " . $e->getMessage() . "\n";
}
```

**Create Future-Dated Payment:**

```php
<?php

$futureDate = (new \DateTimeImmutable('+7 days'))->format('Y-m-d');

$payment = $client->payments()->create(
    accountId: $accountId,
    paymentData: [
        'amount' => 150.00,
        'currency' => 'EUR',
        'creditorName' => 'Supplier Ltd',
        'creditorAccountReference' => 'BE68539007547034',
        'creditorAccountReferenceType' => 'IBAN',
        'creditorAgent' => 'NBBEBEBB203',
        'creditorAgentType' => 'BIC',
        'remittanceInformation' => 'Monthly subscription',
        'requestedExecutionDate' => $futureDate, // Future-dated
    ]
);

echo "Future payment scheduled for {$futureDate}\n";
```

**Check Payment Status:**

```php
<?php

$paymentId = 'df9c7241-a872-4601-956f-42c26258cef4';

$payment = $client->payments()->get($accountId, $paymentId);

echo "Payment Status: {$payment->status}\n";

if ($payment->isExecuted()) {
    echo "✅ Payment has been executed\n";
} elseif ($payment->isAuthorized()) {
    echo "⏳ Payment authorized, awaiting execution\n";
} elseif ($payment->isUnsigned()) {
    echo "⚠️  Payment awaiting authorization\n";
} elseif ($payment->isRejected()) {
    echo "❌ Payment rejected\n";
} elseif ($payment->isCancelled()) {
    echo "🚫 Payment cancelled\n";
}
```

---

### 5. Synchronization

**Sync Account Transactions:**

```php
<?php

use AlchemicStudio\Ponto\Client;

$client = new Client(
    clientId: $_ENV['PONTO_CLIENT_ID'],
    clientSecret: $_ENV['PONTO_CLIENT_SECRET']
);

$accountId = '2ca42e3a-357b-4dc4-a22f-197d049cd2fa';

// Create synchronization
$sync = $client->synchronizations()->create(
    resourceType: 'account',
    resourceId: $accountId,
    subtype: 'accountTransactions',
    customerIpAddress: $_SERVER['REMOTE_ADDR'] ?? null
);

echo "Synchronization started: {$sync->id}\n";
echo "Status: {$sync->status}\n";

// Poll until complete (max 60 seconds)
try {
    $completedSync = $client->synchronizations()->pollUntilComplete(
        synchronizationId: $sync->id,
        maxAttempts: 20,
        intervalSeconds: 3
    );
    
    if ($completedSync->isSuccessful()) {
        echo "✅ Synchronization completed successfully\n";
        
        // Get updated transactions
        $transactions = $client->transactions()->getUpdatedFromSync($sync->id);
        echo "Retrieved " . count($transactions) . " updated transactions\n";
        
        // Now fetch the latest transactions
        $latestTransactions = $client->transactions()->list($accountId, ['limit' => 100]);
        echo "Latest transaction count: {$latestTransactions->count()}\n";
    } else {
        echo "❌ Synchronization failed\n";
        print_r($completedSync->errors);
    }
} catch (\AlchemicStudio\Ponto\Exceptions\ApiException $e) {
    echo "Synchronization error: " . $e->getMessage() . "\n";
}
```

**Sync Account Details:**

```php
<?php

// Sync account details (balance, holder info)
$sync = $client->synchronizations()->create(
    resourceType: 'account',
    resourceId: $accountId,
    subtype: 'accountDetails'
);

// Poll with custom intervals
$attempt = 0;
$maxAttempts = 10;

while ($attempt < $maxAttempts) {
    sleep(2);
    $attempt++;
    
    $status = $client->synchronizations()->get($sync->id);
    
    echo "Attempt {$attempt}: {$status->status}\n";
    
    if ($status->isComplete()) {
        if ($status->isSuccessful()) {
            echo "Account details synchronized\n";
            
            // Fetch updated account
            $account = $client->accounts()->get($accountId);
            echo "Updated balance: {$account->availableBalance} {$account->currency}\n";
        } else {
            echo "Sync failed: " . implode(', ', $status->errors) . "\n";
        }
        break;
    }
}
```

---

### 6. Error Handling & Retry Strategy

**Comprehensive Error Handling:**

```php
<?php

use AlchemicStudio\Ponto\Client;
use AlchemicStudio\Ponto\Exceptions\{
    PontoException,
    AuthenticationException,
    ValidationException,
    NotFoundException,
    RateLimitException,
    ApiException
};

$client = new Client(
    clientId: $_ENV['PONTO_CLIENT_ID'],
    clientSecret: $_ENV['PONTO_CLIENT_SECRET']
);

function safeApiCall(callable $operation): mixed
{
    $maxRetries = 3;
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $operation();
            
        } catch (RateLimitException $e) {
            $retryAfter = $e->getRetryAfterSeconds() ?? 60;
            echo "Rate limited. Retrying after {$retryAfter}s...\n";
            sleep($retryAfter);
            $attempt++;
            
        } catch (AuthenticationException $e) {
            echo "Authentication failed: " . $e->getMessage() . "\n";
            // Don't retry auth errors - fix credentials
            throw $e;
            
        } catch (ValidationException $e) {
            echo "Validation error: " . $e->getMessage() . "\n";
            print_r($e->getErrorDetails());
            // Don't retry validation errors - fix input
            throw $e;
            
        } catch (NotFoundException $e) {
            echo "Resource not found: " . $e->getMessage() . "\n";
            // Don't retry 404s
            throw $e;
            
        } catch (ApiException $e) {
            echo "API error ({$e->getCode()}): " . $e->getMessage() . "\n";
            
            if ($e->getCode() >= 500) {
                // Retry server errors with exponential backoff
                $backoff = min(60, pow(2, $attempt));
                echo "Retrying in {$backoff}s...\n";
                sleep($backoff);
                $attempt++;
            } else {
                // Don't retry 4xx errors
                throw $e;
            }
            
        } catch (PontoException $e) {
            echo "Ponto error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    throw new \RuntimeException("Max retries exceeded");
}

// Usage
try {
    $accounts = safeApiCall(fn() => $client->accounts()->list());
    echo "Retrieved " . $accounts->count() . " accounts\n";
} catch (\Throwable $e) {
    echo "Failed to retrieve accounts: " . $e->getMessage() . "\n";
    // Log error, notify admin, etc.
}
```

---

### 7. Idempotency for Payments

**Using endToEndId for Idempotency:**

```php
<?php

use AlchemicStudio\Ponto\Client;

$client = new Client(
    clientId: $_ENV['PONTO_CLIENT_ID'],
    clientSecret: $_ENV['PONTO_CLIENT_SECRET']
);

// Generate idempotency key
$idempotencyKey = hash('sha256', 'invoice_12345_' . date('Y-m-d'));

function createPaymentIdempotent(Client $client, string $accountId, string $idempotencyKey): void
{
    try {
        $payment = $client->payments()->create(
            accountId: $accountId,
            paymentData: [
                'amount' => 100.00,
                'currency' => 'EUR',
                'creditorName' => 'Merchant',
                'creditorAccountReference' => 'BE68539007547034',
                'creditorAccountReferenceType' => 'IBAN',
                'creditorAgent' => 'NBBEBEBB203',
                'creditorAgentType' => 'BIC',
                'remittanceInformation' => 'Invoice 12345',
                'endToEndId' => $idempotencyKey, // Ensures uniqueness
            ]
        );
        
        echo "Payment created: {$payment->id}\n";
        
        // Store payment ID in your database
        // savePaymentId($idempotencyKey, $payment->id);
        
    } catch (\AlchemicStudio\Ponto\Exceptions\ValidationException $e) {
        // If duplicate endToEndId, payment may already exist
        echo "Payment may already exist: " . $e->getMessage() . "\n";
    }
}

// Safe to call multiple times - same endToEndId prevents duplicates
createPaymentIdempotent($client, $accountId, $idempotencyKey);
```

---
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
## Testing Approach

### Testing Strategy

The library uses **Pest v4.1** for testing with a functional approach.

**Test Types:**
1. **Unit Tests**: Test individual classes in isolation with mocks
2. **Integration Tests**: Test against Ponto sandbox API
3. **Feature Tests**: Test complete user workflows

### Unit Tests

**Example: Testing TransactionService**

**`tests/Unit/Services/TransactionServiceTest.php`:**

```php
<?php

use AlchemicStudio\Ponto\Services\TransactionService;
use AlchemicStudio\Ponto\Http\HttpClient;
use AlchemicStudio\Ponto\Models\Transaction;
use AlchemicStudio\Ponto\Models\PaginatedCollection;
use AlchemicStudio\Ponto\Exceptions\NotFoundException;

beforeEach(function () {
    $this->httpClient = Mockery::mock(HttpClient::class);
    $this->service = new TransactionService($this->httpClient);
});

afterEach(function () {
    Mockery::close();
});

test('list transactions returns paginated collection', function () {
    $accountId = 'acc-123';
    $mockResponse = [
        'data' => [
            [
                'id' => 'tx-1',
                'type' => 'transaction',
                'attributes' => [
                    'amount' => 100.50,
                    'currency' => 'EUR',
                    'description' => 'Test transaction',
                    'executionDate' => '2024-02-20T10:00:00Z',
                    'createdAt' => '2024-02-20T10:00:00Z',
                    'updatedAt' => '2024-02-20T10:00:00Z',
                ],
                'relationships' => [
                    'account' => [
                        'data' => ['id' => $accountId, 'type' => 'account'],
                    ],
                ],
            ],
        ],
        'meta' => [
            'paging' => [
                'limit' => 20,
            ],
        ],
        'links' => [],
    ];

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with("/accounts/{$accountId}/transactions", ['limit' => 20])
        ->andReturn($mockResponse);

    $result = $this->service->list($accountId);

    expect($result)->toBeInstanceOf(PaginatedCollection::class);
    expect($result->count())->toBe(1);
    expect($result->data[0])->toBeInstanceOf(Transaction::class);
    expect($result->data[0]->amount)->toBe(100.50);
});

test('list transactions with filters applies correct query parameters', function () {
    $accountId = 'acc-123';
    $filters = [
        'limit' => 50,
        'since' => '2024-01-01',
        'until' => '2024-12-31',
        'after' => 'cursor-123',
    ];

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with("/accounts/{$accountId}/transactions", $filters)
        ->andReturn(['data' => [], 'meta' => ['paging' => []], 'links' => []]);

    $this->service->list($accountId, $filters);
});

test('get transaction by id returns single transaction', function () {
    $accountId = 'acc-123';
    $transactionId = 'tx-456';
    
    $mockResponse = [
        'data' => [
            'id' => $transactionId,
            'type' => 'transaction',
            'attributes' => [
                'amount' => -50.00,
                'currency' => 'EUR',
                'description' => 'Payment',
                'executionDate' => '2024-02-20T10:00:00Z',
                'createdAt' => '2024-02-20T10:00:00Z',
                'updatedAt' => '2024-02-20T10:00:00Z',
            ],
            'relationships' => [
                'account' => [
                    'data' => ['id' => $accountId, 'type' => 'account'],
                ],
            ],
        ],
    ];

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with("/accounts/{$accountId}/transactions/{$transactionId}", [])
        ->andReturn($mockResponse);

    $transaction = $this->service->get($accountId, $transactionId);

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->id)->toBe($transactionId);
    expect($transaction->isDebit())->toBeTrue();
});

test('get non-existent transaction throws NotFoundException', function () {
    $accountId = 'acc-123';
    $transactionId = 'tx-nonexistent';

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->andThrow(new NotFoundException("Transaction not found"));

    $this->service->get($accountId, $transactionId);
})->throws(NotFoundException::class);
```

**Example: Testing Payment Model**

**`tests/Unit/Models/PaymentTest.php`:**

```php
<?php

use AlchemicStudio\Ponto\Models\Payment;

test('payment can be created from array', function () {
    $data = [
        'id' => 'pay-123',
        'type' => 'payment',
        'attributes' => [
            'status' => 'unsigned',
            'amount' => 100.00,
            'currency' => 'EUR',
            'creditorName' => 'John Doe',
            'creditorAccountReference' => 'BE68539007547034',
            'creditorAccountReferenceType' => 'IBAN',
            'creditorAgent' => 'NBBEBEBB203',
            'creditorAgentType' => 'BIC',
            'remittanceInformation' => 'Invoice 123',
            'remittanceInformationType' => 'unstructured',
        ],
        'links' => [
            'redirect' => 'https://authorize.myponto.com/payment/pay-123',
        ],
    ];

    $payment = Payment::fromArray($data);

    expect($payment->id)->toBe('pay-123');
    expect($payment->status)->toBe('unsigned');
    expect($payment->amount)->toBe(100.00);
    expect($payment->creditorName)->toBe('John Doe');
    expect($payment->redirectUrl)->toBe('https://authorize.myponto.com/payment/pay-123');
});

test('payment status checks work correctly', function () {
    $createPayment = fn($status) => Payment::fromArray([
        'id' => 'pay-123',
        'type' => 'payment',
        'attributes' => [
            'status' => $status,
            'amount' => 100,
            'currency' => 'EUR',
            'creditorName' => 'Test',
            'creditorAccountReference' => 'BE68539007547034',
            'creditorAccountReferenceType' => 'IBAN',
            'creditorAgent' => 'NBBEBEBB203',
            'creditorAgentType' => 'BIC',
            'remittanceInformation' => 'Test',
            'remittanceInformationType' => 'unstructured',
        ],
    ]);

    expect($createPayment('unsigned')->isUnsigned())->toBeTrue();
    expect($createPayment('authorized')->isAuthorized())->toBeTrue();
    expect($createPayment('executed')->isExecuted())->toBeTrue();
    expect($createPayment('cancelled')->isCancelled())->toBeTrue();
    expect($createPayment('rejected')->isRejected())->toBeTrue();
    
    expect($createPayment('unsigned')->requiresAuthorization())->toBeTrue();
    expect($createPayment('executed')->requiresAuthorization())->toBeFalse();
});

test('payment converts to array correctly', function () {
    $payment = Payment::fromArray([
        'id' => 'pay-123',
        'type' => 'payment',
        'attributes' => [
            'status' => 'executed',
            'amount' => 50.50,
            'currency' => 'EUR',
            'creditorName' => 'Test',
            'creditorAccountReference' => 'BE68539007547034',
            'creditorAccountReferenceType' => 'IBAN',
            'creditorAgent' => 'NBBEBEBB203',
            'creditorAgentType' => 'BIC',
            'remittanceInformation' => 'Test payment',
            'remittanceInformationType' => 'unstructured',
        ],
    ]);

    $array = $payment->toArray();

    expect($array)->toHaveKey('id', 'pay-123');
    expect($array)->toHaveKey('status', 'executed');
    expect($array)->toHaveKey('amount', 50.50);
});
```

**Example: Testing Validator**

**`tests/Unit/Utils/ValidatorTest.php`:**

```php
<?php

use AlchemicStudio\Ponto\Utils\Validator;
use AlchemicStudio\Ponto\Exceptions\ValidationException;

test('validates valid IBAN', function () {
    $iban = Validator::validateIban('BE68 5390 0754 7034');
    expect($iban)->toBe('BE68539007547034');
});

test('rejects invalid IBAN', function () {
    Validator::validateIban('INVALID');
})->throws(ValidationException::class);

test('validates valid BIC', function () {
    $bic = Validator::validateBic('NBBEBEBB203');
    expect($bic)->toBe('NBBEBEBB203');
});

test('rejects invalid BIC', function () {
    Validator::validateBic('INVALID');
})->throws(ValidationException::class);

test('validates positive amounts', function () {
    expect(fn() => Validator::validateAmount(100.50))->not->toThrow(ValidationException::class);
});

test('rejects negative amounts', function () {
    Validator::validateAmount(-50.00);
})->throws(ValidationException::class);

test('rejects zero amount', function () {
    Validator::validateAmount(0);
})->throws(ValidationException::class);

test('validates currency codes', function () {
    $currency = Validator::validateCurrency('eur');
    expect($currency)->toBe('EUR');
});

test('rejects invalid currency', function () {
    Validator::validateCurrency('INVALID');
})->throws(ValidationException::class);
```

### Integration Tests

**Example: Sandbox Integration Test**

**`tests/Integration/AccountServiceIntegrationTest.php`:**

```php
<?php

use AlchemicStudio\Ponto\Client;
use AlchemicStudio\Ponto\Models\Account;
use AlchemicStudio\Ponto\Models\PaginatedCollection;

beforeEach(function () {
    // Skip if sandbox credentials not configured
    if (!getenv('PONTO_SANDBOX_CLIENT_ID')) {
        test()->markTestSkipped('Sandbox credentials not configured');
    }

    $this->client = new Client(
        clientId: getenv('PONTO_SANDBOX_CLIENT_ID'),
        clientSecret: getenv('PONTO_SANDBOX_CLIENT_SECRET'),
        baseUrl: 'https://api.myponto.com'
    );
});

test('can list accounts from sandbox', function () {
    $accounts = $this->client->accounts()->list(limit: 10);

    expect($accounts)->toBeInstanceOf(PaginatedCollection::class);
    expect($accounts->data)->toBeArray();
    
    if ($accounts->count() > 0) {
        expect($accounts->data[0])->toBeInstanceOf(Account::class);
        expect($accounts->data[0]->currency)->toBeString();
    }
})->group('integration', 'sandbox');

test('can get single account from sandbox', function () {
    $accounts = $this->client->accounts()->list(limit: 1);
    
    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available in sandbox');
    }

    $accountId = $accounts->data[0]->id;
    $account = $this->client->accounts()->get($accountId);

    expect($account)->toBeInstanceOf(Account::class);
    expect($account->id)->toBe($accountId);
    expect($account->reference)->toBeString();
    expect($account->holderName)->toBeString();
})->group('integration', 'sandbox');

test('can list transactions from sandbox account', function () {
    $accounts = $this->client->accounts()->list(limit: 1);
    
    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;
    $transactions = $this->client->transactions()->list($accountId, ['limit' => 20]);

    expect($transactions)->toBeInstanceOf(PaginatedCollection::class);
    expect($transactions->data)->toBeArray();
})->group('integration', 'sandbox');
```

### Running Tests

```bash
# Run all tests
composer test

# Run only unit tests
./vendor/bin/pest --filter Unit

# Run only integration tests (requires sandbox credentials)
PONTO_SANDBOX_CLIENT_ID=your_id PONTO_SANDBOX_CLIENT_SECRET=your_secret \
./vendor/bin/pest --group=integration

# Run with coverage
composer test-coverage

# Run specific test file
./vendor/bin/pest tests/Unit/Models/PaymentTest.php
```

---

## Framework Integration

### Laravel Integration

**Service Provider:**

**`src/Laravel/PontoServiceProvider.php`:**

```php
<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Laravel;

use AlchemicStudio\Ponto\Client;
use AlchemicStudio\Ponto\Config;
use AlchemicStudio\Ponto\Auth\FileTokenStorage;
use Illuminate\Support\ServiceProvider;

class PontoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/ponto.php', 'ponto');

        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['ponto'];

            return new Client(
                clientId: $config['client_id'],
                clientSecret: $config['client_secret'],
                baseUrl: $config['base_url'],
                tokenStorage: new FileTokenStorage($config['token_storage_path']),
                httpOptions: [
                    'timeout' => $config['http_timeout'],
                    'connect_timeout' => $config['connect_timeout'],
                ]
            );
        });

        $this->app->alias(Client::class, 'ponto');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/ponto.php' => config_path('ponto.php'),
            ], 'ponto-config');
        }
    }

    public function provides(): array
    {
        return [Client::class, 'ponto'];
    }
}
```

**Configuration File:**

**`config/ponto.php`:**

```php
<?php

return [
    'client_id' => env('PONTO_CLIENT_ID'),
    'client_secret' => env('PONTO_CLIENT_SECRET'),
    'base_url' => env('PONTO_BASE_URL', 'https://api.myponto.com'),
    'token_storage_path' => env('PONTO_TOKEN_STORAGE_PATH', storage_path('app/ponto')),
    'http_timeout' => env('PONTO_HTTP_TIMEOUT', 30),
    'connect_timeout' => env('PONTO_HTTP_CONNECT_TIMEOUT', 10),
];
```

**Facade:**

**`src/Laravel/Facades/Ponto.php`:**

```php
<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AlchemicStudio\Ponto\Services\AccountService accounts()
 * @method static \AlchemicStudio\Ponto\Services\TransactionService transactions()
 * @method static \AlchemicStudio\Ponto\Services\PaymentService payments()
 * @method static \AlchemicStudio\Ponto\Services\SynchronizationService synchronizations()
 * @method static void refreshToken()
 * @method static bool hasPaymentScope()
 *
 * @see \AlchemicStudio\Ponto\Client
 */
class Ponto extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ponto';
    }
}
```

**Usage in Laravel:**

```php
<?php

namespace App\Http\Controllers;

use AlchemicStudio\Ponto\Laravel\Facades\Ponto;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = Ponto::accounts()->list(limit: 50);
        
        return view('accounts.index', ['accounts' => $accounts->data]);
    }

    public function transactions(string $accountId)
    {
        $transactions = Ponto::transactions()->list($accountId, [
            'limit' => 100,
            'since' => now()->subDays(30)->format('Y-m-d'),
        ]);
        
        return view('transactions.index', [
            'transactions' => $transactions->data,
            'pagination' => $transactions,
        ]);
    }
}
```

**Artisan Command Example:**

```php
<?php

namespace App\Console\Commands;

use AlchemicStudio\Ponto\Client;
use Illuminate\Console\Command;

class SyncPontoTransactions extends Command
{
    protected $signature = 'ponto:sync {accountId}';
    protected $description = 'Synchronize transactions for a Ponto account';

    public function handle(Client $ponto): int
    {
        $accountId = $this->argument('accountId');

        $this->info("Starting synchronization for account {$accountId}...");

        $sync = $ponto->synchronizations()->create(
            resourceType: 'account',
            resourceId: $accountId,
            subtype: 'accountTransactions'
        );

        $this->info("Synchronization created: {$sync->id}");

        $completed = $ponto->synchronizations()->pollUntilComplete($sync->id);

        if ($completed->isSuccessful()) {
            $this->info('✅ Synchronization completed successfully');
            
            $transactions = $ponto->transactions()->getUpdatedFromSync($sync->id);
            $this->info("Retrieved " . count($transactions) . " updated transactions");
            
            return Command::SUCCESS;
        }

        $this->error('❌ Synchronization failed');
        return Command::FAILURE;
    }
}
```

---
## Compliance Checklist

### API Endpoints Coverage

| Endpoint | Method | Status | Implementation |
|----------|--------|--------|----------------|
| `/oauth2/token` | POST | ✅ Complete | `AuthProvider::refreshToken()` |
| `/accounts` | GET | ✅ Complete | `AccountService::list()` |
| `/accounts/{id}` | GET | ✅ Complete | `AccountService::get()` |
| `/accounts/{id}/transactions` | GET | ✅ Complete | `TransactionService::list()` |
| `/accounts/{id}/transactions/{id}` | GET | ✅ Complete | `TransactionService::get()` |
| `/accounts/{id}/pending-transactions` | GET | ✅ Complete | `TransactionService::listPending()` |
| `/accounts/{id}/payments` | POST | ✅ Complete | `PaymentService::create()` |
| `/accounts/{id}/payments/{id}` | GET | ✅ Complete | `PaymentService::get()` |
| `/accounts/{id}/payments/{id}` | DELETE | ✅ Complete | `PaymentService::delete()` |
| `/accounts/{id}/bulk-payments` | POST | 🟡 Planned | Future enhancement |
| `/accounts/{id}/payment-requests` | POST | 🟡 Planned | Future enhancement |
| `/synchronizations` | POST | ✅ Complete | `SynchronizationService::create()` |
| `/synchronizations/{id}` | GET | ✅ Complete | `SynchronizationService::get()` |
| `/synchronizations/{id}/updated-transactions` | GET | ✅ Complete | `TransactionService::getUpdatedFromSync()` |
| `/financial-institutions` | GET | 🟡 Planned | Future enhancement |

### Fields Supported

#### Account Model
- ✅ `id`, `type`, `reference`, `referenceType`
- ✅ `currency`, `subtype`, `product`, `description`
- ✅ `availableBalance`, `currentBalance`
- ✅ `holderName`, `deprecated`
- ✅ `authorizedAt`, `authorizationExpirationExpectedAt`
- ✅ `internalReference`
- ✅ Synchronization metadata

#### Transaction Model
- ✅ `id`, `type`, `amount`, `currency`, `description`
- ✅ `digest`, `executionDate`, `valueDate`
- ✅ `counterpartName`, `counterpartReference`
- ✅ `remittanceInformation`, `remittanceInformationType`
- ✅ `bankTransactionCode`, `endToEndId`
- ✅ `mandateId`, `creditorId`, `purposeCode`
- ✅ `fee`, `additionalInformation`
- ✅ `createdAt`, `updatedAt`, `internalReference`

#### Payment Model
- ✅ `id`, `type`, `status`
- ✅ `amount`, `currency`
- ✅ `creditorName`, `creditorAccountReference`, `creditorAccountReferenceType`
- ✅ `creditorAgent`, `creditorAgentType`
- ✅ `remittanceInformation`, `remittanceInformationType`
- ✅ `endToEndId`, `requestedExecutionDate`
- ✅ `redirectUrl` for authorization flow

#### Synchronization Model
- ✅ `id`, `type`, `status`
- ✅ `resourceType`, `resourceId`, `subtype`
- ✅ `errors`, `createdAt`, `updatedAt`

### Features Implemented

| Feature | Status | Notes |
|---------|--------|-------|
| OAuth2 Client Credentials | ✅ Complete | With token caching and refresh |
| Token Storage Interface | ✅ Complete | File-based implementation |
| Account Information | ✅ Complete | List and get accounts |
| Transaction Retrieval | ✅ Complete | With filtering and pagination |
| Payment Initiation | ✅ Complete | Single payments with authorization |
| Future-Dated Payments | ✅ Complete | Via `requestedExecutionDate` |
| Synchronization | ✅ Complete | With polling helper |
| Pagination | ✅ Complete | Cursor-based navigation |
| Error Handling | ✅ Complete | Comprehensive exception hierarchy |
| Retry Logic | ✅ Complete | Exponential backoff |
| Rate Limiting | ✅ Complete | Respects `Retry-After` header |
| Idempotency | ✅ Complete | Via `endToEndId` |
| Input Validation | ✅ Complete | IBAN, BIC, amounts, etc. |
| Type Safety | ✅ Complete | Full PHP 8.4 types |
| Sandbox Support | ✅ Complete | Same API, different credentials |
| Laravel Integration | ✅ Complete | ServiceProvider and Facade |

### Known Limitations

| Limitation | Workaround | Priority |
|------------|------------|----------|
| Bulk Payments Not Implemented | Use multiple single payments | Medium |
| Payment Requests Not Implemented | Use standard payments | Low |
| Financial Institution Listing Not Implemented | Use dashboard for linking | Low |
| [ASSUMPTION] No webhooks support | Poll synchronizations | Low |
| [ASSUMPTION] No payment status webhooks | Poll payment status | Medium |
| Synchronization rate limit (30 min) | Cache results, plan syncs carefully | High |

### Assumptions and Safe Defaults

1. **[ASSUMPTION]** Token expiration buffer: 5 minutes before actual expiration
2. **[ASSUMPTION]** Default pagination limit: 20 items (max 100)
3. **[ASSUMPTION]** HTTP timeout: 30 seconds (configurable)
4. **[ASSUMPTION]** Max retries: 3 attempts with exponential backoff
5. **[ASSUMPTION]** Synchronization polling: 20 attempts at 3-second intervals (60s total)
6. **[ASSUMPTION]** File token storage default path: `/tmp/ponto_tokens`
7. **[ASSUMPTION]** IBAN/BIC validation: Basic format checks (not checksum validation)
8. **[ASSUMPTION]** Currency validation: ISO 4217 format check only

---

## Versioning and Continuous Integration

### Semantic Versioning

Follow [SemVer 2.0.0](https://semver.org/):

- **MAJOR** version: Breaking changes (e.g., 1.0.0 → 2.0.0)
- **MINOR** version: New features, backward compatible (e.g., 1.0.0 → 1.1.0)
- **PATCH** version: Bug fixes (e.g., 1.0.0 → 1.0.1)

**Example Version History:**
```
v1.0.0 - Initial release with core features
v1.1.0 - Add bulk payments support
v1.1.1 - Fix token refresh edge case
v2.0.0 - Refactor to use PSR-18 HTTP client interface
```

### Git Workflow

```bash
# Feature branches
git checkout -b feature/bulk-payments
git commit -m "feat: add bulk payment support"
git push origin feature/bulk-payments

# Hotfix branches
git checkout -b hotfix/token-expiration
git commit -m "fix: token expiration edge case"
git push origin hotfix/token-expiration

# Release tagging
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

### Continuous Integration (GitHub Actions)

**`.github/workflows/tests.yml`:**

```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: ['8.4']
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: json, mbstring, curl
        coverage: xdebug
    
    - name: Validate composer.json
      run: composer validate --strict
    
    - name: Cache Composer packages
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run PHPStan
      run: composer stan
    
    - name: Run PHP CS Fixer (check)
      run: vendor/bin/php-cs-fixer fix --dry-run --diff
    
    - name: Run Pest Tests
      run: composer test
    
    - name: Run Pest Tests with Coverage
      run: composer test-coverage
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        files: ./coverage.xml
        flags: unittests
        name: codecov-umbrella

  integration:
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        extensions: json, mbstring, curl
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run Integration Tests
      env:
        PONTO_SANDBOX_CLIENT_ID: ${{ secrets.PONTO_SANDBOX_CLIENT_ID }}
        PONTO_SANDBOX_CLIENT_SECRET: ${{ secrets.PONTO_SANDBOX_CLIENT_SECRET }}
      run: ./vendor/bin/pest --group=integration
```

**`.github/workflows/release.yml`:**

```yaml
name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Create Release
      uses: actions/create-release@v1
      env:
        GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
      with:
        tag_name: ${{ github.ref }}
        release_name: Release ${{ github.ref }}
        draft: false
        prerelease: false
```

### Quality Gates

Before merging to main:
1. ✅ All tests pass (unit + integration)
2. ✅ PHPStan level max passes
3. ✅ PHP CS Fixer formatting applied
4. ✅ Code coverage ≥ 80%
5. ✅ Documentation updated
6. ✅ CHANGELOG.md updated

### Pre-commit Hook

**`.git/hooks/pre-commit`:**

```bash
#!/bin/bash

echo "Running pre-commit checks..."

# Format code
composer format

# Static analysis
composer stan
if [ $? -ne 0 ]; then
    echo "❌ PHPStan failed"
    exit 1
fi

# Tests
composer test
if [ $? -ne 0 ]; then
    echo "❌ Tests failed"
    exit 1
fi

echo "✅ All checks passed"
exit 0
```

---

## Complete Implementation Example

### Minimal Working Client

**`src/Client.php`:**

```php
<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto;

use AlchemicStudio\Ponto\Auth\AuthProvider;
use AlchemicStudio\Ponto\Auth\FileTokenStorage;
use AlchemicStudio\Ponto\Auth\TokenStorage;
use AlchemicStudio\Ponto\Http\HttpClient;
use AlchemicStudio\Ponto\Services\AccountService;
use AlchemicStudio\Ponto\Services\TransactionService;
use AlchemicStudio\Ponto\Services\PaymentService;
use AlchemicStudio\Ponto\Services\SynchronizationService;
use AlchemicStudio\Ponto\Exceptions\AuthenticationException;

final class Client
{
    private AuthProvider $authProvider;
    private HttpClient $httpClient;
    private ?AccountService $accountService = null;
    private ?TransactionService $transactionService = null;
    private ?PaymentService $paymentService = null;
    private ?SynchronizationService $synchronizationService = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $baseUrl = 'https://api.myponto.com',
        ?TokenStorage $tokenStorage = null,
        array $httpOptions = []
    ) {
        $tokenStorage ??= new FileTokenStorage();
        
        $guzzle = new \GuzzleHttp\Client(array_merge([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true,
        ], $httpOptions));

        $this->httpClient = new HttpClient($guzzle);
        
        $this->authProvider = new AuthProvider(
            clientId: $clientId,
            clientSecret: $clientSecret,
            tokenUrl: $baseUrl . '/oauth2/token',
            tokenStorage: $tokenStorage,
            httpClient: $this->httpClient
        );
        
        $this->httpClient->setAuthProvider($this->authProvider);
    }

    public function accounts(): AccountService
    {
        return $this->accountService ??= new AccountService($this->httpClient);
    }

    public function transactions(): TransactionService
    {
        return $this->transactionService ??= new TransactionService($this->httpClient);
    }

    public function payments(): PaymentService
    {
        if (/home/sebastien/Codes/ponto-phpthis->hasPaymentScope()) {
            throw new AuthenticationException('Payment initiation scope (pi) not granted');
        }
        
        return $this->paymentService ??= new PaymentService($this->httpClient);
    }

    public function synchronizations(): SynchronizationService
    {
        return $this->synchronizationService ??= new SynchronizationService($this->httpClient);
    }

    public function refreshToken(): void
    {
        $this->authProvider->refreshToken();
    }

    public function hasPaymentScope(): bool
    {
        return $this->authProvider->hasScope('pi');
    }
}
```

### Quick Start Example

**`examples/quickstart.php`:**

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AlchemicStudio\Ponto\Client;

// Initialize client
$client = new Client(
    clientId: getenv('PONTO_CLIENT_ID'),
    clientSecret: getenv('PONTO_CLIENT_SECRET')
);

echo "=== Ponto PHP Library - Quick Start ===\n\n";

// 1. List accounts
echo "1. Fetching accounts...\n";
$accounts = $client->accounts()->list(limit: 10);
echo "   Found {$accounts->count()} accounts\n\n";

foreach ($accounts->data as $account) {
    echo "   - {$account->reference} ({$account->holderName})\n";
    echo "     Balance: {$account->availableBalance} {$account->currency}\n";
}

// 2. Get transactions for first account
if ($accounts->count() > 0) {
    $accountId = $accounts->data[0]->id;
    
    echo "\n2. Fetching transactions...\n";
    $transactions = $client->transactions()->list($accountId, ['limit' => 5]);
    echo "   Found {$transactions->count()} recent transactions\n\n";
    
    foreach ($transactions->data as $tx) {
        $type = $tx->isCredit() ? '💰' : '💸';
        echo "   {$type} {$tx->amount} {$tx->currency} - {$tx->description}\n";
    }
}

// 3. Create a payment (if scope available)
if ($client->hasPaymentScope() && $accounts->count() > 0) {
    echo "\n3. Creating test payment...\n";
    
    try {
        $payment = $client->payments()->create($accountId, [
            'amount' => 10.00,
            'currency' => 'EUR',
            'creditorName' => 'Test Recipient',
            'creditorAccountReference' => 'BE68539007547034',
            'creditorAccountReferenceType' => 'IBAN',
            'creditorAgent' => 'NBBEBEBB203',
            'creditorAgentType' => 'BIC',
            'remittanceInformation' => 'Test payment from PHP library',
            'endToEndId' => uniqid('test_'),
        ]);
        
        echo "   Payment created: {$payment->id}\n";
        echo "   Status: {$payment->status}\n";
        
        if ($payment->redirectUrl) {
            echo "   Authorize at: {$payment->redirectUrl}\n";
        }
    } catch (\Exception $e) {
        echo "   Payment failed: {$e->getMessage()}\n";
    }
}

echo "\n=== Done ===\n";
```

---

## Summary

This documentation provides a comprehensive guide to implementing the **ponto-php** library for integrating with the Ponto API.

**Key Takeaways:**

1. **Architecture**: Modular design with Services, Models, Auth, and HTTP layers
2. **Type Safety**: Full PHP 8.4 type declarations throughout
3. **Standards Compliance**: PSR-4, PSR-12, and REST best practices
4. **Security**: Environment-based configuration, secure token storage, TLS enforcement
5. **Error Handling**: Comprehensive exception hierarchy with retry logic
6. **Testing**: Pest-based unit and integration tests
7. **Framework Support**: Laravel ServiceProvider and Facade included
8. **Production Ready**: Token management, rate limiting, idempotency support

**Next Steps:**

1. Review the Ponto API documentation at https://documentation.myponto.com
2. Obtain sandbox credentials from https://myponto.com
3. Implement the core classes following the specifications above
4. Write comprehensive tests (aim for 80%+ coverage)
5. Add examples directory with working code samples
6. Set up CI/CD pipeline with GitHub Actions
7. Publish to Packagist for easy installation

**Support:**

- GitHub Issues: For bug reports and feature requests
- Pull Requests: Contributions welcome following PSR-12
- Documentation: Keep this file updated with API changes
- Changelog: Maintain CHANGELOG.md following Keep a Changelog format

---

*Document Version: 1.0.0*  
*Last Updated: 2024-02-20*  
*Compatible with: Ponto API v1, PHP 8.4+*
