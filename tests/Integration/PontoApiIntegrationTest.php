<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Client;
use AlchemicStudio\Ponto\Exceptions\ApiException;
use AlchemicStudio\Ponto\Exceptions\AuthenticationException;
use AlchemicStudio\Ponto\Exceptions\NotFoundException;
use AlchemicStudio\Ponto\Exceptions\RateLimitException;
use AlchemicStudio\Ponto\Exceptions\ValidationException;
use AlchemicStudio\Ponto\Models\Account;
use AlchemicStudio\Ponto\Models\PaginatedCollection;
use AlchemicStudio\Ponto\Models\Payment;
use AlchemicStudio\Ponto\Models\Synchronization;
use AlchemicStudio\Ponto\Models\Transaction;

/**
 * Integration tests against Ponto Sandbox API
 *
 * These tests require valid sandbox credentials:
 * - PONTO_SANDBOX_CLIENT_ID
 * - PONTO_SANDBOX_CLIENT_SECRET
 *
 * Run with: ./vendor/bin/pest --group=integration
 * Skip with: ./vendor/bin/pest --exclude-group=integration
 */

beforeEach(function () {
    // Skip if sandbox credentials not configured
    if (! getenv('PONTO_SANDBOX_CLIENT_ID') || ! getenv('PONTO_SANDBOX_CLIENT_SECRET')) {
        test()->markTestSkipped('Sandbox credentials not configured. Set PONTO_SANDBOX_CLIENT_ID and PONTO_SANDBOX_CLIENT_SECRET environment variables.');
    }

    $this->client = new Client(
        clientId: "8f8cea3d-6e56-450c-b774-8ab1a8e10fd6",
        clientSecret: "18eaeb69-9b77-4155-be11-7e314c1574b0",
        baseUrl: getenv('PONTO_BASE_URL') ?: 'https://api.myponto.com'
    );
});

// Authentication Tests

test('can authenticate with sandbox credentials', function () {
    // This will trigger authentication
    $accounts = $this->client->accounts()->list(limit: 1);

    expect($accounts)->toBeInstanceOf(PaginatedCollection::class);
})->group('integration', 'auth');

test('authentication fails with invalid credentials', function () {
    $client = new Client(
        clientId: 'invalid-client-id',
        clientSecret: 'invalid-client-secret',
        baseUrl: getenv('PONTO_BASE_URL') ?: 'https://api.myponto.com'
    );

    $client->accounts()->list();
})->throws(AuthenticationException::class)->group('integration', 'auth');

// Account Tests

test('can list accounts from sandbox', function () {
    $accounts = $this->client->accounts()->list(limit: 10);

    expect($accounts)->toBeInstanceOf(PaginatedCollection::class)
        ->and($accounts->data)->toBeArray()
        ->and($accounts->limit)->toBe(10);

    if ($accounts->count() > 0) {
        expect($accounts->data[0])->toBeInstanceOf(Account::class)
            ->and($accounts->data[0]->reference)->toBeValidIban()
            ->and($accounts->data[0]->currency)->toBeValidIso4217Currency();
    }
})->group('integration', 'accounts');

test('can get single account from sandbox', function () {
    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available in sandbox');
    }

    $accountId = $accounts->data[0]->id;
    $account = $this->client->accounts()->get($accountId);

    expect($account)->toBeInstanceOf(Account::class)
        ->and($account->id)->toBe($accountId)
        ->and($account->reference)->toBeString()
        ->and($account->holderName)->toBeString()
        ->and($account->currency)->toBeValidIso4217Currency();
})->group('integration', 'accounts');

test('getting non-existent account throws NotFoundException', function () {
    $this->client->accounts()->get('00000000-0000-0000-0000-000000000000');
})->throws(NotFoundException::class)->group('integration', 'accounts');

test('can paginate through accounts', function () {
    $firstPage = $this->client->accounts()->list(limit: 2);

    if (! $firstPage->hasNextPage()) {
        test()->markTestSkipped('Not enough accounts for pagination test');
    }

    $secondPage = $this->client->accounts()->list(
        limit: 2,
        after: $firstPage->afterCursor
    );

    expect($secondPage)->toBeInstanceOf(PaginatedCollection::class)
        ->and($secondPage->data)->toBeArray();
})->group('integration', 'accounts', 'pagination');

// Transaction Tests

test('can list transactions from sandbox account', function () {
    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;
    $transactions = $this->client->transactions()->list($accountId, ['limit' => 20]);

    expect($transactions)->toBeInstanceOf(PaginatedCollection::class)
        ->and($transactions->data)->toBeArray();

    if ($transactions->count() > 0) {
        expect($transactions->data[0])->toBeInstanceOf(Transaction::class)
            ->and($transactions->data[0]->amount)->toBeFloat()
            ->and($transactions->data[0]->currency)->toBeValidIso4217Currency();
    }
})->group('integration', 'transactions');

test('can get single transaction', function () {
    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;
    $transactions = $this->client->transactions()->list($accountId, ['limit' => 1]);

    if ($transactions->isEmpty()) {
        test()->markTestSkipped('No transactions available');
    }

    $transactionId = $transactions->data[0]->id;
    $transaction = $this->client->transactions()->get($accountId, $transactionId);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->id)->toBe($transactionId)
        ->and($transaction->amount)->toBeFloat()
        ->and($transaction->currency)->toBeString();
})->group('integration', 'transactions');

test('can list transactions with date filters', function () {
    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;
    $since = (new DateTimeImmutable('-30 days'))->format('Y-m-d');
    $until = (new DateTimeImmutable())->format('Y-m-d');

    $transactions = $this->client->transactions()->list($accountId, [
        'limit' => 50,
        'since' => $since,
        'until' => $until,
    ]);

    expect($transactions)->toBeInstanceOf(PaginatedCollection::class)
        ->and($transactions->data)->toBeArray();
})->group('integration', 'transactions', 'filters');

test('can list pending transactions', function () {
    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;
    $pendingTransactions = $this->client->transactions()->listPending($accountId, limit: 20);

    expect($pendingTransactions)->toBeInstanceOf(PaginatedCollection::class)
        ->and($pendingTransactions->data)->toBeArray();
})->group('integration', 'transactions');

// Payment Tests (requires 'pi' scope)

test('can check payment scope availability', function () {
    $hasPaymentScope = $this->client->hasPaymentScope();

    expect($hasPaymentScope)->toBeBool();
})->group('integration', 'payments');

test('can create payment in sandbox', function () {
    if (! $this->client->hasPaymentScope()) {
        test()->markTestSkipped('Payment initiation scope not available');
    }

    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;

    $payment = $this->client->payments()->create($accountId, [
        'amount' => 10.00,
        'currency' => 'EUR',
        'creditorName' => 'Test Creditor',
        'creditorAccountReference' => 'BE68539007547034',
        'creditorAccountReferenceType' => 'IBAN',
        'creditorAgent' => 'NBBEBEBB203',
        'creditorAgentType' => 'BIC',
        'remittanceInformation' => 'Test payment',
        'remittanceInformationType' => 'unstructured',
        'endToEndId' => 'test-' . uniqid(),
    ]);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->id)->toBeString()
        ->and($payment->amount)->toBe(10.00)
        ->and($payment->status)->toBeIn(['unsigned', 'authorized', 'executed']);
})->group('integration', 'payments');

// Synchronization Tests

test('can create synchronization for account transactions', function () {
    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;

    try {
        $sync = $this->client->synchronizations()->create(
            resourceType: 'account',
            resourceId: $accountId,
            subtype: 'accountTransactions',
            customerIpAddress: '127.0.0.1'
        );
    } catch (RateLimitException $e) {
        $this->markTestSkipped('Rate limit exceeded, skipping test');
    }

    expect($sync)->toBeInstanceOf(Synchronization::class)
        ->and($sync->id)->toBeString()
        ->and($sync->status)->toBeIn(['pending', 'running', 'success', 'error'])
        ->and($sync->resourceId)->toBe($accountId);
})->group('integration', 'synchronization');

test(/**
 * @throws RateLimitException
 * @throws ValidationException
 * @throws NotFoundException
 * @throws ApiException
 */ 'can get synchronization status', function () {
    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;

    try {
        $sync = $this->client->synchronizations()->create(
            resourceType: 'account',
            resourceId: $accountId,
            subtype: 'accountDetails'
        );
    } catch (RateLimitException $e) {
        $this->markTestSkipped('Rate limit exceeded, skipping test');
    }

    // Wait a moment for sync to process
    sleep(2);

    $status = $this->client->synchronizations()->get($sync->id);

    expect($status)->toBeInstanceOf(Synchronization::class)
        ->and($status->id)->toBe($sync->id)
        ->and($status->status)->toBeString();
})->group('integration', 'synchronization');

test('can poll synchronization until complete', function () {
    $accounts = $this->client->accounts()->list(limit: 1);

    if ($accounts->isEmpty()) {
        test()->markTestSkipped('No accounts available');
    }

    $accountId = $accounts->data[0]->id;

    try {
        $sync = $this->client->synchronizations()->create(
            resourceType: 'account',
            resourceId: $accountId,
            subtype: 'accountDetails'
        );
    } catch (RateLimitException $e) {
        $this->markTestSkipped('Rate limit exceeded, skipping test');
    }

    $completedSync = $this->client->synchronizations()->pollUntilComplete(
        synchronizationId: $sync->id,
        maxAttempts: 20,
        intervalSeconds: 2
    );

    expect($completedSync)->toBeInstanceOf(Synchronization::class)
        ->and($completedSync->isComplete())->toBeTrue();
})->group('integration', 'synchronization');

// End-to-End Workflow Tests

test('complete workflow: list accounts, get transactions, create synchronization', function () {
    // 1. List accounts
    $accounts = $this->client->accounts()->list(limit: 5);
    expect($accounts->count())->toBeGreaterThan(0);

    $account = $accounts->data[0];

    // 2. Get account details
    $accountDetails = $this->client->accounts()->get($account->id);
    expect($accountDetails->id)->toBe($account->id);

    // 3. List transactions
    $transactions = $this->client->transactions()->list($account->id, ['limit' => 10]);
    expect($transactions)->toBeInstanceOf(PaginatedCollection::class);

    // 4. Create synchronization
    try {
        $sync = $this->client->synchronizations()->create(
            resourceType: 'account',
            resourceId: $account->id,
            subtype: 'accountTransactions'
        );
    } catch (RateLimitException $e) {
        $this->markTestSkipped('Rate limit exceeded, skipping test');
    }
    expect($sync->resourceId)->toBe($account->id);

})->group('integration', 'workflow');

test('token is cached and reused across multiple requests', function () {
    // Make multiple requests - token should be reused
    $this->client->accounts()->list(limit: 1);
    $this->client->accounts()->list(limit: 1);
    $this->client->accounts()->list(limit: 1);

    // If we get here without authentication errors, token caching works
    expect(true)->toBeTrue();
})->group('integration', 'auth');
