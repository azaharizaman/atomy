<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\WireType;
use Nexus\PaymentRails\ValueObjects\Iban;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use Nexus\PaymentRails\ValueObjects\SwiftCode;

/**
 * Request DTO for initiating a wire transfer.
 */
final readonly class WireTransferRequest
{
    /**
     * @param Money $amount Transfer amount
     * @param WireType $wireType Type of wire transfer
     * @param string $beneficiaryName Beneficiary name
     * @param string $beneficiaryAccountNumber Beneficiary account number
     * @param string $beneficiaryBankName Beneficiary bank name
     * @param RoutingNumber|null $beneficiaryRoutingNumber US routing number (domestic)
     * @param SwiftCode|null $beneficiarySwiftCode SWIFT/BIC code (international)
     * @param Iban|null $beneficiaryIban IBAN (international)
     * @param string|null $beneficiaryAddress Beneficiary address
     * @param string|null $beneficiaryCity Beneficiary city
     * @param string|null $beneficiaryCountry Beneficiary country (ISO 2-letter)
     * @param string|null $intermediaryBankName Intermediary/correspondent bank name
     * @param SwiftCode|null $intermediarySwiftCode Intermediary bank SWIFT code
     * @param string|null $purposeOfPayment Purpose code or description
     * @param string|null $paymentReference Payment reference/memo
     * @param string|null $originatorReference Originator's reference number
     * @param array<string, string> $additionalInfo Additional wire instructions
     * @param bool $isUrgent Whether this is a priority/urgent wire
     */
    public function __construct(
        public Money $amount,
        public WireType $wireType,
        public string $beneficiaryName,
        public string $beneficiaryAccountNumber,
        public string $beneficiaryBankName,
        public ?RoutingNumber $beneficiaryRoutingNumber = null,
        public ?SwiftCode $beneficiarySwiftCode = null,
        public ?Iban $beneficiaryIban = null,
        public ?string $beneficiaryAddress = null,
        public ?string $beneficiaryCity = null,
        public ?string $beneficiaryCountry = null,
        public ?string $intermediaryBankName = null,
        public ?SwiftCode $intermediarySwiftCode = null,
        public ?string $purposeOfPayment = null,
        public ?string $paymentReference = null,
        public ?string $originatorReference = null,
        public array $additionalInfo = [],
        public bool $isUrgent = false,
    ) {}

    /**
     * Create a domestic wire transfer request.
     */
    public static function domestic(
        Money $amount,
        string $beneficiaryName,
        string $beneficiaryAccountNumber,
        string $beneficiaryBankName,
        RoutingNumber $beneficiaryRoutingNumber,
        ?string $paymentReference = null,
    ): self {
        return new self(
            amount: $amount,
            wireType: WireType::DOMESTIC,
            beneficiaryName: $beneficiaryName,
            beneficiaryAccountNumber: $beneficiaryAccountNumber,
            beneficiaryBankName: $beneficiaryBankName,
            beneficiaryRoutingNumber: $beneficiaryRoutingNumber,
            paymentReference: $paymentReference,
        );
    }

    /**
     * Create an international wire transfer request.
     */
    public static function international(
        Money $amount,
        string $beneficiaryName,
        string $beneficiaryAccountNumber,
        string $beneficiaryBankName,
        SwiftCode $beneficiarySwiftCode,
        string $beneficiaryCountry,
        ?Iban $beneficiaryIban = null,
        ?string $beneficiaryAddress = null,
        ?string $purposeOfPayment = null,
        ?string $paymentReference = null,
    ): self {
        return new self(
            amount: $amount,
            wireType: WireType::INTERNATIONAL,
            beneficiaryName: $beneficiaryName,
            beneficiaryAccountNumber: $beneficiaryAccountNumber,
            beneficiaryBankName: $beneficiaryBankName,
            beneficiarySwiftCode: $beneficiarySwiftCode,
            beneficiaryIban: $beneficiaryIban,
            beneficiaryAddress: $beneficiaryAddress,
            beneficiaryCountry: $beneficiaryCountry,
            purposeOfPayment: $purposeOfPayment,
            paymentReference: $paymentReference,
        );
    }

    /**
     * Add intermediary bank information.
     */
    public function withIntermediaryBank(
        string $bankName,
        SwiftCode $swiftCode,
    ): self {
        return new self(
            amount: $this->amount,
            wireType: $this->wireType,
            beneficiaryName: $this->beneficiaryName,
            beneficiaryAccountNumber: $this->beneficiaryAccountNumber,
            beneficiaryBankName: $this->beneficiaryBankName,
            beneficiaryRoutingNumber: $this->beneficiaryRoutingNumber,
            beneficiarySwiftCode: $this->beneficiarySwiftCode,
            beneficiaryIban: $this->beneficiaryIban,
            beneficiaryAddress: $this->beneficiaryAddress,
            beneficiaryCity: $this->beneficiaryCity,
            beneficiaryCountry: $this->beneficiaryCountry,
            intermediaryBankName: $bankName,
            intermediarySwiftCode: $swiftCode,
            purposeOfPayment: $this->purposeOfPayment,
            paymentReference: $this->paymentReference,
            originatorReference: $this->originatorReference,
            additionalInfo: $this->additionalInfo,
            isUrgent: $this->isUrgent,
        );
    }

    /**
     * Set as urgent/priority wire.
     */
    public function asUrgent(): self
    {
        return new self(
            amount: $this->amount,
            wireType: $this->wireType,
            beneficiaryName: $this->beneficiaryName,
            beneficiaryAccountNumber: $this->beneficiaryAccountNumber,
            beneficiaryBankName: $this->beneficiaryBankName,
            beneficiaryRoutingNumber: $this->beneficiaryRoutingNumber,
            beneficiarySwiftCode: $this->beneficiarySwiftCode,
            beneficiaryIban: $this->beneficiaryIban,
            beneficiaryAddress: $this->beneficiaryAddress,
            beneficiaryCity: $this->beneficiaryCity,
            beneficiaryCountry: $this->beneficiaryCountry,
            intermediaryBankName: $this->intermediaryBankName,
            intermediarySwiftCode: $this->intermediarySwiftCode,
            purposeOfPayment: $this->purposeOfPayment,
            paymentReference: $this->paymentReference,
            originatorReference: $this->originatorReference,
            additionalInfo: $this->additionalInfo,
            isUrgent: true,
        );
    }

    /**
     * Check if this is a domestic wire.
     */
    public function isDomestic(): bool
    {
        return $this->wireType === WireType::DOMESTIC;
    }

    /**
     * Check if this is an international wire.
     */
    public function isInternational(): bool
    {
        return $this->wireType === WireType::INTERNATIONAL;
    }

    /**
     * Check if an intermediary bank is specified.
     */
    public function hasIntermediaryBank(): bool
    {
        return $this->intermediaryBankName !== null && $this->intermediarySwiftCode !== null;
    }

    /**
     * Validate the wire transfer request.
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

        if (empty($this->beneficiaryName)) {
            $errors[] = 'Beneficiary name is required';
        }

        if (empty($this->beneficiaryAccountNumber)) {
            $errors[] = 'Beneficiary account number is required';
        }

        if (empty($this->beneficiaryBankName)) {
            $errors[] = 'Beneficiary bank name is required';
        }

        if ($this->isDomestic()) {
            if ($this->beneficiaryRoutingNumber === null) {
                $errors[] = 'Routing number is required for domestic wires';
            }
        }

        if ($this->isInternational()) {
            if ($this->beneficiarySwiftCode === null) {
                $errors[] = 'SWIFT code is required for international wires';
            }

            if (empty($this->beneficiaryCountry)) {
                $errors[] = 'Beneficiary country is required for international wires';
            }
        }

        return $errors;
    }
}
