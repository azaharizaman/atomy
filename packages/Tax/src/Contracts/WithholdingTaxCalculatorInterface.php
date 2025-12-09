<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Tax\ValueObjects\WithholdingTaxResult;
use Nexus\Tax\ValueObjects\WithholdingTaxContext;

/**
 * Withholding Tax Calculator Interface
 * 
 * Calculates withholding taxes on payments to vendors/suppliers.
 * Supports multiple jurisdictions via strategy pattern.
 * 
 * Common WHT scenarios:
 * - US: Backup withholding (24%), Non-resident withholding (30%), FATCA
 * - MY: Service fees (10%), Royalties (10%), Technical fees (10-15%), Interest (15%)
 * - Cross-border: Treaty-reduced rates, reverse charge
 * 
 * Stateless - does not persist anything, only calculates.
 */
interface WithholdingTaxCalculatorInterface
{
    /**
     * Calculate withholding tax for a payment
     * 
     * @param WithholdingTaxContext $context Complete payment context including
     *                                       vendor info, payment type, jurisdiction
     * @param Money $grossAmount Gross payment amount (before WHT)
     * 
     * @return WithholdingTaxResult Result containing WHT amount, net amount, and breakdown
     * 
     * @throws \Nexus\Tax\Exceptions\WithholdingTaxException If calculation fails
     * @throws \Nexus\Tax\Exceptions\JurisdictionNotSupportedException If jurisdiction not configured
     */
    public function calculate(WithholdingTaxContext $context, Money $grossAmount): WithholdingTaxResult;

    /**
     * Check if withholding tax applies for given context
     * 
     * Used for quick determination before full calculation.
     * 
     * @param WithholdingTaxContext $context Payment context
     * 
     * @return bool True if WHT applies
     */
    public function isApplicable(WithholdingTaxContext $context): bool;

    /**
     * Get applicable WHT rate for context
     * 
     * Returns the rate that would be applied, useful for preview/display.
     * 
     * @param WithholdingTaxContext $context Payment context
     * 
     * @return float WHT rate as decimal (e.g., 0.10 for 10%)
     */
    public function getApplicableRate(WithholdingTaxContext $context): float;

    /**
     * Get supported jurisdictions
     * 
     * @return array<string> List of jurisdiction codes (e.g., ['US', 'MY'])
     */
    public function getSupportedJurisdictions(): array;
}
