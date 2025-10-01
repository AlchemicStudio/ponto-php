<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Models\PaginatedCollection;

test('can create PaginatedCollection from array', function () {
    $data = [mockAccountData(), mockAccountData()];
    $meta = ['paging' => ['limit' => 20]];
    $links = ['next' => 'https://api.myponto.com/accounts?after=cursor123'];

    $collection = PaginatedCollection::fromArray($data, $meta, $links);

    expect($collection)->toBeInstanceOf(PaginatedCollection::class)
        ->and($collection->data)->toBeArray()
        ->and($collection->data)->toHaveCount(2);
});

test('PaginatedCollection has correct limit', function () {
    $meta = ['paging' => ['limit' => 50]];
    $collection = PaginatedCollection::fromArray([], $meta, []);

    expect($collection->limit)->toBe(50);
});

test('PaginatedCollection with before cursor', function () {
    $meta = ['paging' => ['limit' => 20, 'before' => 'cursor-before']];
    $collection = PaginatedCollection::fromArray([], $meta, []);

    expect($collection->beforeCursor)->toBe('cursor-before');
});

test('PaginatedCollection with after cursor', function () {
    $meta = ['paging' => ['limit' => 20, 'after' => 'cursor-after']];
    $collection = PaginatedCollection::fromArray([], $meta, []);

    expect($collection->afterCursor)->toBe('cursor-after');
});

test('PaginatedCollection with pagination links', function () {
    $links = [
        'first' => 'https://api.myponto.com/accounts?limit=20',
        'prev' => 'https://api.myponto.com/accounts?before=cursor1',
        'next' => 'https://api.myponto.com/accounts?after=cursor2',
    ];

    $collection = PaginatedCollection::fromArray([], ['paging' => []], $links);

    expect($collection->firstUrl)->toBe($links['first'])
        ->and($collection->prevUrl)->toBe($links['prev'])
        ->and($collection->nextUrl)->toBe($links['next']);
});

test('PaginatedCollection hasNextPage returns true when next link exists', function () {
    $links = ['next' => 'https://api.myponto.com/accounts?after=cursor'];
    $collection = PaginatedCollection::fromArray([], ['paging' => []], $links);

    expect($collection->hasNextPage())->toBeTrue();
});

test('PaginatedCollection hasNextPage returns false when no next link', function () {
    $collection = PaginatedCollection::fromArray([], ['paging' => []], []);

    expect($collection->hasNextPage())->toBeFalse();
});

test('PaginatedCollection hasPrevPage returns true when prev link exists', function () {
    $links = ['prev' => 'https://api.myponto.com/accounts?before=cursor'];
    $collection = PaginatedCollection::fromArray([], ['paging' => []], $links);

    expect($collection->hasPrevPage())->toBeTrue();
});

test('PaginatedCollection hasPrevPage returns false when no prev link', function () {
    $collection = PaginatedCollection::fromArray([], ['paging' => []], []);

    expect($collection->hasPrevPage())->toBeFalse();
});

test('PaginatedCollection isEmpty returns true for empty data', function () {
    $collection = PaginatedCollection::fromArray([], ['paging' => []], []);

    expect($collection->isEmpty())->toBeTrue();
});

test('PaginatedCollection isEmpty returns false for non-empty data', function () {
    $data = [mockAccountData()];
    $collection = PaginatedCollection::fromArray($data, ['paging' => []], []);

    expect($collection->isEmpty())->toBeFalse();
});

test('PaginatedCollection count returns correct number of items', function () {
    $data = [mockAccountData(), mockAccountData(), mockAccountData()];
    $collection = PaginatedCollection::fromArray($data, ['paging' => []], []);

    expect($collection->count())->toBe(3);
});

test('PaginatedCollection count returns zero for empty collection', function () {
    $collection = PaginatedCollection::fromArray([], ['paging' => []], []);

    expect($collection->count())->toBe(0);
});

test('PaginatedCollection toArray includes all data', function () {
    $data = [mockAccountData()];
    $meta = ['paging' => ['limit' => 20]];
    $links = ['next' => 'https://example.com/next'];

    $collection = PaginatedCollection::fromArray($data, $meta, $links);
    $array = $collection->toArray();

    expect($array)->toHaveKey('data')
        ->and($array)->toHaveKey('limit')
        ->and($array['data'])->toHaveCount(1)
        ->and($array['limit'])->toBe(20);
});

test('PaginatedCollection with maximum limit', function () {
    $meta = ['paging' => ['limit' => 100]];
    $collection = PaginatedCollection::fromArray([], $meta, []);

    expect($collection->limit)->toBe(100);
});

test('PaginatedCollection with minimum limit', function () {
    $meta = ['paging' => ['limit' => 1]];
    $collection = PaginatedCollection::fromArray([], $meta, []);

    expect($collection->limit)->toBe(1);
});

test('PaginatedCollection without cursors', function () {
    $meta = ['paging' => ['limit' => 20]];
    $collection = PaginatedCollection::fromArray([], $meta, []);

    expect($collection->beforeCursor)->toBeNull()
        ->and($collection->afterCursor)->toBeNull();
});

test('PaginatedCollection with both cursors', function () {
    $meta = [
        'paging' => [
            'limit' => 20,
            'before' => 'before-cursor',
            'after' => 'after-cursor',
        ],
    ];

    $collection = PaginatedCollection::fromArray([], $meta, []);

    expect($collection->beforeCursor)->toBe('before-cursor')
        ->and($collection->afterCursor)->toBe('after-cursor');
});

test('PaginatedCollection readonly properties cannot be modified', function () {
    $collection = PaginatedCollection::fromArray([], ['paging' => ['limit' => 20]], []);

    expect(fn () => $collection->limit = 50)
        ->toThrow(\Error::class);
});

test('PaginatedCollection data is readonly array', function () {
    $data = [mockAccountData()];
    $collection = PaginatedCollection::fromArray($data, ['paging' => []], []);

    expect($collection->data)->toBeArray()
        ->and(fn () => $collection->data = [])
        ->toThrow(\Error::class);
});

test('PaginatedCollection handles large datasets', function () {
    $data = array_map(fn () => mockAccountData(), range(1, 100));
    $collection = PaginatedCollection::fromArray($data, ['paging' => ['limit' => 100]], []);

    expect($collection->count())->toBe(100)
        ->and($collection->isEmpty())->toBeFalse();
});
