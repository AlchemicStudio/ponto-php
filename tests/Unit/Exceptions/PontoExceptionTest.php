<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Exceptions\PontoException;

test('can create base PontoException', function () {
    $exception = new PontoException('Test error message');

    expect($exception)->toBeInstanceOf(\Exception::class)
        ->and($exception->getMessage())->toBe('Test error message')
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getErrorDetails())->toBeNull();
});

test('can create PontoException with error code', function () {
    $exception = new PontoException('Error message', 500);

    expect($exception->getCode())->toBe(500);
});

test('can create PontoException with error details', function () {
    $errorDetails = [
        'field' => 'amount',
        'issue' => 'must be positive',
    ];

    $exception = new PontoException('Validation failed', 400, $errorDetails);

    expect($exception->getErrorDetails())->toBe($errorDetails)
        ->and($exception->getErrorDetails()['field'])->toBe('amount');
});

test('can create PontoException with previous exception', function () {
    $previous = new \RuntimeException('Previous error');
    $exception = new PontoException('Current error', 0, null, $previous);

    expect($exception->getPrevious())->toBe($previous)
        ->and($exception->getPrevious()->getMessage())->toBe('Previous error');
});

test('PontoException with all parameters', function () {
    $previous = new \Exception('Root cause');
    $errorDetails = ['key' => 'value'];

    $exception = new PontoException('Error', 400, $errorDetails, $previous);

    expect($exception->getMessage())->toBe('Error')
        ->and($exception->getCode())->toBe(400)
        ->and($exception->getErrorDetails())->toBe($errorDetails)
        ->and($exception->getPrevious())->toBe($previous);
});

test('PontoException error details can be empty array', function () {
    $exception = new PontoException('Error', 0, []);

    expect($exception->getErrorDetails())->toBe([])
        ->and($exception->getErrorDetails())->toBeArray()
        ->and($exception->getErrorDetails())->toBeEmpty();
});

test('PontoException can be caught as Exception', function () {
    try {
        throw new PontoException('Test');
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(PontoException::class);
    }
});

test('PontoException can be thrown and caught', function () {
    expect(fn() => throw new PontoException('Test error'))
        ->toThrow(PontoException::class, 'Test error');
});
