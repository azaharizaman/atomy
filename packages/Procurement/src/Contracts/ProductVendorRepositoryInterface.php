<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Repository interface for product-vendor relationships.
 *
 * Provides methods to resolve vendors for products, enabling scenarios like:
 * - Dropshipping: Finding the supplier for a product
 * - Cost comparison: Getting cost quotes across multiple vendors
 * - Preferred vendor selection: Determining default supplier
 *
 * This interface follows the Query pattern (CQRS) and is intended for
 * read-only operations. The consuming application must provide the
 * concrete implementation in the adapters layer.
 *
 * @see \Nexus\Payable\Contracts\VendorRepositoryInterface
 */
interface ProductVendorRepositoryInterface
{
    /**
     * Get the default/preferred vendor for a product.
     *
     * Used when a specific vendor is not specified but one is needed
     * (e.g., dropshipping, automated requisitioning).
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product variant identifier
     * @return string|null Vendor ID or null if no default vendor is set
     */
    public function getDefaultVendorForProduct(string $tenantId, string $productId): ?string;

    /**
     * Get all vendors that supply a specific product.
     *
     * Returns all approved vendor relationships for the product,
     * ordered by preference/ranking if available.
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product variant identifier
     * @return array<string> Array of vendor IDs, ordered by preference
     */
    public function getVendorsForProduct(string $tenantId, string $productId): array;

    /**
     * Check if a specific vendor supplies a product.
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product variant identifier
     * @param string $vendorId Vendor identifier
     * @return bool True if vendor supplies the product
     */
    public function isVendorForProduct(string $tenantId, string $productId, string $vendorId): bool;

    /**
     * Get the vendor's cost for a product.
     *
     * Returns the negotiated or catalog price for the product
     * from the specified vendor.
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product variant identifier
     * @param string $vendorId Vendor identifier
     * @return float|null Cost per unit, or null if not available
     */
    public function getProductCostForVendor(string $tenantId, string $productId, string $vendorId): ?float;
}
