<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Exceptions\ValidationException;
use AlchemicStudio\Ponto\Exceptions\PontoException;

test('ValidationException extends PontoException', function () {
    $exception = new ValidationException('Validation failed');

    expect($exception)->toBeInstanceOf(PontoException::class)
        ->and($exception)->toBeInstanceOf(\Exception::class);
});

test('can create ValidationException with message', function () {
    $exception = new ValidationException('Invalid input data');

    expect($exception->getMessage())->toBe('Invalid input data');
});

test('ValidationException typically uses 400 code', function () {
    $exception = new ValidationException('Bad request', 400);

    expect($exception->getCode())->toBe(400);
});

test('ValidationException with field-specific errors', function () {
    $errorDetails = [
        'errors' => [
            ['field' => 'amount', 'message' => 'must be positive'],
            ['field' => 'currency', 'message' => 'invalid currency code'],
        ],
    ];

    $exception = new ValidationException('Validation failed', 400, $errorDetails);

    expect($exception->getErrorDetails())->toBe($errorDetails)
        ->and($exception->getErrorDetails()['errors'])->toHaveCount(2);
});

test('ValidationException for invalid IBAN', function () {
    $exception = new ValidationException('Invalid IBAN format: INVALID');

    expect($exception->getMessage())->toContain('IBAN');
});

test('ValidationException for invalid amount', function () {
    $exception = new ValidationException('Amount must be positive: -50.00');

    expect($exception->getMessage())->toContain('Amount')
        ->and($exception->getMessage())->toContain('positive');
});

test('ValidationException for limit out of range', function () {
    $exception = new ValidationException('Limit must be between 1 and 100');

    expect($exception->getMessage())->toContain('Limit');
});

test('ValidationException for invalid date format', function () {
    $errorDetails = [
        'field' => 'since',
        'value' => 'invalid-date',
        'expected' => 'Y-m-d format',
    ];

    $exception = new ValidationException('Invalid date format', 400, $errorDetails);

    expect($exception->getErrorDetails()['field'])->toBe('since')
        ->and($exception->getErrorDetails()['expected'])->toBe('Y-m-d format');
});

test('ValidationException can be caught as PontoException', function () {
    try {
        throw new ValidationException('Test');
    } catch (PontoException $e) {
        expect($e)->toBeInstanceOf(ValidationException::class);
    }
});
