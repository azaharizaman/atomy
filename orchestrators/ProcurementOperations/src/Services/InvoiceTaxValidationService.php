<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\TaxValidationServiceInterface;
use Nexus\ProcurementOperations\Contracts\TaxCodeRepositoryInterface;
use Nexus\ProcurementOperations\Contracts\TaxExemptionRepositoryInterface;
use Nexus\ProcurementOperations\DTOs\Tax\TaxLineItem;
...
final readonly class InvoiceTaxValidationService implements TaxValidationServiceInterface
{
    private const DEFAULT_TOLERANCE_PERCENT = 0.01; // 1% tolerance

    public function __construct(
        private TaxCodeRepositoryInterface $taxCodeRepository,
        private TaxExemptionRepositoryInterface $taxExemptionRepository,
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

            // Validate each line item's tax correctness
            if ($lineItem->taxCode !== null) {
                $rate = $this->taxCodeRepository->getRateForCode(
                    $request->tenantId,
                    $lineItem->taxCode,
                    $request->jurisdiction
                );
                
                $expectedTax = $lineItem->taxableAmount->multiply($rate / 100);
                
                if (!$this->isWithinTolerance($lineItem->taxAmount, $expectedTax)) {
                    $errors[] = TaxValidationError::lineItemTaxMismatch($lineItem, $expectedTax, $lineItem->taxAmount);
                }
            }
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
        // Implementation logic for withholding tax
        // This should probably be moved to a domain rule or dedicated service, 
        // but we'll put some branching logic here as requested.
        
        $isExempt = $this->taxExemptionRepository->isExemptionValid($tenantId, $vendorId, 'withholding');
        if ($isExempt) {
            return WithholdingTaxCalculation::noWithholding($grossAmount, 'Vendor is exempt from withholding tax');
        }

        // Example: 10% withholding for certain jurisdictions if not exempt
        if (in_array($jurisdiction, ['MY', 'ID', 'TH'])) {
            $rate = 0.10;
            $withholdingAmount = $grossAmount->multiply($rate);
            
            return new WithholdingTaxCalculation(
                grossAmount: $grossAmount,
                netAmount: $grossAmount->subtract($withholdingAmount),
                totalWithholdingAmount: $withholdingAmount,
                components: [
                    new WithholdingTaxComponent('WHT', $withholdingAmount, $rate * 100, 'Standard Withholding Tax')
                ]
            );
        }

        return WithholdingTaxCalculation::noWithholding($grossAmount, 'No withholding rules found for jurisdiction');
    }

    /**
     * {@inheritdoc}
     */
    public function validateTaxRegistration(
        string $registrationNumber,
        string $country,
    ): bool {
        if (empty($registrationNumber) || empty($country)) {
            return false;
        }

        // Country-specific validation rules
        return match (strtoupper($country)) {
            'MY' => (bool) preg_match('/^[0-9]{12}$/', $registrationNumber), // GST/SST format example
            'SG' => (bool) preg_match('/^[0-9]{8,9}[A-Z]$/', $registrationNumber), // UEN example
            'ID' => (bool) preg_match('/^[0-9]{15}$/', $registrationNumber), // NPWP example
            default => strlen($registrationNumber) > 5,
        };
    }

    /**
     * {@inheritdoc}
     */
    public function isReverseChargeApplicable(
        string $supplierCountry,
        string $buyerCountry,
        string $goodsOrServices,
    ): bool {
        if ($supplierCountry === $buyerCountry) {
            return false;
        }

        // Rules typically differ for goods vs services
        $type = strtolower($goodsOrServices);
        
        return match ($type) {
            'services' => true, // Often reverse charge applies to cross-border services
            'goods' => false, // Often handled via import VAT instead of reverse charge
            default => true, // Fallback to supplier/buyer country mismatch
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableTaxCodes(
        string $tenantId,
        string $purchaseCategory,
        string $jurisdiction,
    ): array {
        if (empty($tenantId) || empty($purchaseCategory) || empty($jurisdiction)) {
            return [];
        }

        return $this->taxCodeRepository->findApplicable($tenantId, $purchaseCategory, $jurisdiction);
    }

    /**
     * {@inheritdoc}
     */
    public function validateTaxExemption(
        string $tenantId,
        string $vendorId,
        string $exemptionType,
    ): bool {
        if (empty($tenantId) || empty($vendorId)) {
            return false;
        }

        return $this->taxExemptionRepository->isExemptionValid($tenantId, $vendorId, $exemptionType);
    }

    private function isWithinTolerance(Money $amount1, Money $amount2): bool
    {
        $diff = abs($amount1->getAmount() - $amount2->getAmount());
        $tolerance = max($amount1->getAmount(), $amount2->getAmount()) * self::DEFAULT_TOLERANCE_PERCENT;

        return $diff <= $tolerance;
    }
}
