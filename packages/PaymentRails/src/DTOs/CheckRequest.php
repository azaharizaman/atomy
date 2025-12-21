<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;

/**
 * Request DTO for issuing a check.
 */
final readonly class CheckRequest
{
    /**
     * @param Money $amount Check amount
     * @param string $payeeName Payee name (pay to the order of)
     * @param string|null $payeeAddressLine1 Payee address line 1
     * @param string|null $payeeAddressLine2 Payee address line 2
     * @param string|null $payeeCity Payee city
     * @param string|null $payeeState Payee state/province
     * @param string|null $payeePostalCode Payee postal code
     * @param string|null $payeeCountry Payee country
     * @param string $memo Check memo/reference
     * @param \DateTimeImmutable|null $checkDate Date on the check (defaults to today)
     * @param string|null $bankAccountId Bank account to draw from
     * @param string|null $invoiceReference Related invoice reference
     * @param bool $shouldMail Whether to mail the check
     * @param string|null $deliveryMethod Delivery method (mail, print, etc.)
     * @param array<string, string> $metadata Additional metadata
     */
    public function __construct(
        public Money $amount,
        public string $payeeName,
        public ?string $payeeAddressLine1 = null,
        public ?string $payeeAddressLine2 = null,
        public ?string $payeeCity = null,
        public ?string $payeeState = null,
        public ?string $payeePostalCode = null,
        public ?string $payeeCountry = null,
        public string $memo = '',
        public ?\DateTimeImmutable $checkDate = null,
        public ?string $bankAccountId = null,
        public ?string $invoiceReference = null,
        public bool $shouldMail = true,
        public ?string $deliveryMethod = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a check request with full address.
     */
    public static function create(
        Money $amount,
        string $payeeName,
        ?string $addressLine1 = null,
        ?string $city = null,
        ?string $state = null,
        ?string $postalCode = null,
        string $memo = '',
    ): self {
        return new self(
            amount: $amount,
            payeeName: $payeeName,
            payeeAddressLine1: $addressLine1,
            payeeCity: $city,
            payeeState: $state,
            payeePostalCode: $postalCode,
            memo: $memo,
        );
    }

    /**
     * Get the formatted address for mailing.
     */
    public function getFormattedAddress(): string
    {
        $parts = array_filter([
            $this->payeeAddressLine1,
            $this->payeeAddressLine2,
            implode(', ', array_filter([
                $this->payeeCity,
                $this->payeeState,
                $this->payeePostalCode,
            ])),
            $this->payeeCountry,
        ]);

        return implode("\n", $parts);
    }

    /**
     * Check if a mailing address is provided.
     */
    public function hasMailingAddress(): bool
    {
        return $this->payeeAddressLine1 !== null
            && $this->payeeCity !== null
            && $this->payeeState !== null
            && $this->payeePostalCode !== null;
    }

    /**
     * Validate the check request.
     *
     * @return array<string> Validation errors
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->amount->isZero()) {
            $errors[] = 'Amount must be greater than zero';
        }

        if ($this->amount->isNegative()) {
            $errors[] = 'Amount cannot be negative';
        }

        if (empty($this->payeeName)) {
            $errors[] = 'Payee name is required';
        }

        if (mb_strlen($this->payeeName) > 100) {
            $errors[] = 'Payee name must not exceed 100 characters';
        }

        if ($this->shouldMail && !$this->hasMailingAddress()) {
            $errors[] = 'Complete mailing address is required when mailing check';
        }

        if (mb_strlen($this->memo) > 100) {
            $errors[] = 'Memo must not exceed 100 characters';
        }

        return $errors;
    }
}
