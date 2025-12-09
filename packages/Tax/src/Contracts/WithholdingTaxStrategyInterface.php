<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\ValueObjects\WithholdingTaxResult;
use Nexus\Tax\ValueObjects\WithholdingTaxContext;

/**
 * Withholding Tax Strategy Interface
 * 
 * Jurisdiction-specific WHT calculation strategy.
 * Each jurisdiction implements its own WHT rules via this interface.
 * 
 * Strategy pattern allows:
 * - US: Federal withholding rules, 1099 categorization
 * - MY: Malaysian WHT rates per Income Tax Act sections
 * - Extensible to other jurisdictions
 */
interface WithholdingTaxStrategyInterface
{
    /**
     * Get jurisdiction code this strategy handles
     * 
     * @return string ISO 3166-1 alpha-2 country code (e.g., 'US', 'MY')
     */
    public function getJurisdictionCode(): string;

    /**
     * Calculate withholding tax using jurisdiction-specific rules
     * 
     * @param WithholdingTaxContext $context Payment context
     * @param Money $grossAmount Gross payment amount
     * 
     * @return WithholdingTaxResult Calculation result with breakdown
     */
    public function calculate(WithholdingTaxContext $context, Money $grossAmount): WithholdingTaxResult;

    /**
     * Determine if WHT applies based on jurisdiction rules
     * 
     * @param WithholdingTaxContext $context Payment context
     * 
     * @return bool True if this jurisdiction requires WHT for context
     */
    public function isApplicable(WithholdingTaxContext $context): bool;

    /**
     * Get WHT rate for the given context
     * 
     * @param WithholdingTaxContext $context Payment context
     * 
     * @return float Rate as decimal (0.10 = 10%)
     */
    public function getRate(WithholdingTaxContext $context): float;

    /**
     * Get supported payment types for this jurisdiction
     * 
     * @return array<string> List of supported payment type codes
     */
    public function getSupportedPaymentTypes(): array;
}
