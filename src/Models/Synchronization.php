<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Models;

use DateMalformedStringException;
use DateTimeImmutable;

readonly class Synchronization
{
    public function __construct(
        public string            $id,
        public string            $type,
        public string            $status,
        public string            $resourceType,
        public string            $resourceId,
        public string            $subtype,
        public array             $errors,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @throws DateMalformedStringException
     */
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
