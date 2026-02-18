<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\DataProviders;

use Nexus\Procurement\Contracts\ProductVendorRepositoryInterface;
use Nexus\Sales\Contracts\SalesOrderLineInterface;

/**
 * Data provider for dropshipping operations.
 *
 * Aggregates vendor information for sales order lines,
 * enabling vendor grouping for purchase order creation.
 */
final readonly class DropshipDataProvider
{
    public function __construct(
        private ProductVendorRepositoryInterface $productVendorRepository
    ) {
    }

    /**
     * Group sales order lines by their default vendor.
     *
     * @param string $tenantId Tenant identifier
     * @param SalesOrderLineInterface[] $lines Sales order lines
     * @return array<string, SalesOrderLineInterface[]> Array keyed by vendor ID
     */
    public function groupLinesByVendor(string $tenantId, array $lines): array
    {
        $vendorGroups = [];

        foreach ($lines as $line) {
            $productId = $line->getProductVariantId();
            $vendorId = $this->productVendorRepository->getDefaultVendorForProduct($tenantId, $productId);

            if ($vendorId === null) {
                continue;
            }

            if (!isset($vendorGroups[$vendorId])) {
                $vendorGroups[$vendorId] = [];
            }

            $vendorGroups[$vendorId][] = $line;
        }

        return $vendorGroups;
    }

    /**
     * Get vendor costs for multiple product lines.
     *
     * @param string $tenantId Tenant identifier
     * @param SalesOrderLineInterface[] $lines Sales order lines
     * @param string $vendorId Vendor identifier
     * @return array<string, float|null> Product ID => Cost per unit
     */
    public function getVendorCostsForLines(
        string $tenantId,
        array $lines,
        string $vendorId
    ): array {
        $costs = [];

        foreach ($lines as $line) {
            $productId = $line->getProductVariantId();
            $costs[$productId] = $this->productVendorRepository->getProductCostForVendor(
                $tenantId,
                $productId,
                $vendorId
            );
        }

        return $costs;
    }
}
