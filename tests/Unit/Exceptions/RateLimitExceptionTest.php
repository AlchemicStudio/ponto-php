<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Exceptions\RateLimitException;
use AlchemicStudio\Ponto\Exceptions\PontoException;

test('RateLimitException extends PontoException', function () {
    $exception = new RateLimitException('Rate limit exceeded');

    expect($exception)->toBeInstanceOf(PontoException::class)
        ->and($exception)->toBeInstanceOf(\Exception::class);
});

test('can create RateLimitException with message', function () {
    $exception = new RateLimitException('Too many requests');

    expect($exception->getMessage())->toBe('Too many requests');
});

test('RateLimitException always uses 429 code', function () {
    $exception = new RateLimitException('Rate limit exceeded');

    expect($exception->getCode())->toBe(429);
});

test('RateLimitException with retry after seconds', function () {
    $exception = new RateLimitException('Rate limit exceeded', 60);

    expect($exception->getRetryAfterSeconds())->toBe(60)
        ->and($exception->getCode())->toBe(429);
});

test('RateLimitException with null retry after', function () {
    $exception = new RateLimitException('Rate limit exceeded', null);

    expect($exception->getRetryAfterSeconds())->toBeNull();
});

test('RateLimitException with error details', function () {
    $errorDetails = [
        'limit' => 100,
        'remaining' => 0,
        'reset' => 1708431600,
    ];

    $exception = new RateLimitException('Rate limit exceeded', 60, $errorDetails);

    expect($exception->getErrorDetails())->toBe($errorDetails)
        ->and($exception->getErrorDetails()['remaining'])->toBe(0)
        ->and($exception->getRetryAfterSeconds())->toBe(60);
});

test('RateLimitException with retry after from Retry-After header', function () {
    $retryAfter = 120;
    $exception = new RateLimitException(
        'Rate limit exceeded. Retry after 120 seconds',
        $retryAfter
    );

    expect($exception->getRetryAfterSeconds())->toBe(120)
        ->and($exception->getMessage())->toContain('120');
});

test('RateLimitException with zero retry after', function () {
    $exception = new RateLimitException('Rate limit exceeded', 0);

    expect($exception->getRetryAfterSeconds())->toBe(0);
});

test('RateLimitException with large retry after value', function () {
    $exception = new RateLimitException('Rate limit exceeded', 3600);

    expect($exception->getRetryAfterSeconds())->toBe(3600);
});

test('RateLimitException can be caught as PontoException', function () {
    try {
        throw new RateLimitException('Test', 60);
    } catch (PontoException $e) {
        expect($e)->toBeInstanceOf(RateLimitException::class)
            ->and($e->getRetryAfterSeconds())->toBe(60);
    }
});

test('RateLimitException message suggests retry behavior', function () {
    $exception = new RateLimitException('Too many requests. Please retry later.', 30);

    expect($exception->getMessage())->toContain('retry')
        ->and($exception->getRetryAfterSeconds())->toBe(30);
});
