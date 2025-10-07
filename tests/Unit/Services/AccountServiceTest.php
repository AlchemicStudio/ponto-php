<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Exceptions\NotFoundException;
use AlchemicStudio\Ponto\Exceptions\ValidationException;
use AlchemicStudio\Ponto\Http\HttpClient;
use AlchemicStudio\Ponto\Models\Account;
use AlchemicStudio\Ponto\Models\PaginatedCollection;
use AlchemicStudio\Ponto\Services\AccountService;

beforeEach(function () {
    $this->httpClient = Mockery::mock(HttpClient::class);
    $this->service = new AccountService($this->httpClient);
});

afterEach(function () {
    Mockery::close();
});

test('list returns paginated collection of accounts', function () {
    $mockResponse = mockJsonApiResponse(
        [mockAccountData(), mockAccountData()],
        ['paging' => ['limit' => 20]],
        []
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 20]])
        ->andReturn($mockResponse);

    $result = $this->service->list();

    expect($result)->toBeInstanceOf(PaginatedCollection::class)
        ->and($result->count())->toBe(2)
        ->and($result->limit)->toBe(20);
})->group('unit');

test('list with custom limit', function () {
    $mockResponse = mockJsonApiResponse(
        [],
        ['paging' => ['limit' => 50]],
        []
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 50]])
        ->andReturn($mockResponse);

    $result = $this->service->list(limit: 50);

    expect($result->limit)->toBe(50);
})->group('unit');

test('list with after cursor', function () {
    $mockResponse = mockJsonApiResponse(
        [],
        ['paging' => ['limit' => 20, 'after' => 'cursor123']],
        []
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 20, 'after' => 'cursor123']])
        ->andReturn($mockResponse);

    $result = $this->service->list(after: 'cursor123');

    expect($result->afterCursor)->toBe('cursor123');
})->group('unit');

test('list with before cursor', function () {
    $mockResponse = mockJsonApiResponse(
        [],
        ['paging' => ['limit' => 20, 'before' => 'cursor456']],
        []
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 20, 'before' => 'cursor456']])
        ->andReturn($mockResponse);

    $result = $this->service->list(before: 'cursor456');

    expect($result->beforeCursor)->toBe('cursor456');
})->group('unit');

test('list throws ValidationException for limit below minimum', function () {
    $this->service->list(limit: 0);
})->throws(ValidationException::class)->group('unit');

test('list throws ValidationException for limit above maximum', function () {
    $this->service->list(limit: 101);
})->throws(ValidationException::class)->group('unit');

test('get returns single account', function () {
    $accountId = 'acc-123';
    $mockResponse = ['data' => mockAccountData(['id' => $accountId])];

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with("/accounts/{$accountId}")
        ->andReturn($mockResponse);

    $result = $this->service->get($accountId);

    expect($result)->toBeInstanceOf(Account::class)
        ->and($result->id)->toBe($accountId);
})->group('unit');

test('get throws NotFoundException for non-existent account', function () {
    $accountId = 'acc-nonexistent';

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->andThrow(new NotFoundException("Account {$accountId} not found", 404));

    $this->service->get($accountId);
})->throws(NotFoundException::class)->group('unit');

test('getSyncMetadata returns synchronization info', function () {
    $accountId = 'giovani.klocko@streich.info';
    $mockResponse = [
        'data' => mockAccountData(['id' => $accountId]),
        'meta' => [
            'synchronizedAt' => '2024-02-20T10:00:00Z',
            'latestSynchronization' => mockSynchronizationData(),
        ],
    ];

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with("/accounts/{$accountId}")
        ->andReturn($mockResponse);

    $result = $this->service->getSyncMetadata($accountId);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('synchronizedAt')
        ->and($result)->toHaveKey('latestSynchronization');
})->group('unit');

test('list handles empty results', function () {
    $mockResponse = mockJsonApiResponse(
        [],
        ['paging' => ['limit' => 20]],
        []
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 20]])
        ->andReturn($mockResponse);

    $result = $this->service->list();

    expect($result->isEmpty())->toBeTrue()
        ->and($result->count())->toBe(0);
})->group('unit');

test('list with pagination links', function () {
    $mockResponse = mockJsonApiResponse(
        [mockAccountData()],
        ['paging' => ['limit' => 20]],
        [
            'first' => 'https://api.myponto.com/accounts?limit=20',
            'next' => 'https://api.myponto.com/accounts?after=cursor',
        ]
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 20]])
        ->andReturn($mockResponse);

    $result = $this->service->list();

    expect($result->hasNextPage())->toBeTrue()
        ->and($result->nextUrl)->toContain('after=cursor');
})->group('unit');

test('list validates limit is positive', function () {
    $this->service->list(limit: -5);
})->throws(ValidationException::class)->group('unit');

test('get validates account ID format', function () {
    $this->service->get('');
})->throws(ValidationException::class)->group('unit');

test('list with maximum allowed limit', function () {
    $mockResponse = mockJsonApiResponse(
        [],
        ['paging' => ['limit' => 100]],
        []
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 100]])
        ->andReturn($mockResponse);

    $result = $this->service->list(limit: 100);

    expect($result->limit)->toBe(100);
})->group('unit');

test('list with minimum allowed limit', function () {
    $mockResponse = mockJsonApiResponse(
        [],
        ['paging' => ['limit' => 1]],
        []
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 1]])
        ->andReturn($mockResponse);

    $result = $this->service->list(limit: 1);

    expect($result->limit)->toBe(1);
})->group('unit');

test('list returns Account models with correct properties', function () {
    $mockResponse = mockJsonApiResponse(
        [mockAccountData(['id' => 'acc-test', 'attributes' => ['holderName' => 'John Doe']])],
        ['paging' => ['limit' => 20]],
        []
    );

    $this->httpClient
        ->shouldReceive('get')
        ->once()
        ->with('/accounts', ['page' => ['limit' => 20]])
        ->andReturn($mockResponse);

    $result = $this->service->list();

    expect($result->data[0])->toBeInstanceOf(Account::class)
        ->and($result->data[0]->id)->toBe('acc-test')
        ->and($result->data[0]->holderName)->toBe('John Doe');
})->group('unit');
