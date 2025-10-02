<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Models;

use DateTimeImmutable;

class Account
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $reference,
        public readonly string $referenceType,
        public readonly string $currency,
        public readonly string $subtype,
        public readonly float $availableBalance,
        public readonly float $currentBalance,
        public readonly string $holderName,
        public readonly string $product,
        public readonly ?string $description,
        public readonly bool $deprecated,
        public readonly DateTimeImmutable $availableBalanceChangedAt,
        public readonly DateTimeImmutable $currentBalanceChangedAt,
        public readonly DateTimeImmutable $authorizedAt,
        public readonly ?DateTimeImmutable $authorizationExpirationExpectedAt,
        public readonly ?string $internalReference,
        public readonly array $relationships,
        public readonly array $meta,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];
        $meta = $data['meta'] ?? [];

        return new self(
            id: $data['id'],
            type: $data['type'],
            reference: $attributes['reference'],
            referenceType: $attributes['referenceType'],
            currency: $attributes['currency'],
            subtype: $attributes['subtype'],
            availableBalance: $attributes['availableBalance'],
            currentBalance: $attributes['currentBalance'],
            holderName: $attributes['holderName'],
            product: $attributes['product'],
            description: $attributes['description'] ?? null,
            deprecated: $attributes['deprecated'] ?? false,
            availableBalanceChangedAt: new DateTimeImmutable($attributes['availableBalanceChangedAt']),
            currentBalanceChangedAt: new DateTimeImmutable($attributes['currentBalanceChangedAt']),
            authorizedAt: new DateTimeImmutable($attributes['authorizedAt']),
            authorizationExpirationExpectedAt: isset($attributes['authorizationExpirationExpectedAt'])
                ? new DateTimeImmutable($attributes['authorizationExpirationExpectedAt'])
                : null,
            internalReference: $attributes['internalReference'] ?? null,
            relationships: $relationships,
            meta: $meta,
        );
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function needsReauthorization(): bool
    {
        if ($this->authorizationExpirationExpectedAt === null) {
            return false;
        }

        $now = new DateTimeImmutable();
        $daysUntilExpiration = $now->diff($this->authorizationExpirationExpectedAt)->days;

        return $daysUntilExpiration !== false && $daysUntilExpiration < 30;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'reference' => $this->reference,
            'referenceType' => $this->referenceType,
            'currency' => $this->currency,
            'subtype' => $this->subtype,
            'availableBalance' => $this->availableBalance,
            'currentBalance' => $this->currentBalance,
            'holderName' => $this->holderName,
            'product' => $this->product,
            'description' => $this->description,
            'deprecated' => $this->deprecated,
            'availableBalanceChangedAt' => $this->availableBalanceChangedAt->format('Y-m-d\TH:i:s\Z'),
            'currentBalanceChangedAt' => $this->currentBalanceChangedAt->format('Y-m-d\TH:i:s\Z'),
            'authorizedAt' => $this->authorizedAt->format('Y-m-d\TH:i:s\Z'),
            'authorizationExpirationExpectedAt' => $this->authorizationExpirationExpectedAt?->format('Y-m-d\TH:i:s\Z'),
            'internalReference' => $this->internalReference,
            'relationships' => $this->relationships,
            'meta' => $this->meta,
        ];
    }
}
