<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Utils;

use AlchemicStudio\Ponto\Exceptions\ValidationException;

class Validator
{
    /**
     * Validate and normalize IBAN
     *
     * @throws ValidationException
     */
    public static function validateIban(string $iban): string
    {
        // Remove spaces and convert to uppercase
        $normalized = strtoupper(str_replace(' ', '', $iban));

        // Check if empty
        if ($normalized === '') {
            throw new ValidationException('Invalid IBAN format');
        }

        // Check format: 2 letters + 2 digits + alphanumeric
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $normalized)) {
            throw new ValidationException('Invalid IBAN format');
        }

        // Check minimum length
        if (strlen($normalized) < 15) {
            throw new ValidationException('Invalid IBAN format');
        }

        return $normalized;
    }

    /**
     * Validate and normalize BIC
     *
     * @throws ValidationException
     */
    public static function validateBic(string $bic): string
    {
        // Remove spaces and convert to uppercase
        $normalized = strtoupper(str_replace(' ', '', $bic));

        // Check if empty
        if ($normalized === '') {
            throw new ValidationException('Invalid BIC format');
        }

        // Check format: 6 letters + 2 alphanumeric + optional 3 alphanumeric
        if (!preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $normalized)) {
            throw new ValidationException('Invalid BIC format');
        }

        return $normalized;
    }

    /**
     * Validate payment amount
     *
     * @throws ValidationException
     */
    public static function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new ValidationException('Amount must be positive');
        }

        if ($amount > 999999999.99) {
            throw new ValidationException('Amount too large');
        }
    }

    /**
     * Validate and normalize currency code
     *
     * @throws ValidationException
     */
    public static function validateCurrency(string $currency): string
    {
        // Convert to uppercase
        $normalized = strtoupper($currency);

        // Check if empty
        if ($normalized === '') {
            throw new ValidationException('Invalid currency code');
        }

        // Check format: exactly 3 letters
        if (!preg_match('/^[A-Z]{3}$/', $normalized)) {
            throw new ValidationException('Invalid currency code');
        }

        return $normalized;
    }

    /**
     * Validate remittance information
     *
     * @throws ValidationException
     */
    public static function validateRemittanceInfo(string $info): string
    {
        // Empty is allowed
        if ($info === '') {
            return $info;
        }

        // Check maximum length
        if (strlen($info) > 140) {
            throw new ValidationException('Remittance information too long');
        }

        // Check for allowed characters: alphanumeric, space, +, -, ., ,, /, :, (, ), ?, '
        if (!preg_match('/^[a-zA-Z0-9 +\-.,\/:()?\'\+]+$/', $info)) {
            throw new ValidationException('Remittance information contains invalid characters');
        }

        return $info;
    }
}
