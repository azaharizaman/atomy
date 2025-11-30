<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Enums\ServiceClassification;
use Nexus\Tax\Enums\TaxCalculationMethod;
use Nexus\Tax\Enums\TaxType;
use Nexus\Tax\Exceptions\InvalidTaxContextException;

/**
 * Tax Context: Complete context for a tax calculation
 * 
 * Contains all information needed to calculate taxes:
 * - Transaction identification and timing
 * - Tax type and code
 * - Customer and location details
 * - Optional exemptions and special rules
 * 
 * Immutable and validated on construction.
 */
final readonly class TaxContext
{
    /**
     * @param string $transactionId Unique transaction identifier (invoice, PO, etc.)
     * @param \DateTimeImmutable $transactionDate Transaction date (NOT current date for backdated transactions)
     * @param string $taxCode Primary tax code to apply (e.g., "US-CA-SALES", "CA-ON-HST")
     * @param TaxType $taxType Type of tax (VAT, GST, Sales Tax, etc.)
     * @param string $customerId Customer identifier for nexus and exemption checks
     * @param array<string, mixed> $destinationAddress Destination address for jurisdiction resolution
     * @param array<string, mixed>|null $originAddress Optional origin address for place-of-supply rules
     * @param ServiceClassification|null $serviceClassification Optional service type for cross-border logic
     * @param TaxCalculationMethod $calculationMethod How to calculate tax (default: Standard)
     * @param ExemptionCertificate|null $exemptionCertificate Optional tax exemption certificate
     * @param array<string, mixed> $metadata Optional custom metadata
     * 
     * @throws InvalidTaxContextException If any required field is invalid
     */
    public function __construct(
        public string $transactionId,
        public \DateTimeImmutable $transactionDate,
        public string $taxCode,
        public TaxType $taxType,
        public string $customerId,
        public array $destinationAddress,
        public ?array $originAddress = null,
        public ?ServiceClassification $serviceClassification = null,
        public TaxCalculationMethod $calculationMethod = TaxCalculationMethod::Standard,
        public ?ExemptionCertificate $exemptionCertificate = null,
        public array $metadata = [],
    ) {
        $this->validate();
    }

    /**
     * Validate tax context
     * 
     * @throws InvalidTaxContextException
     */
    private function validate(): void
    {
        if (empty($this->transactionId)) {
            throw new InvalidTaxContextException('transactionId', $this->transactionId, 'Transaction ID cannot be empty');
        }

        if (empty($this->taxCode)) {
            throw new InvalidTaxContextException('taxCode', $this->taxCode, 'Tax code cannot be empty');
        }

        if (empty($this->customerId)) {
            throw new InvalidTaxContextException('customerId', $this->customerId, 'Customer ID cannot be empty');
        }

        // Validate destination address has required fields
        $this->validateAddress($this->destinationAddress, 'destinationAddress');

        // Validate origin address if provided
        if ($this->originAddress !== null) {
            $this->validateAddress($this->originAddress, 'originAddress');
        }

        // Validate exemption certificate if provided
        if ($this->exemptionCertificate !== null) {
            // Certificate validation happens in ExemptionManager, but check basics
            if ($this->exemptionCertificate->customerId !== $this->customerId) {
                throw new InvalidTaxContextException(
                    'exemptionCertificate',
                    $this->exemptionCertificate->customerId,
                    "Exemption certificate customer ID ({$this->exemptionCertificate->customerId}) does not match transaction customer ID ({$this->customerId})"
                );
            }
        }
    }

    /**
     * Validate address array has required fields
     * 
     * @param array<string, mixed> $address
     * @param string $fieldName
     * 
     * @throws InvalidTaxContextException
     */
    private function validateAddress(array $address, string $fieldName): void
    {
        $requiredFields = ['country'];
        
        foreach ($requiredFields as $field) {
            if (!isset($address[$field]) || empty($address[$field])) {
                throw new InvalidTaxContextException(
                    $fieldName,
                    json_encode($address),
                    "Address field '{$field}' is required"
                );
            }
        }

        // Validate country code format (ISO 3166-1 alpha-2)
        if (!preg_match('/^[A-Z]{2}$/', $address['country'])) {
            throw new InvalidTaxContextException(
                "{$fieldName}.country",
                $address['country'],
                'Country code must be 2-letter ISO 3166-1 alpha-2 format (e.g., "US", "CA", "GB")'
            );
        }
    }

    /**
     * Check if transaction has exemption certificate
     */
    public function hasExemption(): bool
    {
        return $this->exemptionCertificate !== null;
    }

    /**
     * Check if transaction uses reverse charge mechanism
     */
    public function isReverseCharge(): bool
    {
        return $this->calculationMethod === TaxCalculationMethod::ReverseCharge;
    }

    /**
     * Check if transaction is cross-border (different origin and destination countries)
     */
    public function isCrossBorder(): bool
    {
        if ($this->originAddress === null) {
            return false;
        }

        return $this->originAddress['country'] !== $this->destinationAddress['country'];
    }

    /**
     * Get effective tax rate percentage for exemption
     * 
     * Returns the percentage of tax to actually charge after exemption.
     * For example:
     * - No exemption: 100.0000%
     * - 50% exemption: 50.0000%
     * - Full exemption: 0.0000%
     */
    public function getEffectiveTaxPercentage(): string
    {
        if ($this->exemptionCertificate === null) {
            return '100.0000';
        }

        // If 50% exemption, effective tax = 100% - 50% = 50%
        return bcsub('100.0000', $this->exemptionCertificate->exemptionPercentage, 4);
    }

    /**
     * Create a copy with different calculation method
     * 
     * Useful for scenarios where you need to preview both standard and reverse charge
     */
    public function withCalculationMethod(TaxCalculationMethod $method): self
    {
        return new self(
            transactionId: $this->transactionId,
            transactionDate: $this->transactionDate,
            taxCode: $this->taxCode,
            taxType: $this->taxType,
            customerId: $this->customerId,
            destinationAddress: $this->destinationAddress,
            originAddress: $this->originAddress,
            serviceClassification: $this->serviceClassification,
            calculationMethod: $method,
            exemptionCertificate: $this->exemptionCertificate,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a copy with exemption certificate
     */
    public function withExemption(ExemptionCertificate $certificate): self
    {
        return new self(
            transactionId: $this->transactionId,
            transactionDate: $this->transactionDate,
            taxCode: $this->taxCode,
            taxType: $this->taxType,
            customerId: $this->customerId,
            destinationAddress: $this->destinationAddress,
            originAddress: $this->originAddress,
            serviceClassification: $this->serviceClassification,
            calculationMethod: $this->calculationMethod,
            exemptionCertificate: $certificate,
            metadata: $this->metadata,
        );
    }

    /**
     * Convert to array for logging/debugging
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'transaction_date' => $this->transactionDate->format('Y-m-d H:i:s'),
            'tax_code' => $this->taxCode,
            'tax_type' => $this->taxType->value,
            'customer_id' => $this->customerId,
            'destination_address' => $this->destinationAddress,
            'origin_address' => $this->originAddress,
            'service_classification' => $this->serviceClassification?->value,
            'calculation_method' => $this->calculationMethod->value,
            'has_exemption' => $this->hasExemption(),
            'exemption_percentage' => $this->exemptionCertificate?->exemptionPercentage ?? '0.0000',
            'metadata' => $this->metadata,
        ];
    }
}
