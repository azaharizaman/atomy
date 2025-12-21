<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\WireType;

/**
 * Represents wire transfer instructions for domestic and international wires.
 *
 * Contains all information needed to initiate a wire transfer including
 * beneficiary details, bank information, and intermediary bank if needed.
 */
final class WireInstruction
{
    /**
     * @param WireType $wireType Type of wire transfer
     * @param Money $amount Transfer amount
     * @param string $beneficiaryName Beneficiary/Receiver name
     * @param string $beneficiaryAccountNumber Beneficiary account number
     * @param string $beneficiaryBankName Beneficiary bank name
     * @param string|null $beneficiaryAddress Beneficiary address
     * @param RoutingNumber|null $beneficiaryRoutingNumber Routing number (domestic)
     * @param SwiftCode|null $beneficiarySwiftCode SWIFT/BIC code (international)
     * @param Iban|null $beneficiaryIban IBAN (international)
     * @param string|null $intermediaryBankName Intermediary bank name
     * @param SwiftCode|null $intermediarySwiftCode Intermediary SWIFT code
     * @param string|null $intermediaryAccountNumber Intermediary account number
     * @param string|null $purposeOfPayment Purpose/Reference for the wire
     * @param string|null $additionalInstructions Additional payment instructions
     * @param string|null $originatorToBeneficiaryInfo OBI lines
     * @param string|null $referenceForBeneficiary Reference visible to beneficiary
     */
    public function __construct(
        public readonly WireType $wireType,
        public readonly Money $amount,
        public readonly string $beneficiaryName,
        public readonly string $beneficiaryAccountNumber,
        public readonly string $beneficiaryBankName,
        public readonly ?string $beneficiaryAddress = null,
        public readonly ?RoutingNumber $beneficiaryRoutingNumber = null,
        public readonly ?SwiftCode $beneficiarySwiftCode = null,
        public readonly ?Iban $beneficiaryIban = null,
        public readonly ?string $intermediaryBankName = null,
        public readonly ?SwiftCode $intermediarySwiftCode = null,
        public readonly ?string $intermediaryAccountNumber = null,
        public readonly ?string $purposeOfPayment = null,
        public readonly ?string $additionalInstructions = null,
        public readonly ?string $originatorToBeneficiaryInfo = null,
        public readonly ?string $referenceForBeneficiary = null,
    ) {}

    /**
     * Create a domestic wire instruction.
     */
    public static function domestic(
        Money $amount,
        string $beneficiaryName,
        string $beneficiaryAccountNumber,
        RoutingNumber $beneficiaryRoutingNumber,
        string $beneficiaryBankName,
        ?string $purposeOfPayment = null,
    ): self {
        return new self(
            wireType: WireType::DOMESTIC,
            amount: $amount,
            beneficiaryName: $beneficiaryName,
            beneficiaryAccountNumber: $beneficiaryAccountNumber,
            beneficiaryBankName: $beneficiaryBankName,
            beneficiaryRoutingNumber: $beneficiaryRoutingNumber,
            purposeOfPayment: $purposeOfPayment,
        );
    }

    /**
     * Create an international wire instruction.
     */
    public static function international(
        Money $amount,
        string $beneficiaryName,
        string $beneficiaryAccountNumber,
        SwiftCode $beneficiarySwiftCode,
        string $beneficiaryBankName,
        ?Iban $beneficiaryIban = null,
        ?string $beneficiaryAddress = null,
        ?SwiftCode $intermediarySwiftCode = null,
        ?string $intermediaryBankName = null,
        ?string $purposeOfPayment = null,
    ): self {
        return new self(
            wireType: WireType::INTERNATIONAL,
            amount: $amount,
            beneficiaryName: $beneficiaryName,
            beneficiaryAccountNumber: $beneficiaryIban?->value ?? $beneficiaryAccountNumber,
            beneficiaryBankName: $beneficiaryBankName,
            beneficiaryAddress: $beneficiaryAddress,
            beneficiarySwiftCode: $beneficiarySwiftCode,
            beneficiaryIban: $beneficiaryIban,
            intermediaryBankName: $intermediaryBankName,
            intermediarySwiftCode: $intermediarySwiftCode,
            purposeOfPayment: $purposeOfPayment,
        );
    }

    /**
     * Create a book transfer instruction.
     */
    public static function bookTransfer(
        Money $amount,
        string $beneficiaryName,
        string $beneficiaryAccountNumber,
        ?string $purposeOfPayment = null,
    ): self {
        return new self(
            wireType: WireType::BOOK_TRANSFER,
            amount: $amount,
            beneficiaryName: $beneficiaryName,
            beneficiaryAccountNumber: $beneficiaryAccountNumber,
            beneficiaryBankName: 'Internal',
            purposeOfPayment: $purposeOfPayment,
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
     * Check if this is a book transfer.
     */
    public function isBookTransfer(): bool
    {
        return $this->wireType === WireType::BOOK_TRANSFER;
    }

    /**
     * Check if an intermediary bank is involved.
     */
    public function hasIntermediaryBank(): bool
    {
        return $this->intermediarySwiftCode !== null
            || ($this->intermediaryBankName !== null && $this->intermediaryBankName !== '');
    }

    /**
     * Check if the beneficiary has an IBAN.
     */
    public function hasIban(): bool
    {
        return $this->beneficiaryIban !== null;
    }

    /**
     * Get the beneficiary bank identifier.
     *
     * Returns routing number for domestic, SWIFT code for international.
     */
    public function getBeneficiaryBankIdentifier(): string
    {
        if ($this->beneficiaryRoutingNumber !== null) {
            return $this->beneficiaryRoutingNumber->value;
        }

        if ($this->beneficiarySwiftCode !== null) {
            return $this->beneficiarySwiftCode->value;
        }

        return '';
    }

    /**
     * Get the effective account number.
     *
     * Returns IBAN if available, otherwise account number.
     */
    public function getEffectiveAccountNumber(): string
    {
        if ($this->beneficiaryIban !== null) {
            return $this->beneficiaryIban->value;
        }

        return $this->beneficiaryAccountNumber;
    }

    /**
     * Add an intermediary bank to the wire instruction.
     */
    public function withIntermediaryBank(
        string $bankName,
        SwiftCode $swiftCode,
        ?string $accountNumber = null,
    ): self {
        return new self(
            wireType: $this->wireType,
            amount: $this->amount,
            beneficiaryName: $this->beneficiaryName,
            beneficiaryAccountNumber: $this->beneficiaryAccountNumber,
            beneficiaryBankName: $this->beneficiaryBankName,
            beneficiaryAddress: $this->beneficiaryAddress,
            beneficiaryRoutingNumber: $this->beneficiaryRoutingNumber,
            beneficiarySwiftCode: $this->beneficiarySwiftCode,
            beneficiaryIban: $this->beneficiaryIban,
            intermediaryBankName: $bankName,
            intermediarySwiftCode: $swiftCode,
            intermediaryAccountNumber: $accountNumber,
            purposeOfPayment: $this->purposeOfPayment,
            additionalInstructions: $this->additionalInstructions,
            originatorToBeneficiaryInfo: $this->originatorToBeneficiaryInfo,
            referenceForBeneficiary: $this->referenceForBeneficiary,
        );
    }

    /**
     * Add OBI (Originator to Beneficiary Information) lines.
     */
    public function withObiInfo(string $obiInfo): self
    {
        return new self(
            wireType: $this->wireType,
            amount: $this->amount,
            beneficiaryName: $this->beneficiaryName,
            beneficiaryAccountNumber: $this->beneficiaryAccountNumber,
            beneficiaryBankName: $this->beneficiaryBankName,
            beneficiaryAddress: $this->beneficiaryAddress,
            beneficiaryRoutingNumber: $this->beneficiaryRoutingNumber,
            beneficiarySwiftCode: $this->beneficiarySwiftCode,
            beneficiaryIban: $this->beneficiaryIban,
            intermediaryBankName: $this->intermediaryBankName,
            intermediarySwiftCode: $this->intermediarySwiftCode,
            intermediaryAccountNumber: $this->intermediaryAccountNumber,
            purposeOfPayment: $this->purposeOfPayment,
            additionalInstructions: $this->additionalInstructions,
            originatorToBeneficiaryInfo: $obiInfo,
            referenceForBeneficiary: $this->referenceForBeneficiary,
        );
    }

    /**
     * Validate that the wire instruction has all required fields.
     *
     * @return array<string> List of missing required fields
     */
    public function validate(): array
    {
        $missing = [];

        if ($this->isDomestic() && $this->beneficiaryRoutingNumber === null) {
            $missing[] = 'beneficiaryRoutingNumber';
        }

        if ($this->isInternational() && $this->beneficiarySwiftCode === null) {
            $missing[] = 'beneficiarySwiftCode';
        }

        if ($this->beneficiaryName === '') {
            $missing[] = 'beneficiaryName';
        }

        if ($this->beneficiaryAccountNumber === '' && $this->beneficiaryIban === null) {
            $missing[] = 'beneficiaryAccountNumber';
        }

        return $missing;
    }

    /**
     * Check if the wire instruction is valid.
     */
    public function isValid(): bool
    {
        return count($this->validate()) === 0;
    }
}
