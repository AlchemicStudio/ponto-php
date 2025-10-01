<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Models\Account;

test('can create Account from array', function () {
    $data = mockAccountData();
    $account = Account::fromArray($data);

    expect($account)->toBeInstanceOf(Account::class)
        ->and($account->id)->toBeString()
        ->and($account->type)->toBe('account');
});

test('Account has all required properties', function () {
    $data = mockAccountData([
        'id' => 'acc-123',
        'attributes' => [
            'reference' => 'BE68539007547034',
            'referenceType' => 'IBAN',
            'currency' => 'EUR',
            'subtype' => 'checking',
            'availableBalance' => 1500.50,
            'currentBalance' => 1600.00,
            'holderName' => 'John Doe',
            'product' => 'Easy Account',
        ],
    ]);

    $account = Account::fromArray($data);

    expect($account->id)->toBe('acc-123')
        ->and($account->reference)->toBe('BE68539007547034')
        ->and($account->referenceType)->toBe('IBAN')
        ->and($account->currency)->toBe('EUR')
        ->and($account->subtype)->toBe('checking')
        ->and($account->availableBalance)->toBe(1500.50)
        ->and($account->currentBalance)->toBe(1600.00)
        ->and($account->holderName)->toBe('John Doe')
        ->and($account->product)->toBe('Easy Account');
});

test('Account reference is valid IBAN', function () {
    $account = Account::fromArray(mockAccountData());

    expect($account->reference)->toBeValidIban();
});

test('Account currency is valid ISO 4217', function () {
    $account = Account::fromArray(mockAccountData());

    expect($account->currency)->toBeValidIso4217Currency();
});

test('Account isDeprecated returns correct value', function () {
    $deprecatedAccount = Account::fromArray(mockAccountData([
        'attributes' => ['deprecated' => true],
    ]));

    $activeAccount = Account::fromArray(mockAccountData([
        'attributes' => ['deprecated' => false],
    ]));

    expect($deprecatedAccount->isDeprecated())->toBeTrue()
        ->and($activeAccount->isDeprecated())->toBeFalse();
});

test('Account needsReauthorization when expiration is soon', function () {
    $soonExpiring = Account::fromArray(mockAccountData([
        'attributes' => [
            'authorizationExpirationExpectedAt' => (new DateTimeImmutable('+5 days'))->format('Y-m-d\TH:i:s\Z'),
        ],
    ]));

    $account = Account::fromArray($soonExpiring);

    expect($account->needsReauthorization())->toBeTrue();
});

test('Account needsReauthorization when expiration is far', function () {
    $farExpiring = Account::fromArray(mockAccountData([
        'attributes' => [
            'authorizationExpirationExpectedAt' => (new DateTimeImmutable('+60 days'))->format('Y-m-d\TH:i:s\Z'),
        ],
    ]));

    $account = Account::fromArray($farExpiring);

    expect($account->needsReauthorization())->toBeFalse();
});

test('Account with null expiration date', function () {
    $data = mockAccountData([
        'attributes' => ['authorizationExpirationExpectedAt' => null],
    ]);

    $account = Account::fromArray($data);

    expect($account->authorizationExpirationExpectedAt)->toBeNull();
});

test('Account with null description', function () {
    $data = mockAccountData([
        'attributes' => ['description' => null],
    ]);

    $account = Account::fromArray($data);

    expect($account->description)->toBeNull();
});

test('Account parses DateTimeImmutable correctly', function () {
    $account = Account::fromArray(mockAccountData());

    expect($account->availableBalanceChangedAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($account->currentBalanceChangedAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($account->authorizedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

test('Account toArray returns correct structure', function () {
    $account = Account::fromArray(mockAccountData(['id' => 'acc-test']));
    $array = $account->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKey('id', 'acc-test')
        ->and($array)->toHaveKey('reference')
        ->and($array)->toHaveKey('currency')
        ->and($array)->toHaveKey('holderName');
});

test('Account with different subtypes', function () {
    $checking = Account::fromArray(mockAccountData(['attributes' => ['subtype' => 'checking']]));
    $savings = Account::fromArray(mockAccountData(['attributes' => ['subtype' => 'savings']]));
    $credit = Account::fromArray(mockAccountData(['attributes' => ['subtype' => 'credit']]));

    expect($checking->subtype)->toBe('checking')
        ->and($savings->subtype)->toBe('savings')
        ->and($credit->subtype)->toBe('credit');
});

test('Account with zero balance', function () {
    $account = Account::fromArray(mockAccountData([
        'attributes' => [
            'availableBalance' => 0.0,
            'currentBalance' => 0.0,
        ],
    ]));

    expect($account->availableBalance)->toBe(0.0)
        ->and($account->currentBalance)->toBe(0.0);
});

test('Account with negative balance', function () {
    $account = Account::fromArray(mockAccountData([
        'attributes' => [
            'availableBalance' => -500.00,
            'currentBalance' => -500.00,
        ],
    ]));

    expect($account->availableBalance)->toBe(-500.00)
        ->and($account->currentBalance)->toBe(-500.00);
});

test('Account with large balance', function () {
    $account = Account::fromArray(mockAccountData([
        'attributes' => [
            'availableBalance' => 9999999.99,
            'currentBalance' => 10000000.00,
        ],
    ]));

    expect($account->availableBalance)->toBe(9999999.99)
        ->and($account->currentBalance)->toBe(10000000.00);
});

test('Account readonly properties cannot be modified', function () {
    $account = Account::fromArray(mockAccountData());

    expect(fn() => $account->id = 'new-id')
        ->toThrow(\Error::class);
});

test('Account with relationships', function () {
    $data = mockAccountData([
        'relationships' => [
            'financialInstitution' => [
                'data' => ['id' => 'fi-123', 'type' => 'financialInstitution'],
            ],
        ],
    ]);

    $account = Account::fromArray($data);

    expect($account->relationships)->toBeArray()
        ->and($account->relationships)->toHaveKey('financialInstitution');
});

test('Account with metadata', function () {
    $data = mockAccountData([
        'meta' => [
            'synchronizedAt' => '2024-02-20T10:00:00Z',
            'latestSynchronizationId' => 'sync-123',
        ],
    ]);

    $account = Account::fromArray($data);

    expect($account->meta)->toBeArray()
        ->and($account->meta)->toHaveKey('synchronizedAt');
});

test('Account with different currencies', function () {
    $eur = Account::fromArray(mockAccountData(['attributes' => ['currency' => 'EUR']]));
    $usd = Account::fromArray(mockAccountData(['attributes' => ['currency' => 'USD']]));
    $gbp = Account::fromArray(mockAccountData(['attributes' => ['currency' => 'GBP']]));

    expect($eur->currency)->toBe('EUR')
        ->and($usd->currency)->toBe('USD')
        ->and($gbp->currency)->toBe('GBP');
});
