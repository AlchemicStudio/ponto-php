<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Load Test Environment Variables
|--------------------------------------------------------------------------
|
| Load environment variables from .env.testing file for integration tests
|
*/

use Dotenv\Dotenv;

$dotenv = Dotenv::createUnsafeImmutable(__DIR__ . '/..', '.env.testing');
$dotenv->safeLoad();

/*
|--------------------------------------------------------------------------
| Test Groups
|--------------------------------------------------------------------------
|
| Available groups:
| - unit: Unit tests with mocked dependencies
| - integration: Tests against real Ponto sandbox API
| - feature: End-to-end workflow tests
|
*/

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Custom expectations can be defined here
|
*/

expect()->extend('toBeValidUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

expect()->extend('toBeValidIban', function () {
    return $this->toMatch('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/');
});

expect()->extend('toBeValidBic', function () {
    return $this->toMatch('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/');
});

expect()->extend('toBeValidIso4217Currency', function () {
    return $this->toMatch('/^[A-Z]{3}$/');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Helper functions available in all tests
|
*/

/**
 * Create a mock HTTP response in JSON:API format
 */
function mockJsonApiResponse(array $data, array $meta = [], array $links = []): array
{
    return [
        'data' => $data,
        'meta' => $meta,
        'links' => $links,
    ];
}

/**
 * Create mock account data
 */
function mockAccountData(array $overrides = []): array
{
    $defaults = [
        'id' => 'acc-' . uniqid(),
        'type' => 'account',
        'attributes' => [
            'reference' => 'BE68539007547034',
            'referenceType' => 'IBAN',
            'currency' => 'EUR',
            'subtype' => 'checking',
            'availableBalance' => 1000.00,
            'currentBalance' => 1000.00,
            'holderName' => 'John Doe',
            'product' => 'Easy Account',
            'description' => 'My main account',
            'deprecated' => false,
            'availableBalanceChangedAt' => '2024-02-20T10:00:00Z',
            'currentBalanceChangedAt' => '2024-02-20T10:00:00Z',
            'authorizedAt' => '2024-01-01T00:00:00Z',
            'authorizationExpirationExpectedAt' => '2024-12-31T23:59:59Z',
            'internalReference' => 'internal-ref-123',
        ],
        'relationships' => [
            'financialInstitution' => [
                'data' => ['id' => 'fi-123', 'type' => 'financialInstitution'],
            ],
        ],
        'meta' => [
            'synchronizedAt' => '2024-02-20T10:00:00Z',
        ],
    ];

    // Deep merge attributes
    if (isset($overrides['attributes'])) {
        $overrides['attributes'] = array_merge($defaults['attributes'], $overrides['attributes']);
    }
    if (isset($overrides['relationships'])) {
        $overrides['relationships'] = array_merge($defaults['relationships'], $overrides['relationships']);
    }
    if (isset($overrides['meta'])) {
        $overrides['meta'] = array_merge($defaults['meta'], $overrides['meta']);
    }

    return array_merge($defaults, $overrides);
}

/**
 * Create mock transaction data
 */
function mockTransactionData(array $overrides = []): array
{
    $defaults = [
        'id' => 'tx-' . uniqid(),
        'type' => 'transaction',
        'attributes' => [
            'amount' => 100.50,
            'currency' => 'EUR',
            'description' => 'Test transaction',
            'digest' => hash('sha256', 'test-digest'),
            'executionDate' => '2024-02-20T10:00:00Z',
            'valueDate' => '2024-02-20T10:00:00Z',
            'createdAt' => '2024-02-20T10:00:00Z',
            'updatedAt' => '2024-02-20T10:00:00Z',
            'counterpartName' => 'Counterpart Name',
            'counterpartReference' => 'BE68539007547034',
            'remittanceInformation' => 'Payment reference',
            'remittanceInformationType' => 'unstructured',
            'internalReference' => 'internal-tx-123',
        ],
        'relationships' => [
            'account' => [
                'data' => ['id' => 'acc-123', 'type' => 'account'],
            ],
        ],
    ];

    // Deep merge attributes
    if (isset($overrides['attributes'])) {
        $overrides['attributes'] = array_merge($defaults['attributes'], $overrides['attributes']);
    }
    if (isset($overrides['relationships'])) {
        $overrides['relationships'] = array_merge($defaults['relationships'], $overrides['relationships']);
    }

    return array_merge($defaults, $overrides);
}

/**
 * Create mock payment data
 */
function mockPaymentData(array $overrides = []): array
{
    $defaults = [
        'id' => 'pay-' . uniqid(),
        'type' => 'payment',
        'attributes' => [
            'status' => 'unsigned',
            'amount' => 100.00,
            'currency' => 'EUR',
            'creditorName' => 'Creditor Name',
            'creditorAccountReference' => 'BE68539007547034',
            'creditorAccountReferenceType' => 'IBAN',
            'creditorAgent' => 'NBBEBEBB203',
            'creditorAgentType' => 'BIC',
            'remittanceInformation' => 'Invoice 123',
            'remittanceInformationType' => 'unstructured',
            'endToEndId' => 'e2e-' . uniqid(),
        ],
        'links' => [
            'redirect' => 'https://authorize.myponto.com/payment/pay-123',
        ],
    ];

    // Deep merge attributes
    if (isset($overrides['attributes'])) {
        $overrides['attributes'] = array_merge($defaults['attributes'], $overrides['attributes']);
    }
    if (isset($overrides['links'])) {
        $overrides['links'] = array_merge($defaults['links'], $overrides['links']);
    }

    return array_merge($defaults, $overrides);
}

/**
 * Create mock synchronization data
 */
function mockSynchronizationData(array $overrides = []): array
{
    $defaults = [
        'id' => 'sync-' . uniqid(),
        'type' => 'synchronization',
        'attributes' => [
            'status' => 'pending',
            'resourceType' => 'account',
            'resourceId' => 'acc-123',
            'subtype' => 'accountTransactions',
            'errors' => [],
            'createdAt' => '2024-02-20T10:00:00Z',
            'updatedAt' => '2024-02-20T10:00:00Z',
        ],
    ];

    // Deep merge attributes
    if (isset($overrides['attributes'])) {
        $overrides['attributes'] = array_merge($defaults['attributes'], $overrides['attributes']);
    }

    return array_merge($defaults, $overrides);
}
