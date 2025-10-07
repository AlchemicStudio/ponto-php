<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Models\Payment;

test('can create Payment from array', function () {
    $data = mockPaymentData();
    $payment = Payment::fromArray($data);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->id)->toBeString()
        ->and($payment->type)->toBe('payment');
});

test('Payment has all required properties', function () {
    $data = mockPaymentData([
        'id' => 'pay-123',
        'attributes' => [
            'status' => 'unsigned',
            'amount' => 100.00,
            'currency' => 'EUR',
            'creditorName' => 'John Doe',
            'creditorAccountReference' => 'BE68539007547034',
            'creditorAccountReferenceType' => 'IBAN',
            'creditorAgent' => 'NBBEBEBB203',
            'creditorAgentType' => 'BIC',
            'remittanceInformation' => 'Invoice 123',
            'remittanceInformationType' => 'unstructured',
        ],
    ]);

    $payment = Payment::fromArray($data);

    expect($payment->id)->toBe('pay-123')
        ->and($payment->status)->toBe('unsigned')
        ->and($payment->amount)->toBe(100.00)
        ->and($payment->currency)->toBe('EUR')
        ->and($payment->creditorName)->toBe('John Doe')
        ->and($payment->creditorAccountReference)->toBe('BE68539007547034')
        ->and($payment->creditorAgent)->toBe('NBBEBEBB203');
});

test('Payment isUnsigned returns true for unsigned status', function () {
    $payment = Payment::fromArray(mockPaymentData(['attributes' => ['status' => 'unsigned']]));

    expect($payment->isUnsigned())->toBeTrue()
        ->and($payment->isAuthorized())->toBeFalse()
        ->and($payment->isExecuted())->toBeFalse();
});

test('Payment isAuthorized returns true for authorized status', function () {
    $payment = Payment::fromArray(mockPaymentData(['attributes' => ['status' => 'authorized']]));

    expect($payment->isAuthorized())->toBeTrue()
        ->and($payment->isUnsigned())->toBeFalse()
        ->and($payment->isExecuted())->toBeFalse();
});

test('Payment isExecuted returns true for executed status', function () {
    $payment = Payment::fromArray(mockPaymentData(['attributes' => ['status' => 'executed']]));

    expect($payment->isExecuted())->toBeTrue()
        ->and($payment->isUnsigned())->toBeFalse()
        ->and($payment->isAuthorized())->toBeFalse();
});

test('Payment isCancelled returns true for cancelled status', function () {
    $payment = Payment::fromArray(mockPaymentData(['attributes' => ['status' => 'cancelled']]));

    expect($payment->isCancelled())->toBeTrue()
        ->and($payment->isExecuted())->toBeFalse();
});

test('Payment isRejected returns true for rejected status', function () {
    $payment = Payment::fromArray(mockPaymentData(['attributes' => ['status' => 'rejected']]));

    expect($payment->isRejected())->toBeTrue()
        ->and($payment->isExecuted())->toBeFalse();
});

test('Payment requiresAuthorization for unsigned payment', function () {
    $payment = Payment::fromArray(mockPaymentData(['attributes' => ['status' => 'unsigned']]));

    expect($payment->requiresAuthorization())->toBeTrue();
});

test('Payment requiresAuthorization false for executed payment', function () {
    $payment = Payment::fromArray(mockPaymentData(['attributes' => ['status' => 'executed']]));

    expect($payment->requiresAuthorization())->toBeFalse();
});

test('Payment with redirect URL', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'links' => ['redirect' => 'https://authorize.myponto.com/payment/pay-123'],
    ]));

    expect($payment->redirectUrl)->toBe('https://authorize.myponto.com/payment/pay-123');
});

test('Payment with null redirect URL', function () {
    $payment = Payment::fromArray(mockPaymentData(['links' => []]));

    expect($payment->redirectUrl)->toBeNull();
});

test('Payment with endToEndId for idempotency', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => ['endToEndId' => 'unique-payment-id-123'],
    ]));

    expect($payment->endToEndId)->toBe('unique-payment-id-123');
});

test('Payment with null endToEndId', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => ['endToEndId' => null],
    ]));

    expect($payment->endToEndId)->toBeNull();
});

test('Payment with requested execution date', function () {
    $futureDate = new DateTimeImmutable('+7 days');
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => ['requestedExecutionDate' => $futureDate->format('Y-m-d\TH:i:s\Z')],
    ]));

    expect($payment->requestedExecutionDate)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($payment->requestedExecutionDate->format('Y-m-d'))->toBe($futureDate->format('Y-m-d'));
});

test('Payment with null requested execution date', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => ['requestedExecutionDate' => null],
    ]));

    expect($payment->requestedExecutionDate)->toBeNull();
});

test('Payment with structured remittance', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => [
            'remittanceInformation' => '+++123/4567/89012+++',
            'remittanceInformationType' => 'structured',
        ],
    ]));

    expect($payment->remittanceInformationType)->toBe('structured')
        ->and($payment->remittanceInformation)->toBe('+++123/4567/89012+++');
});

test('Payment with unstructured remittance', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => [
            'remittanceInformation' => 'Invoice 2024-001',
            'remittanceInformationType' => 'unstructured',
        ],
    ]));

    expect($payment->remittanceInformationType)->toBe('unstructured');
});

test('Payment creditor IBAN is valid', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => ['creditorAccountReference' => 'BE68539007547034'],
    ]));

    expect($payment->creditorAccountReference)->toBeValidIban();
});

test('Payment creditor BIC is valid', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => ['creditorAgent' => 'NBBEBEBB203'],
    ]));

    expect($payment->creditorAgent)->toBeValidBic();
});

test('Payment currency is valid ISO 4217', function () {
    $payment = Payment::fromArray(mockPaymentData());

    expect($payment->currency)->toBeValidIso4217Currency();
});

test('Payment toArray returns correct structure', function () {
    $payment = Payment::fromArray(mockPaymentData(['id' => 'pay-test']));
    $array = $payment->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKey('id', 'pay-test')
        ->and($array)->toHaveKey('status')
        ->and($array)->toHaveKey('amount')
        ->and($array)->toHaveKey('currency')
        ->and($array)->toHaveKey('creditorName');
});

test('Payment readonly properties cannot be modified', function () {
    $payment = Payment::fromArray(mockPaymentData());

    expect(fn () => $payment->amount = 999.99)
        ->toThrow(\Error::class);
});

test('Payment with different currencies', function () {
    $eur = Payment::fromArray(mockPaymentData(['attributes' => ['currency' => 'EUR']]));
    $usd = Payment::fromArray(mockPaymentData(['attributes' => ['currency' => 'USD']]));
    $gbp = Payment::fromArray(mockPaymentData(['attributes' => ['currency' => 'GBP']]));

    expect($eur->currency)->toBe('EUR')
        ->and($usd->currency)->toBe('USD')
        ->and($gbp->currency)->toBe('GBP');
});

test('Payment with large amount', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => ['amount' => 50000.00],
    ]));

    expect($payment->amount)->toBe(50000.00);
});

test('Payment with small amount', function () {
    $payment = Payment::fromArray(mockPaymentData([
        'attributes' => ['amount' => 0.01],
    ]));

    expect($payment->amount)->toBe(0.01);
});

test('Payment all status states are mutually exclusive', function () {
    $statuses = ['unsigned', 'authorized', 'executed', 'cancelled', 'rejected'];

    foreach ($statuses as $status) {
        $payment = Payment::fromArray(mockPaymentData(['attributes' => ['status' => $status]]));

        $checks = [
            'unsigned' => $payment->isUnsigned(),
            'authorized' => $payment->isAuthorized(),
            'executed' => $payment->isExecuted(),
            'cancelled' => $payment->isCancelled(),
            'rejected' => $payment->isRejected(),
        ];

        // Only the current status should be true
        foreach ($checks as $checkStatus => $result) {
            if ($checkStatus === $status) {
                expect($result)->toBeTrue("Expected {$status} to be true");
            } else {
                expect($result)->toBeFalse("Expected {$checkStatus} to be false when status is {$status}");
            }
        }
    }
});
