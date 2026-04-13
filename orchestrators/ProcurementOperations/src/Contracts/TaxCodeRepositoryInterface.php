<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\Tax\TaxLineItem;

/**
 * Tax Code Repository Interface
 * 
 * Defines the contract for retrieval of tax codes and rates.
 */
interface TaxCodeRepositoryInterface
{
    /**
     * Find applicable tax codes for a tenant, category and jurisdiction
     * 
     * @return array<array{code: string, rate: float, description: string}>
     */
    public function findApplicable(string $tenantId, string $purchaseCategory, string $jurisdiction): array;

    /**
     * Get the current tax rate for a specific tax code
     */
    public function getRateForCode(string $tenantId, string $taxCode, string $jurisdiction): float;
}
