<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Models;

use DateTimeImmutable;

class Synchronization
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $status,
        public readonly string $resourceType,
        public readonly string $resourceId,
        public readonly string $subtype,
        public readonly array $errors,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];

        return new self(
            id: $data['id'],
            type: $data['type'],
            status: $attributes['status'],
            resourceType: $attributes['resourceType'],
            resourceId: $attributes['resourceId'],
            subtype: $attributes['subtype'],
            errors: $attributes['errors'] ?? [],
            createdAt: new DateTimeImmutable($attributes['createdAt']),
            updatedAt: new DateTimeImmutable($attributes['updatedAt']),
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function hasErrors(): bool
    {
        return $this->status === 'error';
    }

    public function isComplete(): bool
    {
        return $this->status === 'success' || $this->status === 'error';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'resourceType' => $this->resourceType,
            'resourceId' => $this->resourceId,
            'subtype' => $this->subtype,
            'errors' => $this->errors,
            'createdAt' => $this->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $this->updatedAt->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
