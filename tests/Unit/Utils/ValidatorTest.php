<?php

declare(strict_types=1);

use AlchemicStudio\Ponto\Utils\Validator;
use AlchemicStudio\Ponto\Exceptions\ValidationException;

// IBAN Validation Tests

test('validates valid Belgian IBAN', function () {
    $iban = Validator::validateIban('BE68 5390 0754 7034');

    expect($iban)->toBe('BE68539007547034');
});

test('validates valid French IBAN', function () {
    $iban = Validator::validateIban('FR14 2004 1010 0505 0001 3M02 606');

    expect($iban)->toBe('FR1420041010050500013M02606');
});

test('validates valid German IBAN', function () {
    $iban = Validator::validateIban('DE89 3704 0044 0532 0130 00');

    expect($iban)->toBe('DE89370400440532013000');
});

test('validates IBAN without spaces', function () {
    $iban = Validator::validateIban('BE68539007547034');

    expect($iban)->toBe('BE68539007547034');
});

test('validates IBAN converts to uppercase', function () {
    $iban = Validator::validateIban('be68539007547034');

    expect($iban)->toBe('BE68539007547034');
});

test('rejects invalid IBAN format', function () {
    Validator::validateIban('INVALID');
})->throws(ValidationException::class, 'Invalid IBAN format');

test('rejects IBAN without country code', function () {
    Validator::validateIban('68539007547034');
})->throws(ValidationException::class);

test('rejects IBAN with invalid country code', function () {
    Validator::validateIban('XX68539007547034');
})->throws(ValidationException::class);

test('rejects IBAN with special characters', function () {
    Validator::validateIban('BE68-5390-0754-7034');
})->throws(ValidationException::class);

test('rejects empty IBAN', function () {
    Validator::validateIban('');
})->throws(ValidationException::class);

test('rejects IBAN with only spaces', function () {
    Validator::validateIban('   ');
})->throws(ValidationException::class);

// BIC Validation Tests

test('validates valid 8-character BIC', function () {
    $bic = Validator::validateBic('NBBEBEBB');

    expect($bic)->toBe('NBBEBEBB');
});

test('validates valid 11-character BIC', function () {
    $bic = Validator::validateBic('NBBEBEBB203');

    expect($bic)->toBe('NBBEBEBB203');
});

test('validates BIC with spaces', function () {
    $bic = Validator::validateBic('NBBEEBB 203');

    expect($bic)->toBe('NBBEBEBB203');
});

test('validates BIC converts to uppercase', function () {
    $bic = Validator::validateBic('nbbebebb203');

    expect($bic)->toBe('NBBEBEBB203');
});

test('rejects invalid BIC format', function () {
    Validator::validateBic('INVALID');
})->throws(ValidationException::class, 'Invalid BIC format');

test('rejects BIC with wrong length', function () {
    Validator::validateBic('NBB');
})->throws(ValidationException::class);

test('rejects BIC with numbers in wrong position', function () {
    Validator::validateBic('1BBEBEBB');
})->throws(ValidationException::class);

test('rejects empty BIC', function () {
    Validator::validateBic('');
})->throws(ValidationException::class);

test('rejects BIC with special characters', function () {
    Validator::validateBic('NBBE-BEBB');
})->throws(ValidationException::class);

// Amount Validation Tests

test('validates positive amount', function () {
    expect(fn() => Validator::validateAmount(100.50))->not->toThrow(ValidationException::class);
});

test('validates small positive amount', function () {
    expect(fn() => Validator::validateAmount(0.01))->not->toThrow(ValidationException::class);
});

test('validates large amount', function () {
    expect(fn() => Validator::validateAmount(999999.99))->not->toThrow(ValidationException::class);
});

test('validates integer amount', function () {
    expect(fn() => Validator::validateAmount(100.0))->not->toThrow(ValidationException::class);
});

test('rejects negative amount', function () {
    Validator::validateAmount(-50.00);
})->throws(ValidationException::class, 'Amount must be positive');

test('rejects zero amount', function () {
    Validator::validateAmount(0.0);
})->throws(ValidationException::class, 'Amount must be positive');

test('rejects extremely large amount', function () {
    Validator::validateAmount(1000000000.00);
})->throws(ValidationException::class, 'Amount too large');

test('rejects amount exceeding maximum', function () {
    Validator::validateAmount(999999999.99 + 0.01);
})->throws(ValidationException::class, 'Amount too large');

test('validates maximum allowed amount', function () {
    expect(fn() => Validator::validateAmount(999999999.99))->not->toThrow(ValidationException::class);
});

test('validates minimum allowed amount', function () {
    expect(fn() => Validator::validateAmount(0.01))->not->toThrow(ValidationException::class);
});

// Currency Validation Tests

test('validates EUR currency', function () {
    $currency = Validator::validateCurrency('EUR');

    expect($currency)->toBe('EUR');
});

test('validates USD currency', function () {
    $currency = Validator::validateCurrency('USD');

    expect($currency)->toBe('USD');
});

test('validates GBP currency', function () {
    $currency = Validator::validateCurrency('GBP');

    expect($currency)->toBe('GBP');
});

test('validates currency converts to uppercase', function () {
    $currency = Validator::validateCurrency('eur');

    expect($currency)->toBe('EUR');
});

test('validates various ISO 4217 currencies', function () {
    $currencies = ['CHF', 'JPY', 'CAD', 'AUD', 'SEK', 'NOK', 'DKK'];

    foreach ($currencies as $code) {
        $validated = Validator::validateCurrency($code);
        expect($validated)->toBe($code);
    }
});

test('rejects invalid currency code', function () {
    Validator::validateCurrency('INVALID');
})->throws(ValidationException::class, 'Invalid currency code');

test('rejects two-letter currency', function () {
    Validator::validateCurrency('EU');
})->throws(ValidationException::class);

test('rejects four-letter currency', function () {
    Validator::validateCurrency('EURO');
})->throws(ValidationException::class);

test('rejects currency with numbers', function () {
    Validator::validateCurrency('E1R');
})->throws(ValidationException::class);

test('rejects empty currency', function () {
    Validator::validateCurrency('');
})->throws(ValidationException::class);

test('rejects currency with special characters', function () {
    Validator::validateCurrency('E$R');
})->throws(ValidationException::class);

// Remittance Information Validation Tests

test('validates simple remittance information', function () {
    $info = Validator::validateRemittanceInfo('Invoice 2024-001');

    expect($info)->toBe('Invoice 2024-001');
});

test('validates remittance with allowed special characters', function () {
    $info = Validator::validateRemittanceInfo('Payment/Ref:123-456.789,ABC+');

    expect($info)->toBe('Payment/Ref:123-456.789,ABC+');
});

test('validates remittance with spaces', function () {
    $info = Validator::validateRemittanceInfo('Monthly subscription fee');

    expect($info)->toBe('Monthly subscription fee');
});

test('validates remittance with parentheses', function () {
    $info = Validator::validateRemittanceInfo('Payment (Invoice 123)');

    expect($info)->toBe('Payment (Invoice 123)');
});

test('validates remittance with question mark', function () {
    $info = Validator::validateRemittanceInfo('Payment?');

    expect($info)->toBe('Payment?');
});

test('validates remittance with apostrophe', function () {
    $info = Validator::validateRemittanceInfo("Customer's payment");

    expect($info)->toBe("Customer's payment");
});

test('validates maximum length remittance', function () {
    $info = str_repeat('A', 140);

    expect(fn() => Validator::validateRemittanceInfo($info))->not->toThrow(ValidationException::class);
});

test('rejects remittance exceeding max length', function () {
    $info = str_repeat('A', 141);

    Validator::validateRemittanceInfo($info);
})->throws(ValidationException::class, 'Remittance information too long');

test('rejects remittance with invalid characters', function () {
    Validator::validateRemittanceInfo('Payment@email.com');
})->throws(ValidationException::class, 'invalid characters');

test('rejects remittance with special symbols', function () {
    Validator::validateRemittanceInfo('Payment #123');
})->throws(ValidationException::class);

test('rejects remittance with underscore', function () {
    Validator::validateRemittanceInfo('Payment_123');
})->throws(ValidationException::class);

test('rejects remittance with equals sign', function () {
    Validator::validateRemittanceInfo('Amount=100');
})->throws(ValidationException::class);

test('rejects remittance with ampersand', function () {
    Validator::validateRemittanceInfo('Smith & Co');
})->throws(ValidationException::class);

test('validates empty remittance information', function () {
    $info = Validator::validateRemittanceInfo('');

    expect($info)->toBe('');
});

test('validates structured remittance format', function () {
    $info = Validator::validateRemittanceInfo('+++123/4567/89012+++');

    expect($info)->toBe('+++123/4567/89012+++');
});

test('validates numeric remittance', function () {
    $info = Validator::validateRemittanceInfo('1234567890');

    expect($info)->toBe('1234567890');
});

test('validates remittance with mixed case', function () {
    $info = Validator::validateRemittanceInfo('Payment Invoice ABC-123');

    expect($info)->toBe('Payment Invoice ABC-123');
});
