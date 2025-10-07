<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Models;

use DateTimeImmutable;

class Transaction
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $description,
        public readonly string $digest,
        public readonly ?DateTimeImmutable $executionDate,
        public readonly ?DateTimeImmutable $valueDate,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
        public readonly ?string $counterpartName,
        public readonly ?string $counterpartReference,
        public readonly ?string $remittanceInformation,
        public readonly ?string $remittanceInformationType,
        public readonly ?string $internalReference,
        public readonly ?string $endToEndId,
        public readonly ?string $mandateId,
        public readonly ?string $creditorId,
        public readonly ?string $bankTransactionCode,
        public readonly ?string $purposeCode,
        public readonly ?float $fee,
        public readonly ?string $additionalInformation,
        public readonly ?string $accountId,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];

        $accountId = null;
        if (isset($relationships['account']['data']['id'])) {
            $accountId = $relationships['account']['data']['id'];
        }

        return new self(
            id: $data['id'],
            type: $data['type'],
            amount: $attributes['amount'],
            currency: $attributes['currency'],
            description: $attributes['description'],
            digest: $attributes['digest'],
            executionDate: isset($attributes['executionDate'])
                ? new DateTimeImmutable($attributes['executionDate'])
                : null,
            valueDate: isset($attributes['valueDate'])
                ? new DateTimeImmutable($attributes['valueDate'])
                : null,
            createdAt: new DateTimeImmutable($attributes['createdAt']),
            updatedAt: new DateTimeImmutable($attributes['updatedAt']),
            counterpartName: $attributes['counterpartName'] ?? null,
            counterpartReference: $attributes['counterpartReference'] ?? null,
            remittanceInformation: $attributes['remittanceInformation'] ?? null,
            remittanceInformationType: $attributes['remittanceInformationType'] ?? null,
            internalReference: $attributes['internalReference'] ?? null,
            endToEndId: $attributes['endToEndId'] ?? null,
            mandateId: $attributes['mandateId'] ?? null,
            creditorId: $attributes['creditorId'] ?? null,
            bankTransactionCode: $attributes['bankTransactionCode'] ?? null,
            purposeCode: $attributes['purposeCode'] ?? null,
            fee: $attributes['fee'] ?? null,
            additionalInformation: $attributes['additionalInformation'] ?? null,
            accountId: $accountId,
        );
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    public function getAbsoluteAmount(): float
    {
        return abs($this->amount);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'digest' => $this->digest,
            'executionDate' => $this->executionDate?->format('Y-m-d\TH:i:s\Z'),
            'valueDate' => $this->valueDate?->format('Y-m-d\TH:i:s\Z'),
            'createdAt' => $this->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $this->updatedAt->format('Y-m-d\TH:i:s\Z'),
            'counterpartName' => $this->counterpartName,
            'counterpartReference' => $this->counterpartReference,
            'remittanceInformation' => $this->remittanceInformation,
            'remittanceInformationType' => $this->remittanceInformationType,
            'internalReference' => $this->internalReference,
            'endToEndId' => $this->endToEndId,
            'mandateId' => $this->mandateId,
            'creditorId' => $this->creditorId,
            'bankTransactionCode' => $this->bankTransactionCode,
            'purposeCode' => $this->purposeCode,
            'fee' => $this->fee,
            'additionalInformation' => $this->additionalInformation,
            'accountId' => $this->accountId,
        ];
    }
}
