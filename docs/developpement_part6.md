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
