<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Tax\Enums\WithholdingTaxType;
use Nexus\Tax\Enums\PaymentType;

/**
 * Withholding Tax Context: Complete context for WHT calculation
 * 
 * Contains all information needed to determine applicable WHT rate:
 * - Vendor residency and tax ID status
 * - Payment type (services, royalties, interest, etc.)
 * - Jurisdiction (payer's country, vendor's country)
 * - Treaty status for reduced rates
 */
final readonly class WithholdingTaxContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string $payerJurisdiction Payer's jurisdiction code (ISO 3166-1 alpha-2)
     * @param string $vendorJurisdiction Vendor's jurisdiction code
     * @param PaymentType $paymentType Type of payment (services, royalties, etc.)
     * @param bool $vendorIsResident Whether vendor is tax resident in payer's jurisdiction
     * @param bool $vendorHasValidTaxId Whether vendor has provided valid tax ID (W-9, etc.)
     * @param string|null $vendorTaxId Vendor's tax identification number
     * @param string|null $treatyCountry Treaty country code if applicable
     * @param bool $isTreatyEligible Whether payment qualifies for treaty-reduced rate
     * @param array<string, mixed> $metadata Additional context-specific data
     */
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public string $payerJurisdiction,
        public string $vendorJurisdiction,
        public PaymentType $paymentType,
        public bool $vendorIsResident = true,
        public bool $vendorHasValidTaxId = true,
        public ?string $vendorTaxId = null,
        public ?string $treatyCountry = null,
        public bool $isTreatyEligible = false,
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->tenantId)) {
            throw new \InvalidArgumentException('Tenant ID cannot be empty');
        }

        if (empty($this->vendorId)) {
            throw new \InvalidArgumentException('Vendor ID cannot be empty');
        }

        if (!preg_match('/^[A-Z]{2}$/', $this->payerJurisdiction)) {
            throw new \InvalidArgumentException("Payer jurisdiction must be 2-letter ISO code: {$this->payerJurisdiction}");
        }

        if (!preg_match('/^[A-Z]{2}$/', $this->vendorJurisdiction)) {
            throw new \InvalidArgumentException("Vendor jurisdiction must be 2-letter ISO code: {$this->vendorJurisdiction}");
        }
    }

    /**
     * Check if this is a cross-border payment
     */
    public function isCrossBorder(): bool
    {
        return $this->payerJurisdiction !== $this->vendorJurisdiction;
    }

    /**
     * Check if vendor is non-resident for WHT purposes
     */
    public function isNonResident(): bool
    {
        return !$this->vendorIsResident || $this->isCrossBorder();
    }

    /**
     * Check if backup withholding should apply (US specific)
     */
    public function requiresBackupWithholding(): bool
    {
        return $this->payerJurisdiction === 'US' 
            && $this->vendorIsResident 
            && !$this->vendorHasValidTaxId;
    }
}
