<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Models\Transaction;

test('can create Transaction from array', function () {
    $data = mockTransactionData();
    $transaction = Transaction::fromArray($data);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->id)->toBeString()
        ->and($transaction->type)->toBe('transaction');
});

test('Transaction has all required properties', function () {
    $data = mockTransactionData([
        'id' => 'tx-123',
        'attributes' => [
            'amount' => 150.75,
            'currency' => 'EUR',
            'description' => 'Payment to supplier',
            'counterpartName' => 'Supplier Ltd',
            'counterpartReference' => 'BE68539007547034',
        ],
    ]);

    $transaction = Transaction::fromArray($data);

    expect($transaction->id)->toBe('tx-123')
        ->and($transaction->amount)->toBe(150.75)
        ->and($transaction->currency)->toBe('EUR')
        ->and($transaction->description)->toBe('Payment to supplier')
        ->and($transaction->counterpartName)->toBe('Supplier Ltd')
        ->and($transaction->counterpartReference)->toBe('BE68539007547034');
});

test('Transaction isCredit returns true for positive amount', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['amount' => 100.00],
    ]));

    expect($transaction->isCredit())->toBeTrue()
        ->and($transaction->isDebit())->toBeFalse();
});

test('Transaction isDebit returns true for negative amount', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['amount' => -50.00],
    ]));

    expect($transaction->isDebit())->toBeTrue()
        ->and($transaction->isCredit())->toBeFalse();
});

test('Transaction getAbsoluteAmount returns positive value', function () {
    $debit = Transaction::fromArray(mockTransactionData(['attributes' => ['amount' => -75.50]]));
    $credit = Transaction::fromArray(mockTransactionData(['attributes' => ['amount' => 75.50]]));

    expect($debit->getAbsoluteAmount())->toBe(75.50)
        ->and($credit->getAbsoluteAmount())->toBe(75.50);
});

test('Transaction with zero amount', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['amount' => 0.0],
    ]));

    expect($transaction->amount)->toBe(0.0)
        ->and($transaction->isCredit())->toBeFalse()
        ->and($transaction->isDebit())->toBeFalse();
});

test('Transaction parses dates correctly', function () {
    $transaction = Transaction::fromArray(mockTransactionData());

    expect($transaction->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($transaction->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

test('Transaction with null execution date', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['executionDate' => null],
    ]));

    expect($transaction->executionDate)->toBeNull();
});

test('Transaction with null value date', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['valueDate' => null],
    ]));

    expect($transaction->valueDate)->toBeNull();
});

test('Transaction with optional counterpart fields', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => [
            'counterpartName' => 'John Doe',
            'counterpartReference' => 'BE68539007547034',
        ],
    ]));

    expect($transaction->counterpartName)->toBe('John Doe')
        ->and($transaction->counterpartReference)->toBe('BE68539007547034');
});

test('Transaction with null counterpart fields', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => [
            'counterpartName' => null,
            'counterpartReference' => null,
        ],
    ]));

    expect($transaction->counterpartName)->toBeNull()
        ->and($transaction->counterpartReference)->toBeNull();
});

test('Transaction with remittance information', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => [
            'remittanceInformation' => 'Invoice 2024-001',
            'remittanceInformationType' => 'unstructured',
        ],
    ]));

    expect($transaction->remittanceInformation)->toBe('Invoice 2024-001')
        ->and($transaction->remittanceInformationType)->toBe('unstructured');
});

test('Transaction with structured remittance', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => [
            'remittanceInformation' => '+++123/4567/89012+++',
            'remittanceInformationType' => 'structured',
        ],
    ]));

    expect($transaction->remittanceInformationType)->toBe('structured');
});

test('Transaction with endToEndId', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['endToEndId' => 'e2e-123456'],
    ]));

    expect($transaction->endToEndId)->toBe('e2e-123456');
});

test('Transaction with mandateId and creditorId', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => [
            'mandateId' => 'mandate-123',
            'creditorId' => 'creditor-456',
        ],
    ]));

    expect($transaction->mandateId)->toBe('mandate-123')
        ->and($transaction->creditorId)->toBe('creditor-456');
});

test('Transaction with bank transaction code', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['bankTransactionCode' => 'PMNT-RCDT-ESCT'],
    ]));

    expect($transaction->bankTransactionCode)->toBe('PMNT-RCDT-ESCT');
});

test('Transaction with purpose code', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['purposeCode' => 'SALA'],
    ]));

    expect($transaction->purposeCode)->toBe('SALA');
});

test('Transaction with fee', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['fee' => 2.50],
    ]));

    expect($transaction->fee)->toBe(2.50);
});

test('Transaction with null fee', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['fee' => null],
    ]));

    expect($transaction->fee)->toBeNull();
});

test('Transaction with additional information', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['additionalInformation' => 'Some additional details'],
    ]));

    expect($transaction->additionalInformation)->toBe('Some additional details');
});

test('Transaction toArray returns correct structure', function () {
    $transaction = Transaction::fromArray(mockTransactionData(['id' => 'tx-test']));
    $array = $transaction->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKey('id', 'tx-test')
        ->and($array)->toHaveKey('amount')
        ->and($array)->toHaveKey('currency')
        ->and($array)->toHaveKey('description');
});

test('Transaction readonly properties cannot be modified', function () {
    $transaction = Transaction::fromArray(mockTransactionData());

    expect(fn () => $transaction->amount = 999.99)
        ->toThrow(\Error::class);
});

test('Transaction with digest', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['digest' => hash('sha256', 'test-transaction')],
    ]));

    expect($transaction->digest)->toBeString()
        ->and(strlen($transaction->digest))->toBe(64); // SHA256 length
});

test('Transaction with account relationship', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'relationships' => [
            'account' => [
                'data' => ['id' => 'acc-123', 'type' => 'account'],
            ],
        ],
    ]));

    expect($transaction->accountId)->toBe('acc-123');
});

test('Transaction with large amount', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['amount' => 999999.99],
    ]));

    expect($transaction->amount)->toBe(999999.99)
        ->and($transaction->getAbsoluteAmount())->toBe(999999.99);
});

test('Transaction with small decimal amount', function () {
    $transaction = Transaction::fromArray(mockTransactionData([
        'attributes' => ['amount' => 0.01],
    ]));

    expect($transaction->amount)->toBe(0.01)
        ->and($transaction->isCredit())->toBeTrue();
});
