<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Models;

use DateTimeImmutable;

class Payment
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $status,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $creditorName,
        public readonly string $creditorAccountReference,
        public readonly string $creditorAccountReferenceType,
        public readonly string $creditorAgent,
        public readonly string $creditorAgentType,
        public readonly string $remittanceInformation,
        public readonly string $remittanceInformationType,
        public readonly ?string $endToEndId,
        public readonly ?DateTimeImmutable $requestedExecutionDate,
        public readonly ?string $redirectUrl,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? [];
        $links = $data['links'] ?? [];

        return new self(
            id: $data['id'],
            type: $data['type'],
            status: $attributes['status'],
            amount: $attributes['amount'],
            currency: $attributes['currency'],
            creditorName: $attributes['creditorName'],
            creditorAccountReference: $attributes['creditorAccountReference'],
            creditorAccountReferenceType: $attributes['creditorAccountReferenceType'],
            creditorAgent: $attributes['creditorAgent'],
            creditorAgentType: $attributes['creditorAgentType'],
            remittanceInformation: $attributes['remittanceInformation'],
            remittanceInformationType: $attributes['remittanceInformationType'],
            endToEndId: $attributes['endToEndId'] ?? null,
            requestedExecutionDate: isset($attributes['requestedExecutionDate'])
                ? new DateTimeImmutable($attributes['requestedExecutionDate'])
                : null,
            redirectUrl: $links['redirect'] ?? null,
        );
    }

    public function isUnsigned(): bool
    {
        return $this->status === 'unsigned';
    }

    public function isAuthorized(): bool
    {
        return $this->status === 'authorized';
    }

    public function isExecuted(): bool
    {
        return $this->status === 'executed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function requiresAuthorization(): bool
    {
        return $this->isUnsigned() || $this->isAuthorized();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'creditorName' => $this->creditorName,
            'creditorAccountReference' => $this->creditorAccountReference,
            'creditorAccountReferenceType' => $this->creditorAccountReferenceType,
            'creditorAgent' => $this->creditorAgent,
            'creditorAgentType' => $this->creditorAgentType,
            'remittanceInformation' => $this->remittanceInformation,
            'remittanceInformationType' => $this->remittanceInformationType,
            'endToEndId' => $this->endToEndId,
            'requestedExecutionDate' => $this->requestedExecutionDate?->format('Y-m-d\TH:i:s\Z'),
            'redirectUrl' => $this->redirectUrl,
        ];
    }
}
