<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationRequest;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationResult;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxCalculation;

/**
 * Contract for tax validation within procurement workflows.
 *
 * Integrates with Nexus\Tax for calculation but provides
 * procurement-specific validation and compliance checks.
 */
interface TaxValidationServiceInterface
{
    /**
     * Validate tax amounts on an invoice.
     *
     * Checks:
     * - Tax codes are valid for jurisdiction
     * - Tax rates match master data
     * - Tax amounts are mathematically correct (within tolerance)
     * - Vendor tax registration is valid
     * - Reverse charge is applied where required
     */
    public function validateInvoiceTax(TaxValidationRequest $request): TaxValidationResult;

    /**
     * Calculate withholding tax for a payment.
     *
     * @param array<string, mixed> $vendorProfile Vendor tax profile
     */
    public function calculateWithholdingTax(
        string $tenantId,
        string $vendorId,
        Money $grossAmount,
        string $jurisdiction,
        array $vendorProfile = [],
    ): WithholdingTaxCalculation;

    /**
     * Validate vendor tax registration number format.
     */
    public function validateTaxRegistration(
        string $registrationNumber,
        string $country,
    ): bool;

    /**
     * Check if reverse charge mechanism applies.
     */
    public function isReverseChargeApplicable(
        string $supplierCountry,
        string $buyerCountry,
        string $goodsOrServices,
    ): bool;

    /**
     * Get applicable tax codes for a purchase category.
     *
     * @return array<string, array{code: string, rate: float, description: string}>
     */
    public function getApplicableTaxCodes(
        string $tenantId,
        string $purchaseCategory,
        string $jurisdiction,
    ): array;

    /**
     * Validate that tax exemption certificate is on file.
     */
    public function validateTaxExemption(
        string $tenantId,
        string $vendorId,
        string $exemptionType,
    ): bool;
}
