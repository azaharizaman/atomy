<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Contract for vendor query operations.
 * Consumer application must implement this interface.
 */
interface VendorQueryInterface
{
    public function isPreferredForCategory(string $tenantId, string $vendorId, string $categoryId): bool;
}
