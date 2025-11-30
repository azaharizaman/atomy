<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

/**
 * Tax Nexus Manager Interface
 * 
 * Determines if business has tax nexus (obligation to collect tax) in a jurisdiction.
 * Handles economic nexus thresholds (revenue/transaction count).
 * 
 * Application layer implements this with stateful tracking:
 * - Revenue aggregation per jurisdiction
 * - Transaction count tracking
 * - Caching for performance
 */
interface TaxNexusManagerInterface
{
    /**
     * Check if business has nexus in jurisdiction on a date
     * 
     * Nexus can be established by:
     * - Physical presence (office, warehouse, employees)
     * - Economic nexus (revenue threshold exceeded)
     * - Click-through nexus (affiliate relationships)
     * 
     * @param string $jurisdictionCode Jurisdiction to check
     * @param \DateTimeInterface $date Date to check nexus status
     * 
     * @return bool True if nexus exists
     */
    public function hasNexus(string $jurisdictionCode, \DateTimeInterface $date): bool;

    /**
     * Check if economic nexus threshold is exceeded
     * 
     * Economic nexus triggered by:
     * - Annual revenue threshold (e.g., $100,000 in CA)
     * - Annual transaction count (e.g., 200 transactions in many states)
     * 
     * @param string $jurisdictionCode Jurisdiction to check
     * @param \DateTimeInterface $date Date to check
     * 
     * @return bool True if economic nexus threshold exceeded
     */
    public function hasEconomicNexus(string $jurisdictionCode, \DateTimeInterface $date): bool;

    /**
     * Get revenue in jurisdiction for trailing 12 months
     * 
     * Used to calculate economic nexus status.
     * 
     * @param string $jurisdictionCode Jurisdiction code
     * @param \DateTimeInterface $asOfDate End date for calculation
     * 
     * @return \Nexus\Currency\ValueObjects\Money Total revenue
     */
    public function getTrailingRevenue(string $jurisdictionCode, \DateTimeInterface $asOfDate): \Nexus\Currency\ValueObjects\Money;

    /**
     * Get transaction count in jurisdiction for trailing 12 months
     * 
     * @param string $jurisdictionCode Jurisdiction code
     * @param \DateTimeInterface $asOfDate End date for calculation
     * 
     * @return int Transaction count
     */
    public function getTrailingTransactionCount(string $jurisdictionCode, \DateTimeInterface $asOfDate): int;

    /**
     * Get all jurisdictions where business has nexus
     * 
     * @param \DateTimeInterface $date Date to check
     * 
     * @return array<string> Jurisdiction codes
     */
    public function getAllNexusJurisdictions(\DateTimeInterface $date): array;
}
