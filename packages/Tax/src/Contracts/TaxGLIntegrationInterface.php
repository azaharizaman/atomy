<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Tax\ValueObjects\TaxBreakdown;
use Nexus\Tax\ValueObjects\TaxContext;

/**
 * Tax GL Integration Interface
 * 
 * Handles posting tax transactions to General Ledger.
 * 
 * Application layer implements this using Nexus\Finance package.
 * Package defines interface; application provides GL integration.
 */
interface TaxGLIntegrationInterface
{
    /**
     * Post tax breakdown to General Ledger
     * 
     * Creates journal entry for tax transaction:
     * - DR: Tax Expense/Receivable (for each tax line)
     * - CR: Tax Liability (for each tax line)
     * 
     * @param TaxContext $context Transaction context
     * @param TaxBreakdown $breakdown Tax breakdown to post
     * @param array<string, mixed> $metadata Optional metadata for GL entry
     * 
     * @return string Journal entry ID
     */
    public function postTaxEntry(
        TaxContext $context,
        TaxBreakdown $breakdown,
        array $metadata = []
    ): string;

    /**
     * Post tax adjustment/reversal to GL
     * 
     * Creates contra-entry to reverse or adjust tax.
     * 
     * @param TaxContext $context Adjustment context
     * @param TaxBreakdown $adjustment Adjustment breakdown (may be negative)
     * @param string $originalJournalEntryId Original JE being adjusted
     * @param array<string, mixed> $metadata Optional metadata
     * 
     * @return string Journal entry ID for adjustment
     */
    public function postTaxAdjustment(
        TaxContext $context,
        TaxBreakdown $adjustment,
        string $originalJournalEntryId,
        array $metadata = []
    ): string;

    /**
     * Get GL account code for tax line
     * 
     * Returns GL account based on tax type, jurisdiction, level.
     * 
     * @param string $taxCode Tax code
     * @param string $jurisdictionCode Jurisdiction code
     * @param bool $isLiability True for liability account, false for expense
     * 
     * @return string GL account code
     */
    public function getGLAccountCode(
        string $taxCode,
        string $jurisdictionCode,
        bool $isLiability = true
    ): string;

    /**
     * Validate GL posting is balanced
     * 
     * Ensures debits = credits before posting.
     * 
     * @param TaxBreakdown $breakdown Tax breakdown
     * 
     * @return bool True if balanced
     */
    public function validateBalanced(TaxBreakdown $breakdown): bool;
}
