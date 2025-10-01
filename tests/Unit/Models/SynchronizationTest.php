<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Models\Synchronization;

test('can create Synchronization from array', function () {
    $data = mockSynchronizationData();
    $sync = Synchronization::fromArray($data);

    expect($sync)->toBeInstanceOf(Synchronization::class)
        ->and($sync->id)->toBeString()
        ->and($sync->type)->toBe('synchronization');
});

test('Synchronization has all required properties', function () {
    $data = mockSynchronizationData([
        'id' => 'sync-123',
        'attributes' => [
            'status' => 'pending',
            'resourceType' => 'account',
            'resourceId' => 'acc-456',
            'subtype' => 'accountTransactions',
        ],
    ]);

    $sync = Synchronization::fromArray($data);

    expect($sync->id)->toBe('sync-123')
        ->and($sync->status)->toBe('pending')
        ->and($sync->resourceType)->toBe('account')
        ->and($sync->resourceId)->toBe('acc-456')
        ->and($sync->subtype)->toBe('accountTransactions');
});

test('Synchronization isPending returns true for pending status', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['status' => 'pending'],
    ]));

    expect($sync->isPending())->toBeTrue()
        ->and($sync->isRunning())->toBeFalse()
        ->and($sync->isSuccessful())->toBeFalse();
});

test('Synchronization isRunning returns true for running status', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['status' => 'running'],
    ]));

    expect($sync->isRunning())->toBeTrue()
        ->and($sync->isPending())->toBeFalse()
        ->and($sync->isSuccessful())->toBeFalse();
});

test('Synchronization isSuccessful returns true for success status', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['status' => 'success'],
    ]));

    expect($sync->isSuccessful())->toBeTrue()
        ->and($sync->isPending())->toBeFalse()
        ->and($sync->isRunning())->toBeFalse()
        ->and($sync->hasErrors())->toBeFalse();
});

test('Synchronization hasErrors returns true for error status', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => [
            'status' => 'error',
            'errors' => ['Connection timeout', 'Retry limit exceeded'],
        ],
    ]));

    expect($sync->hasErrors())->toBeTrue()
        ->and($sync->isSuccessful())->toBeFalse()
        ->and($sync->errors)->toHaveCount(2);
});

test('Synchronization hasErrors returns false when no errors', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['status' => 'success', 'errors' => []],
    ]));

    expect($sync->hasErrors())->toBeFalse()
        ->and($sync->errors)->toBeEmpty();
});

test('Synchronization isComplete returns true for success status', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['status' => 'success'],
    ]));

    expect($sync->isComplete())->toBeTrue();
});

test('Synchronization isComplete returns true for error status', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['status' => 'error'],
    ]));

    expect($sync->isComplete())->toBeTrue();
});

test('Synchronization isComplete returns false for pending status', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['status' => 'pending'],
    ]));

    expect($sync->isComplete())->toBeFalse();
});

test('Synchronization isComplete returns false for running status', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['status' => 'running'],
    ]));

    expect($sync->isComplete())->toBeFalse();
});

test('Synchronization for accountDetails subtype', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => [
            'resourceType' => 'account',
            'subtype' => 'accountDetails',
        ],
    ]));

    expect($sync->subtype)->toBe('accountDetails')
        ->and($sync->resourceType)->toBe('account');
});

test('Synchronization for accountTransactions subtype', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => [
            'resourceType' => 'account',
            'subtype' => 'accountTransactions',
        ],
    ]));

    expect($sync->subtype)->toBe('accountTransactions');
});

test('Synchronization parses dates correctly', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData());

    expect($sync->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($sync->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

test('Synchronization with empty errors array', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['errors' => []],
    ]));

    expect($sync->errors)->toBeArray()
        ->and($sync->errors)->toBeEmpty()
        ->and($sync->hasErrors())->toBeFalse();
});

test('Synchronization with multiple errors', function () {
    $errors = [
        'Bank connection failed',
        'Authentication required',
        'Service temporarily unavailable',
    ];

    $sync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => [
            'status' => 'error',
            'errors' => $errors,
        ],
    ]));

    expect($sync->errors)->toHaveCount(3)
        ->and($sync->errors[0])->toBe('Bank connection failed')
        ->and($sync->hasErrors())->toBeTrue();
});

test('Synchronization toArray returns correct structure', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData(['id' => 'sync-test']));
    $array = $sync->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKey('id', 'sync-test')
        ->and($array)->toHaveKey('status')
        ->and($array)->toHaveKey('resourceType')
        ->and($array)->toHaveKey('subtype');
});

test('Synchronization readonly properties cannot be modified', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData());

    expect(fn () => $sync->status = 'success')
        ->toThrow(\Error::class);
});

test('Synchronization status transitions make sense', function () {
    // Pending sync should not be complete
    $pending = Synchronization::fromArray(mockSynchronizationData(['attributes' => ['status' => 'pending']]));
    expect($pending->isPending())->toBeTrue()
        ->and($pending->isComplete())->toBeFalse();

    // Running sync should not be complete
    $running = Synchronization::fromArray(mockSynchronizationData(['attributes' => ['status' => 'running']]));
    expect($running->isRunning())->toBeTrue()
        ->and($running->isComplete())->toBeFalse();

    // Success sync should be complete
    $success = Synchronization::fromArray(mockSynchronizationData(['attributes' => ['status' => 'success']]));
    expect($success->isSuccessful())->toBeTrue()
        ->and($success->isComplete())->toBeTrue();

    // Error sync should be complete
    $error = Synchronization::fromArray(mockSynchronizationData(['attributes' => ['status' => 'error']]));
    expect($error->hasErrors())->toBeTrue()
        ->and($error->isComplete())->toBeTrue();
});

test('Synchronization all status states are mutually exclusive', function () {
    $statuses = ['pending', 'running', 'success', 'error'];

    foreach ($statuses as $status) {
        $sync = Synchronization::fromArray(mockSynchronizationData([
            'attributes' => ['status' => $status],
        ]));

        $pending = $sync->isPending();
        $running = $sync->isRunning();
        $successful = $sync->isSuccessful();
        $hasErrors = $sync->hasErrors();

        // Only one status should be active
        $activeCount = ($pending ? 1 : 0) + ($running ? 1 : 0) + ($successful ? 1 : 0) + ($hasErrors ? 1 : 0);

        expect($activeCount)->toBe(1, "Expected exactly one status to be active for {$status}");
    }
});

test('Synchronization with specific resource ID formats', function () {
    $accountSync = Synchronization::fromArray(mockSynchronizationData([
        'attributes' => ['resourceId' => 'acc-123'],
    ]));

    expect($accountSync->resourceId)->toBe('acc-123');
});

test('Synchronization timestamps are immutable', function () {
    $sync = Synchronization::fromArray(mockSynchronizationData());

    expect($sync->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($sync->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);

    // DateTimeImmutable ensures timestamps cannot be modified
    $originalCreated = $sync->createdAt;
    $newTime = $sync->createdAt->modify('+1 hour');

    expect($sync->createdAt)->toBe($originalCreated)
        ->and($newTime)->not->toBe($originalCreated);
});
