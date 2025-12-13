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
 *
 * Uses injected interfaces for tax rate lookups and vendor data
 * (implemented by adapter layer).
 */
final readonly class InvoiceTaxValidationService implements TaxValidationServiceInterface
{
    private const DEFAULT_TOLERANCE_PERCENT = 0.01; // 1% tolerance
    private const LARGE_VARIANCE_THRESHOLD = 0.05; // 5% = warning
    private const EXEMPTION_EXPIRY_WARNING_DAYS = 30;

    public function __construct(
        private TaxRateProviderInterface $taxRateProvider,
        private VendorTaxDataProviderInterface $vendorTaxProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function validateInvoiceTax(TaxValidationRequest $request): TaxValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validate vendor tax registration
        $vendorTaxValid = $this->validateTaxRegistration(
            $request->vendorId,
            $request->invoiceDate,
        );

        if (!$vendorTaxValid['valid']) {
            $errors[] = TaxValidationError::invalidVendorRegistration(
                $vendorTaxValid['reason'] ?? 'Invalid vendor tax registration',
            );
        }

        // Validate each line item
        foreach ($request->lineItems as $lineItem) {
            $lineErrors = $this->validateLineItem($lineItem, $request);
            $errors = array_merge($errors, $lineErrors['errors']);
            $warnings = array_merge($warnings, $lineErrors['warnings']);
        }

        // Validate totals
        $totalErrors = $this->validateTotals($request);
        $errors = array_merge($errors, $totalErrors['errors']);
        $warnings = array_merge($warnings, $totalErrors['warnings']);

        // Check for reverse charge applicability
        if ($request->isCrossBorder && $this->isReverseChargeApplicable(
            $request->vendorCountryCode ?? '',
            $request->buyerCountryCode,
            $request->totalAmount,
        )) {
            // Verify reverse charge was correctly applied
            $reverseChargeApplied = $this->hasReverseChargeLineItem($request->lineItems);

            if (!$reverseChargeApplied) {
                $warnings[] = TaxValidationWarning::reverseChargeMayApply(
                    $request->vendorCountryCode ?? 'unknown',
                    $request->buyerCountryCode,
                );
            }
        }

        // Check tax exemption validity
        foreach ($request->lineItems as $lineItem) {
            if ($lineItem->isExempt && $lineItem->exemptionCertificateId !== null) {
                $exemptionCheck = $this->validateTaxExemption(
                    $lineItem->exemptionCertificateId,
                    $request->invoiceDate,
                );

                if (!$exemptionCheck['valid']) {
                    $errors[] = TaxValidationError::invalidExemption(
                        $lineItem->exemptionCertificateId,
                        $exemptionCheck['reason'] ?? 'Exemption invalid',
                    );
                } elseif ($exemptionCheck['expiring_soon'] ?? false) {
                    $warnings[] = TaxValidationWarning::exemptionExpiringSoon(
                        $lineItem->exemptionCertificateId,
                        $exemptionCheck['expiry_date'],
                    );
                }
            }
        }

        $this->logger->debug('Invoice tax validation completed', [
            'invoice_id' => $request->invoiceId,
            'errors' => count($errors),
            'warnings' => count($warnings),
        ]);

        if (count($errors) > 0) {
            return TaxValidationResult::invalid($errors, $warnings);
        }

        if (count($warnings) > 0) {
            return TaxValidationResult::validWithWarnings($warnings);
        }

        return TaxValidationResult::valid();
    }

    /**
     * {@inheritdoc}
     */
    public function calculateWithholdingTax(
        string $vendorId,
        string $incomeType,
        Money $grossAmount,
        string $buyerCountryCode,
    ): WithholdingTaxCalculation {
        // Get vendor tax profile
        $vendorTaxProfile = $this->vendorTaxProvider->getVendorTaxProfile($vendorId);

        if ($vendorTaxProfile === null) {
            $this->logger->warning('Vendor tax profile not found for withholding calculation', [
                'vendor_id' => $vendorId,
            ]);

            // Return conservative calculation (apply maximum rate)
            $defaultRate = $this->getDefaultWithholdingRate($incomeType, $buyerCountryCode);
            $withholdingAmount = $grossAmount->multiply($defaultRate / 100);

            return WithholdingTaxCalculation::withWithholding(
                grossAmount: $grossAmount,
                withholdingAmount: $withholdingAmount,
                netAmount: $grossAmount->subtract($withholdingAmount),
                withholdingRate: $defaultRate,
                incomeType: $incomeType,
                components: [
                    $this->createWithholdingComponent($incomeType, $defaultRate, $withholdingAmount),
                ],
            );
        }

        // Check if vendor is exempt from withholding
        if ($vendorTaxProfile['withholding_exempt'] ?? false) {
            return WithholdingTaxCalculation::noWithholding(
                grossAmount: $grossAmount,
                reason: 'Vendor is exempt from withholding tax',
                exemptionCertificateId: $vendorTaxProfile['exemption_certificate_id'] ?? null,
            );
        }

        // Check for tax treaty rate
        $vendorCountry = $vendorTaxProfile['country_code'] ?? '';
        $treatyRate = $this->getTreatyWithholdingRate($vendorCountry, $buyerCountryCode, $incomeType);

        if ($treatyRate !== null) {
            $withholdingAmount = $grossAmount->multiply($treatyRate / 100);

            return WithholdingTaxCalculation::withTreatyRate(
                grossAmount: $grossAmount,
                withholdingAmount: $withholdingAmount,
                netAmount: $grossAmount->subtract($withholdingAmount),
                withholdingRate: $treatyRate,
                incomeType: $incomeType,
                treatyCountry: $vendorCountry,
                components: [
                    $this->createWithholdingComponent($incomeType, $treatyRate, $withholdingAmount),
                ],
            );
        }

        // Apply standard domestic rate
        $standardRate = $this->getStandardWithholdingRate($incomeType, $buyerCountryCode);
        $withholdingAmount = $grossAmount->multiply($standardRate / 100);

        return WithholdingTaxCalculation::withWithholding(
            grossAmount: $grossAmount,
            withholdingAmount: $withholdingAmount,
            netAmount: $grossAmount->subtract($withholdingAmount),
            withholdingRate: $standardRate,
            incomeType: $incomeType,
            components: [
                $this->createWithholdingComponent($incomeType, $standardRate, $withholdingAmount),
            ],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateTaxRegistration(
        string $vendorId,
        \DateTimeImmutable $asOfDate,
    ): array {
        $vendorTaxProfile = $this->vendorTaxProvider->getVendorTaxProfile($vendorId);

        if ($vendorTaxProfile === null) {
            return [
                'valid' => false,
                'reason' => 'Vendor tax profile not found',
            ];
        }

        $taxRegNumber = $vendorTaxProfile['tax_registration_number'] ?? null;
        $taxRegValidFrom = isset($vendorTaxProfile['tax_reg_valid_from'])
            ? new \DateTimeImmutable($vendorTaxProfile['tax_reg_valid_from'])
            : null;
        $taxRegValidTo = isset($vendorTaxProfile['tax_reg_valid_to'])
            ? new \DateTimeImmutable($vendorTaxProfile['tax_reg_valid_to'])
            : null;

        // Check if tax registration number exists
        if (empty($taxRegNumber)) {
            return [
                'valid' => false,
                'reason' => 'Vendor has no tax registration number on file',
            ];
        }

        // Check validity period
        if ($taxRegValidFrom !== null && $asOfDate < $taxRegValidFrom) {
            return [
                'valid' => false,
                'reason' => 'Tax registration not yet effective',
            ];
        }

        if ($taxRegValidTo !== null && $asOfDate > $taxRegValidTo) {
            return [
                'valid' => false,
                'reason' => 'Tax registration has expired',
            ];
        }

        // Validate registration number format (country-specific)
        $countryCode = $vendorTaxProfile['country_code'] ?? '';
        $formatValid = $this->taxRateProvider->validateTaxIdFormat($taxRegNumber, $countryCode);

        if (!$formatValid) {
            return [
                'valid' => false,
                'reason' => 'Tax registration number format invalid for country ' . $countryCode,
            ];
        }

        return [
            'valid' => true,
            'tax_registration_number' => $taxRegNumber,
            'country_code' => $countryCode,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isReverseChargeApplicable(
        string $vendorCountryCode,
        string $buyerCountryCode,
        Money $amount,
    ): bool {
        // Same country - no reverse charge
        if ($vendorCountryCode === $buyerCountryCode) {
            return false;
        }

        // Check if countries have reverse charge agreement
        $reverseChargeCountries = $this->taxRateProvider->getReverseChargeCountries($buyerCountryCode);

        if (!in_array($vendorCountryCode, $reverseChargeCountries, true)) {
            return false;
        }

        // Check minimum threshold for reverse charge
        $threshold = $this->taxRateProvider->getReverseChargeThreshold($buyerCountryCode);

        if ($threshold !== null && $amount->getAmount() < $threshold) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableTaxCodes(
        string $buyerCountryCode,
        string $vendorCountryCode,
        string $productCategory,
    ): array {
        // Get base tax codes for buyer country
        $taxCodes = $this->taxRateProvider->getTaxCodesForCountry($buyerCountryCode);

        // Filter by product category
        $applicableCodes = array_filter(
            $taxCodes,
            fn (array $code) => empty($code['product_categories'])
                || in_array($productCategory, $code['product_categories'], true),
        );

        // Add cross-border codes if applicable
        if ($vendorCountryCode !== $buyerCountryCode) {
            $crossBorderCodes = $this->taxRateProvider->getCrossBorderTaxCodes(
                $buyerCountryCode,
                $vendorCountryCode,
            );
            $applicableCodes = array_merge($applicableCodes, $crossBorderCodes);
        }

        return array_values($applicableCodes);
    }

    /**
     * {@inheritdoc}
     */
    public function validateTaxExemption(
        string $exemptionCertificateId,
        \DateTimeImmutable $asOfDate,
    ): array {
        $certificate = $this->vendorTaxProvider->getExemptionCertificate($exemptionCertificateId);

        if ($certificate === null) {
            return [
                'valid' => false,
                'reason' => 'Exemption certificate not found',
            ];
        }

        $validFrom = isset($certificate['valid_from'])
            ? new \DateTimeImmutable($certificate['valid_from'])
            : null;
        $validTo = isset($certificate['valid_to'])
            ? new \DateTimeImmutable($certificate['valid_to'])
            : null;

        // Check validity period
        if ($validFrom !== null && $asOfDate < $validFrom) {
            return [
                'valid' => false,
                'reason' => 'Exemption certificate not yet effective',
            ];
        }

        if ($validTo !== null && $asOfDate > $validTo) {
            return [
                'valid' => false,
                'reason' => 'Exemption certificate has expired',
            ];
        }

        // Check if expiring soon
        $expiringSoon = false;
        $expiryDate = null;

        if ($validTo !== null) {
            $daysUntilExpiry = (int) $asOfDate->diff($validTo)->days;
            $expiringSoon = $daysUntilExpiry <= self::EXEMPTION_EXPIRY_WARNING_DAYS && $daysUntilExpiry > 0;
            $expiryDate = $validTo->format('Y-m-d');
        }

        return [
            'valid' => true,
            'certificate_id' => $exemptionCertificateId,
            'exemption_type' => $certificate['exemption_type'] ?? 'general',
            'expiring_soon' => $expiringSoon,
            'expiry_date' => $expiryDate,
        ];
    }

    /**
     * Validate a single line item's tax calculation.
     *
     * @return array{errors: array<TaxValidationError>, warnings: array<TaxValidationWarning>}
     */
    private function validateLineItem(TaxLineItem $lineItem, TaxValidationRequest $request): array
    {
        $errors = [];
        $warnings = [];

        // Get expected tax rate for this tax code
        $expectedRate = $this->taxRateProvider->getTaxRate(
            $lineItem->taxCode,
            $request->buyerCountryCode,
            $request->invoiceDate,
        );

        if ($expectedRate === null) {
            $errors[] = TaxValidationError::invalidTaxCode(
                $lineItem->taxCode,
                sprintf('Tax code not valid for country %s on %s', 
                    $request->buyerCountryCode,
                    $request->invoiceDate->format('Y-m-d'),
                ),
            );
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        // Check rate matches
        if (abs($lineItem->taxRate - $expectedRate) > 0.001) {
            $errors[] = TaxValidationError::rateMismatch(
                $lineItem->taxCode,
                $expectedRate,
                $lineItem->taxRate,
            );
        }

        // Check calculation
        if (!$lineItem->isCalculationCorrect()) {
            $expectedTax = $lineItem->taxableAmount->multiply($lineItem->taxRate / 100);

            $errors[] = TaxValidationError::calculationMismatch(
                $lineItem->description ?? 'Line item',
                $expectedTax,
                $lineItem->taxAmount,
            );
        }

        // Check for unusual tax code usage
        if ($this->isUnusualTaxCodeForVendor($lineItem->taxCode, $request->vendorId)) {
            $warnings[] = TaxValidationWarning::unusualTaxCode(
                $lineItem->taxCode,
                'This tax code is not typically used by this vendor',
            );
        }

        // Check for large variance if within tolerance
        $variance = $this->calculateVariance($lineItem);
        if ($variance > self::LARGE_VARIANCE_THRESHOLD && $variance <= self::DEFAULT_TOLERANCE_PERCENT * 10) {
            $warnings[] = TaxValidationWarning::largeVariance(
                $lineItem->description ?? 'Line item',
                $variance,
            );
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate invoice totals.
     *
     * @return array{errors: array<TaxValidationError>, warnings: array<TaxValidationWarning>}
     */
    private function validateTotals(TaxValidationRequest $request): array
    {
        $errors = [];
        $warnings = [];

        // Calculate expected totals from line items
        $calculatedTaxTotal = Money::of(0, $request->totalAmount->getCurrency());
        $calculatedSubtotal = Money::of(0, $request->totalAmount->getCurrency());

        foreach ($request->lineItems as $lineItem) {
            $calculatedTaxTotal = $calculatedTaxTotal->add($lineItem->taxAmount);
            $calculatedSubtotal = $calculatedSubtotal->add($lineItem->taxableAmount);
        }

        // Check tax total
        $expectedTotal = $calculatedSubtotal->add($calculatedTaxTotal);

        if (!$this->isWithinTolerance($request->totalAmount, $expectedTotal)) {
            $errors[] = TaxValidationError::totalMismatch(
                $expectedTotal,
                $request->totalAmount,
            );
        }

        // Check if declared tax total matches line items
        if ($request->totalTaxAmount !== null) {
            if (!$this->isWithinTolerance($request->totalTaxAmount, $calculatedTaxTotal)) {
                $errors[] = TaxValidationError::taxTotalMismatch(
                    $calculatedTaxTotal,
                    $request->totalTaxAmount,
                );
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function hasReverseChargeLineItem(array $lineItems): bool
    {
        foreach ($lineItems as $lineItem) {
            if ($lineItem->isReverseCharge) {
                return true;
            }
        }
        return false;
    }

    private function isUnusualTaxCodeForVendor(string $taxCode, string $vendorId): bool
    {
        $vendorTaxProfile = $this->vendorTaxProvider->getVendorTaxProfile($vendorId);

        if ($vendorTaxProfile === null) {
            return false;
        }

        $typicalCodes = $vendorTaxProfile['typical_tax_codes'] ?? [];

        return !empty($typicalCodes) && !in_array($taxCode, $typicalCodes, true);
    }

    private function calculateVariance(TaxLineItem $lineItem): float
    {
        $expected = $lineItem->taxableAmount->multiply($lineItem->taxRate / 100);
        $actual = $lineItem->taxAmount;

        if ($expected->getAmount() === 0.0) {
            return 0.0;
        }

        return abs($actual->getAmount() - $expected->getAmount()) / $expected->getAmount();
    }

    private function isWithinTolerance(Money $amount1, Money $amount2): bool
    {
        $diff = abs($amount1->getAmount() - $amount2->getAmount());
        $tolerance = max($amount1->getAmount(), $amount2->getAmount()) * self::DEFAULT_TOLERANCE_PERCENT;

        return $diff <= $tolerance;
    }

    private function getDefaultWithholdingRate(string $incomeType, string $countryCode): float
    {
        return $this->taxRateProvider->getDefaultWithholdingRate($incomeType, $countryCode);
    }

    private function getStandardWithholdingRate(string $incomeType, string $countryCode): float
    {
        return $this->taxRateProvider->getWithholdingRate($incomeType, $countryCode);
    }

    private function getTreatyWithholdingRate(
        string $vendorCountry,
        string $buyerCountry,
        string $incomeType,
    ): ?float {
        return $this->taxRateProvider->getTreatyRate($vendorCountry, $buyerCountry, $incomeType);
    }

    private function createWithholdingComponent(
        string $incomeType,
        float $rate,
        Money $amount,
    ): WithholdingTaxComponent {
        return match ($incomeType) {
            'royalty' => WithholdingTaxComponent::royalty($rate, $amount),
            'service_fee' => WithholdingTaxComponent::serviceFee($rate, $amount),
            'interest' => WithholdingTaxComponent::interest($rate, $amount),
            'dividend' => WithholdingTaxComponent::dividend($rate, $amount),
            'contractor' => WithholdingTaxComponent::contractor($rate, $amount),
            default => new WithholdingTaxComponent(
                componentType: $incomeType,
                rate: $rate,
                amount: $amount,
                description: ucfirst($incomeType) . ' withholding',
            ),
        };
    }
}

/**
 * Interface for tax rate data (to be implemented by adapter layer).
 */
interface TaxRateProviderInterface
{
    public function getTaxRate(string $taxCode, string $countryCode, \DateTimeImmutable $asOfDate): ?float;

    public function validateTaxIdFormat(string $taxId, string $countryCode): bool;

    /**
     * @return array<string>
     */
    public function getReverseChargeCountries(string $buyerCountryCode): array;

    public function getReverseChargeThreshold(string $countryCode): ?float;

    /**
     * @return array<array{code: string, name: string, rate: float, product_categories: array<string>}>
     */
    public function getTaxCodesForCountry(string $countryCode): array;

    /**
     * @return array<array{code: string, name: string, rate: float}>
     */
    public function getCrossBorderTaxCodes(string $buyerCountry, string $vendorCountry): array;

    public function getDefaultWithholdingRate(string $incomeType, string $countryCode): float;

    public function getWithholdingRate(string $incomeType, string $countryCode): float;

    public function getTreatyRate(string $vendorCountry, string $buyerCountry, string $incomeType): ?float;
}

/**
 * Interface for vendor tax data (to be implemented by adapter layer).
 */
interface VendorTaxDataProviderInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function getVendorTaxProfile(string $vendorId): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function getExemptionCertificate(string $certificateId): ?array;
}
