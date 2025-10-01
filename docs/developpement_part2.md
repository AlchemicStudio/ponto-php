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
