<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Tax\ValueObjects\TaxRate;

/**
 * Tax Rate Repository Interface
 * 
 * Defines contract for retrieving tax rates from storage.
 * Implements temporal queries - MUST require effective date.
 * 
 * Application layer implements this using Eloquent, Doctrine, etc.
 */
interface TaxRateRepositoryInterface
{
    /**
     * Find tax rate by code and effective date
     * 
     * Returns the tax rate that was active on the specified date.
     * TEMPORAL QUERY: Always requires effective date to prevent bugs.
     * 
     * @param string $taxCode Tax code (e.g., "US-CA-SALES", "CA-ON-HST")
     * @param \DateTimeInterface $effectiveDate Date when rate must be effective
     * 
     * @return TaxRate Tax rate object
     * 
     * @throws \Nexus\Tax\Exceptions\TaxRateNotFoundException If no rate found
     */
    public function findByCode(string $taxCode, \DateTimeInterface $effectiveDate): TaxRate;

    /**
     * Find all tax rates for a jurisdiction on a date
     * 
     * Returns all tax rates (federal, state, local) for a jurisdiction hierarchy.
     * 
     * @param string $jurisdictionCode Jurisdiction code (e.g., "US-CA-SF")
     * @param \DateTimeInterface $effectiveDate Date when rates must be effective
     * 
     * @return array<TaxRate> Array of tax rates ordered by hierarchy (federal → local)
     */
    public function findByJurisdiction(string $jurisdictionCode, \DateTimeInterface $effectiveDate): array;

    /**
     * Find rate by code or return default
     * 
     * @param string $taxCode Tax code
     * @param \DateTimeInterface $effectiveDate Effective date
     * @param TaxRate|null $default Default rate if not found
     * 
     * @return TaxRate|null Tax rate or default
     */
    public function findByCodeOrDefault(string $taxCode, \DateTimeInterface $effectiveDate, ?TaxRate $default = null): ?TaxRate;

    /**
     * Check if tax rate exists
     * 
     * @param string $taxCode Tax code
     * @param \DateTimeInterface $effectiveDate Effective date
     * 
     * @return bool True if rate exists
     */
    public function exists(string $taxCode, \DateTimeInterface $effectiveDate): bool;
}
