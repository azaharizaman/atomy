<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

/**
 * Chart of Account Provider Interface
 * 
 * Integration contract for Nexus\ChartOfAccount package.
 * Provides GL account mapping for cost elements.
 */
interface ChartOfAccountProviderInterface
{
    /**
     * Get GL account for cost element
     * 
     * @param string $costElementId Cost element identifier
     * @param string $costCenterId Cost center identifier
     * @return string|null
     */
    public function getGLAccount(
        string $costElementId,
        string $costCenterId
    ): ?string;

    /**
     * Validate GL account exists
     * 
     * @param string $glAccountId GL account identifier
     * @return bool
     */
    public function validateGLAccount(string $glAccountId): bool;

    /**
     * Get cost element accounts
     * 
     * @param string $costElementId Cost element identifier
     * @return array<string>
     */
    public function getCostElementAccounts(string $costElementId): array;

    /**
     * Get WIP account for cost center
     * 
     * @param string $costCenterId Cost center identifier
     * @return string|null
     */
    public function getWIPAccount(string $costCenterId): ?string;
}
