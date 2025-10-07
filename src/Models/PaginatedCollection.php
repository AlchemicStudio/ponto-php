<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Models;

class PaginatedCollection
{
    public function __construct(
        public readonly array $data,
        public readonly ?int $limit,
        public readonly ?string $beforeCursor,
        public readonly ?string $afterCursor,
        public readonly ?string $firstUrl,
        public readonly ?string $prevUrl,
        public readonly ?string $nextUrl,
    ) {
    }

    public static function fromArray(array $data, array $meta, array $links): self
    {
        $paging = $meta['paging'] ?? [];

        return new self(
            data: $data,
            limit: $paging['limit'] ?? null,
            beforeCursor: $paging['before'] ?? null,
            afterCursor: $paging['after'] ?? null,
            firstUrl: $links['first'] ?? null,
            prevUrl: $links['prev'] ?? null,
            nextUrl: $links['next'] ?? null,
        );
    }

    public function hasNextPage(): bool
    {
        return $this->nextUrl !== null;
    }

    public function hasPrevPage(): bool
    {
        return $this->prevUrl !== null;
    }

    public function isEmpty(): bool
    {
        return count($this->data) === 0;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'limit' => $this->limit,
            'beforeCursor' => $this->beforeCursor,
            'afterCursor' => $this->afterCursor,
            'firstUrl' => $this->firstUrl,
            'prevUrl' => $this->prevUrl,
            'nextUrl' => $this->nextUrl,
        ];
    }
}
