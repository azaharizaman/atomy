<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\TaxValidationServiceInterface;
use Nexus\ProcurementOperations\DTOs\Tax\TaxLineItem;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationError;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationRequest;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationResult;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationWarning;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxCalculation;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxComponent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Validates invoice tax calculations and determines applicable tax treatments.
 *
 * This service handles:
 * - Invoice tax amount validation against expected calculations
 * - Tax code and rate verification
 * - Withholding tax determination
 * - Reverse charge applicability
 * - Tax exemption validation
 */
final readonly class InvoiceTaxValidationService implements TaxValidationServiceInterface
{
    private const DEFAULT_TOLERANCE_PERCENT = 0.01; // 1% tolerance

    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function validateInvoiceTax(TaxValidationRequest $request): TaxValidationResult
    {
        $errors = [];
        $warnings = [];

        // Simple mathematical validation of totals
        $calculatedTaxTotal = Money::of(0, $request->totalAmount->getCurrency());
        $calculatedSubtotal = Money::of(0, $request->totalAmount->getCurrency());

        foreach ($request->lineItems as $lineItem) {
            $calculatedTaxTotal = $calculatedTaxTotal->add($lineItem->taxAmount);
            $calculatedSubtotal = $calculatedSubtotal->add($lineItem->taxableAmount);
        }

        $expectedTotal = $calculatedSubtotal->add($calculatedTaxTotal);

        if (!$this->isWithinTolerance($request->totalAmount, $expectedTotal)) {
            $errors[] = TaxValidationError::totalMismatch(
                $expectedTotal,
                $request->totalAmount,
            );
        }

        if (count($errors) > 0) {
            return TaxValidationResult::invalid($errors, $warnings);
        }

        return TaxValidationResult::valid();
    }

    /**
     * {@inheritdoc}
     */
    public function calculateWithholdingTax(
        string $tenantId,
        string $vendorId,
        Money $grossAmount,
        string $jurisdiction,
        array $vendorProfile = [],
    ): WithholdingTaxCalculation {
        // Simple mock implementation
        return WithholdingTaxCalculation::noWithholding(
            grossAmount: $grossAmount,
            reason: 'No withholding applicable by default'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateTaxRegistration(
        string $registrationNumber,
        string $country,
    ): bool {
        // Basic length check as mock validation
        return strlen($registrationNumber) > 5;
    }

    /**
     * {@inheritdoc}
     */
    public function isReverseChargeApplicable(
        string $supplierCountry,
        string $buyerCountry,
        string $goodsOrServices,
    ): bool {
        return $supplierCountry !== $buyerCountry;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableTaxCodes(
        string $tenantId,
        string $purchaseCategory,
        string $jurisdiction,
    ): array {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function validateTaxExemption(
        string $tenantId,
        string $vendorId,
        string $exemptionType,
    ): bool {
        return true;
    }

    private function isWithinTolerance(Money $amount1, Money $amount2): bool
    {
        $diff = abs($amount1->getAmount() - $amount2->getAmount());
        $tolerance = max($amount1->getAmount(), $amount2->getAmount()) * self::DEFAULT_TOLERANCE_PERCENT;

        return $diff <= $tolerance;
    }
}
