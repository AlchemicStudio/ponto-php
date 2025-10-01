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
