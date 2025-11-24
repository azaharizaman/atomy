<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\ValueObjects\TaxBreakdown;
use Nexus\Tax\ValueObjects\TaxContext;

/**
 * Tax Calculator Interface
 * 
 * Core interface for calculating taxes on a transaction.
 * Implements hierarchical tax calculation with support for:
 * - Multi-jurisdiction taxes (federal, state, local)
 * - Cascading taxes (tax on tax)
 * - Exemptions (partial and full)
 * - Reverse charge mechanism
 * 
 * Stateless - does not persist anything, only calculates.
 */
interface TaxCalculatorInterface
{
    /**
     * Calculate tax for a transaction
     * 
     * @param TaxContext $context Complete transaction context
     * @param Money $amount Taxable amount (before tax)
     * 
     * @return TaxBreakdown Complete tax breakdown with all tax lines
     * 
     * @throws \Nexus\Tax\Exceptions\TaxRateNotFoundException If tax rate not found
     * @throws \Nexus\Tax\Exceptions\TaxCalculationException If calculation fails
     * @throws \Nexus\Tax\Exceptions\ReverseChargeNotAllowedException If reverse charge not supported
     */
    public function calculate(TaxContext $context, Money $amount): TaxBreakdown;

    /**
     * Preview tax calculation without applying exemptions
     * 
     * Useful for showing "tax without exemption" for comparison.
     * 
     * @param TaxContext $context Transaction context (exemption ignored)
     * @param Money $amount Taxable amount
     * 
     * @return TaxBreakdown Tax breakdown without exemption applied
     */
    public function previewWithoutExemption(TaxContext $context, Money $amount): TaxBreakdown;

    /**
     * Calculate adjustment for contra-transaction (credit note, refund)
     * 
     * Creates negative tax breakdown to reverse original transaction.
     * 
     * @param TaxBreakdown $original Original tax breakdown being reversed
     * @param Money|null $adjustmentAmount Optional partial adjustment amount
     * 
     * @return TaxBreakdown Negative tax breakdown for adjustment
     */
    public function calculateAdjustment(TaxBreakdown $original, ?Money $adjustmentAmount = null): TaxBreakdown;
}
