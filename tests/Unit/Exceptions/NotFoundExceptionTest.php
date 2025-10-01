<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Exceptions\NotFoundException;
use AlchemicStudio\Ponto\Exceptions\PontoException;

test('NotFoundException extends PontoException', function () {
    $exception = new NotFoundException('Resource not found');

    expect($exception)->toBeInstanceOf(PontoException::class)
        ->and($exception)->toBeInstanceOf(\Exception::class);
});

test('can create NotFoundException with message', function () {
    $exception = new NotFoundException('Account not found');

    expect($exception->getMessage())->toBe('Account not found');
});

test('NotFoundException uses 404 code', function () {
    $exception = new NotFoundException('Not found', 404);

    expect($exception->getCode())->toBe(404);
});

test('NotFoundException for account not found', function () {
    $accountId = 'acc-123';
    $exception = new NotFoundException("Account {$accountId} not found", 404);

    expect($exception->getMessage())->toContain('acc-123')
        ->and($exception->getMessage())->toContain('Account');
});

test('NotFoundException for transaction not found', function () {
    $transactionId = 'tx-456';
    $exception = new NotFoundException("Transaction {$transactionId} not found", 404);

    expect($exception->getMessage())->toContain('tx-456')
        ->and($exception->getMessage())->toContain('Transaction');
});

test('NotFoundException for payment not found', function () {
    $paymentId = 'pay-789';
    $exception = new NotFoundException("Payment {$paymentId} not found", 404);

    expect($exception->getMessage())->toContain('pay-789')
        ->and($exception->getMessage())->toContain('Payment');
});

test('NotFoundException for synchronization not found', function () {
    $syncId = 'sync-abc';
    $exception = new NotFoundException("Synchronization {$syncId} not found", 404);

    expect($exception->getMessage())->toContain('sync-abc')
        ->and($exception->getMessage())->toContain('Synchronization');
});

test('NotFoundException with error details', function () {
    $errorDetails = [
        'resource_type' => 'account',
        'resource_id' => 'acc-123',
    ];

    $exception = new NotFoundException('Resource not found', 404, $errorDetails);

    expect($exception->getErrorDetails())->toBe($errorDetails)
        ->and($exception->getErrorDetails()['resource_type'])->toBe('account');
});

test('NotFoundException can be caught as PontoException', function () {
    try {
        throw new NotFoundException('Test');
    } catch (PontoException $e) {
        expect($e)->toBeInstanceOf(NotFoundException::class);
    }
});
