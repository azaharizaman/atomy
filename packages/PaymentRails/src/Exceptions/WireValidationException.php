<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

use Nexus\PaymentRails\Enums\WireType;

/**
 * Exception thrown when wire transfer validation fails.
 */
final class WireValidationException extends PaymentRailException
{
    /**
     * @param string $message
     * @param array<string> $errors
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            railType: 'WIRE',
            context: ['errors' => $this->errors],
            previous: $previous,
        );
    }

    /**
     * Get the validation errors.
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create for multiple validation errors.
     *
     * @param array<string> $errors
     */
    public static function multipleErrors(array $errors): self
    {
        $count = count($errors);
        return new self(
            message: "Wire transfer validation failed with {$count} error(s)",
            errors: $errors,
        );
    }

    /**
     * Create for missing beneficiary information.
     */
    public static function missingBeneficiary(string $missingField): self
    {
        return new self(
            message: "Missing beneficiary information: {$missingField}",
            errors: [$missingField],
        );
    }

    /**
     * Create for invalid currency for wire type.
     */
    public static function invalidCurrency(string $currency, WireType $wireType): self
    {
        return new self(
            message: "Currency '{$currency}' is not supported for {$wireType->value} wire transfers",
            errors: ["Currency: {$currency}", "Wire type: {$wireType->value}"],
        );
    }

    /**
     * Create for amount below minimum.
     */
    public static function amountBelowMinimum(int $amountCents, int $minimumCents): self
    {
        $amount = number_format($amountCents / 100, 2);
        $minimum = number_format($minimumCents / 100, 2);
        return new self(
            message: "Wire amount {$amount} is below minimum of {$minimum}",
            errors: ["Amount: {$amount}", "Minimum: {$minimum}"],
        );
    }

    /**
     * Create for amount above maximum.
     */
    public static function amountAboveMaximum(int $amountCents, int $maximumCents): self
    {
        $amount = number_format($amountCents / 100, 2);
        $maximum = number_format($maximumCents / 100, 2);
        return new self(
            message: "Wire amount {$amount} exceeds maximum of {$maximum}",
            errors: ["Amount: {$amount}", "Maximum: {$maximum}"],
        );
    }

    /**
     * Create for missing intermediary bank.
     */
    public static function missingIntermediaryBank(string $reason): self
    {
        return new self(
            message: "Intermediary bank required: {$reason}",
            errors: [$reason],
        );
    }

    /**
     * Create for sanctioned country.
     */
    public static function sanctionedCountry(string $countryCode): self
    {
        return new self(
            message: "Wire transfers to country '{$countryCode}' are prohibited due to sanctions",
            errors: ["Country: {$countryCode}"],
        );
    }

    /**
     * Create for insufficient balance.
     */
    public static function insufficientBalance(int $requiredCents, int $availableCents): self
    {
        $required = number_format($requiredCents / 100, 2);
        $available = number_format($availableCents / 100, 2);
        return new self(
            message: "Insufficient balance for wire transfer: need {$required}, available {$available}",
            errors: ["Required: {$required}", "Available: {$available}"],
        );
    }

    /**
     * Create for missing purpose/reference.
     */
    public static function missingPurpose(): self
    {
        return new self(
            message: 'Wire transfer purpose/reference is required for international transfers',
            errors: ['Missing: purpose'],
        );
    }
}
