<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Tax\ValueObjects\TaxContext;
use Nexus\Tax\ValueObjects\TaxJurisdiction;

/**
 * Tax Jurisdiction Resolver Interface
 * 
 * Resolves tax jurisdiction from address and transaction context.
 * Implements place-of-supply rules for cross-border transactions.
 * 
 * Application layer implements this using:
 * - Geocoding API (Nexus\Geo package)
 * - Database jurisdiction table
 * - Caching decorator for performance
 */
interface TaxJurisdictionResolverInterface
{
    /**
     * Resolve tax jurisdiction from transaction context
     * 
     * Determines which jurisdiction has taxing authority based on:
     * - Destination address
     * - Origin address (if cross-border)
     * - Service classification (digital vs physical)
     * - B2B vs B2C transaction
     * 
     * @param TaxContext $context Transaction context with addresses
     * 
     * @return TaxJurisdiction Resolved jurisdiction
     * 
     * @throws \Nexus\Tax\Exceptions\JurisdictionNotResolvedException If cannot resolve
     */
    public function resolve(TaxContext $context): TaxJurisdiction;

    /**
     * Resolve jurisdiction from address only
     * 
     * Simpler method for non-cross-border scenarios.
     * 
     * @param array<string, mixed> $address Address components
     * 
     * @return TaxJurisdiction Resolved jurisdiction
     * 
     * @throws \Nexus\Tax\Exceptions\JurisdictionNotResolvedException
     */
    public function resolveFromAddress(array $address): TaxJurisdiction;

    /**
     * Get full jurisdiction hierarchy for an address
     * 
     * Returns array of jurisdictions from federal to municipal.
     * Example: [USA (Federal), California (State), San Francisco (Local)]
     * 
     * @param array<string, mixed> $address Address components
     * 
     * @return array<TaxJurisdiction> Ordered by hierarchy level
     */
    public function resolveHierarchy(array $address): array;

    /**
     * Check if address is in a specific jurisdiction
     * 
     * @param array<string, mixed> $address Address to check
     * @param string $jurisdictionCode Jurisdiction code to match
     * 
     * @return bool True if address is within jurisdiction
     */
    public function isInJurisdiction(array $address, string $jurisdictionCode): bool;
}
