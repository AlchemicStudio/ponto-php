<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Exceptions\ApiException;
use AlchemicStudio\Ponto\Exceptions\PontoException;

test('ApiException extends PontoException', function () {
    $exception = new ApiException('API error', 500);

    expect($exception)->toBeInstanceOf(PontoException::class)
        ->and($exception)->toBeInstanceOf(\Exception::class);
});

test('can create ApiException with message and status code', function () {
    $exception = new ApiException('Internal server error', 500);

    expect($exception->getMessage())->toBe('Internal server error')
        ->and($exception->getCode())->toBe(500);
});

test('ApiException with error code', function () {
    $exception = new ApiException('Bad request', 400, 'invalid_request');

    expect($exception->getErrorCode())->toBe('invalid_request')
        ->and($exception->getCode())->toBe(400);
});

test('ApiException with null error code', function () {
    $exception = new ApiException('Error', 500, null);

    expect($exception->getErrorCode())->toBeNull();
});

test('ApiException with error details', function () {
    $errorDetails = [
        'type' => 'server_error',
        'message' => 'Database connection failed',
        'timestamp' => '2024-02-20T10:00:00Z',
    ];

    $exception = new ApiException('Server error', 500, 'db_error', $errorDetails);

    expect($exception->getErrorDetails())->toBe($errorDetails)
        ->and($exception->getErrorCode())->toBe('db_error')
        ->and($exception->getErrorDetails()['type'])->toBe('server_error');
});

test('ApiException for 400 bad request', function () {
    $exception = new ApiException('Bad request', 400, 'bad_request');

    expect($exception->getCode())->toBe(400)
        ->and($exception->getErrorCode())->toBe('bad_request');
});

test('ApiException for 403 forbidden', function () {
    $exception = new ApiException('Forbidden', 403, 'insufficient_permissions');

    expect($exception->getCode())->toBe(403)
        ->and($exception->getErrorCode())->toBe('insufficient_permissions');
});

test('ApiException for 409 conflict', function () {
    $exception = new ApiException('Resource conflict', 409, 'duplicate_resource');

    expect($exception->getCode())->toBe(409)
        ->and($exception->getErrorCode())->toBe('duplicate_resource');
});

test('ApiException for 422 unprocessable entity', function () {
    $exception = new ApiException('Unprocessable entity', 422, 'validation_failed');

    expect($exception->getCode())->toBe(422)
        ->and($exception->getErrorCode())->toBe('validation_failed');
});

test('ApiException for 500 internal server error', function () {
    $exception = new ApiException('Internal server error', 500, 'internal_error');

    expect($exception->getCode())->toBe(500)
        ->and($exception->getErrorCode())->toBe('internal_error');
});

test('ApiException for 502 bad gateway', function () {
    $exception = new ApiException('Bad gateway', 502, 'upstream_error');

    expect($exception->getCode())->toBe(502)
        ->and($exception->getErrorCode())->toBe('upstream_error');
});

test('ApiException for 503 service unavailable', function () {
    $exception = new ApiException('Service unavailable', 503, 'maintenance');

    expect($exception->getCode())->toBe(503)
        ->and($exception->getErrorCode())->toBe('maintenance');
});

test('ApiException for 504 gateway timeout', function () {
    $exception = new ApiException('Gateway timeout', 504, 'timeout');

    expect($exception->getCode())->toBe(504)
        ->and($exception->getErrorCode())->toBe('timeout');
});

test('ApiException with synchronization too soon error', function () {
    $errorDetails = [
        'resource_type' => 'account',
        'resource_id' => 'acc-123',
        'retry_after' => 300,
    ];

    $exception = new ApiException(
        'Synchronization requested too soon',
        409,
        'synchronization_too_soon',
        $errorDetails
    );

    expect($exception->getCode())->toBe(409)
        ->and($exception->getErrorCode())->toBe('synchronization_too_soon')
        ->and($exception->getErrorDetails()['retry_after'])->toBe(300);
});

test('ApiException can be caught as PontoException', function () {
    try {
        throw new ApiException('Test', 500, 'test_error');
    } catch (PontoException $e) {
        expect($e)->toBeInstanceOf(ApiException::class)
            ->and($e->getErrorCode())->toBe('test_error');
    }
});

test('ApiException with complex error details from API', function () {
    $errorDetails = [
        'errors' => [
            [
                'code' => 'invalid_parameter',
                'detail' => 'The limit parameter must be between 1 and 100',
                'source' => ['parameter' => 'limit'],
            ],
        ],
    ];

    $exception = new ApiException('Invalid parameters', 400, 'invalid_request', $errorDetails);

    expect($exception->getErrorDetails()['errors'][0]['code'])->toBe('invalid_parameter')
        ->and($exception->getErrorDetails()['errors'][0]['source']['parameter'])->toBe('limit');
});
