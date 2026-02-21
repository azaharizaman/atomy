<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

use Nexus\CostAccounting\ValueObjects\CostAmount;

/**
 * Finance Posting Interface
 * 
 * Integration contract for Nexus\Finance package.
 * Handles posting costs to the General Ledger.
 */
interface FinancePostingInterface
{
    /**
     * Post cost to GL
     * 
     * @param array<string, mixed> $journalEntry Journal entry data
     * @return string Journal entry ID
     */
    public function postCost(array $journalEntry): string;

    /**
     * Post cost allocation
     * 
     * @param string $sourceCostCenterId Source cost center
     * @param string $targetCostCenterId Target cost center
     * @param CostAmount $amount Allocation amount
     * @param string $periodId Fiscal period
     * @return string Journal entry ID
     */
    public function postAllocation(
        string $sourceCostCenterId,
        string $targetCostCenterId,
        CostAmount $amount,
        string $periodId
    ): string;

    /**
     * Reverse cost posting
     * 
     * @param string $journalEntryId Journal entry to reverse
     * @param string $reason Reversal reason
     * @return string New journal entry ID
     */
    public function reversePosting(
        string $journalEntryId,
        string $reason
    ): string;

    /**
     * Validate posting
     * 
     * @param array<string, mixed> $journalEntry Journal entry
     * @return bool
     */
    public function validatePosting(array $journalEntry): bool;
}
