<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Exceptions\AuthenticationException;
use AlchemicStudio\Ponto\Exceptions\PontoException;

test('AuthenticationException extends PontoException', function () {
    $exception = new AuthenticationException('Auth failed');

    expect($exception)->toBeInstanceOf(PontoException::class)
        ->and($exception)->toBeInstanceOf(\Exception::class);
});

test('can create AuthenticationException with message', function () {
    $exception = new AuthenticationException('Invalid credentials');

    expect($exception->getMessage())->toBe('Invalid credentials');
});

test('AuthenticationException typically uses 401 code', function () {
    $exception = new AuthenticationException('Unauthorized', 401);

    expect($exception->getCode())->toBe(401);
});

test('AuthenticationException with error details', function () {
    $errorDetails = [
        'error' => 'invalid_client',
        'error_description' => 'Client authentication failed',
    ];

    $exception = new AuthenticationException('OAuth error', 401, $errorDetails);

    expect($exception->getErrorDetails())->toBe($errorDetails)
        ->and($exception->getErrorDetails()['error'])->toBe('invalid_client');
});

test('AuthenticationException can wrap previous exception', function () {
    $previous = new \RuntimeException('Network error');
    $exception = new AuthenticationException('Failed to authenticate', 0, null, $previous);

    expect($exception->getPrevious())->toBe($previous);
});

test('AuthenticationException for invalid token', function () {
    $exception = new AuthenticationException('Invalid access token', 401);

    expect($exception->getMessage())->toContain('token')
        ->and($exception->getCode())->toBe(401);
});

test('AuthenticationException for expired token', function () {
    $exception = new AuthenticationException('Access token expired', 401);

    expect($exception->getMessage())->toContain('expired');
});

test('AuthenticationException can be caught as PontoException', function () {
    try {
        throw new AuthenticationException('Test');
    } catch (PontoException $e) {
        expect($e)->toBeInstanceOf(AuthenticationException::class);
    }
});
