<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

use Nexus\Product\ValueObjects\Barcode;
use Nexus\Product\ValueObjects\Sku;

/**
 * Product Variant Repository Interface
 *
 * Manages persistence of product variants (the transactable items).
 */
interface ProductVariantRepositoryInterface
{
    /**
     * Find variant by ID
     *
     * @param string $id
     * @return ProductVariantInterface|null
     */
    public function findById(string $id): ?ProductVariantInterface;

    /**
     * Find variant by SKU within tenant
     *
     * @param string $tenantId
     * @param Sku $sku
     * @return ProductVariantInterface|null
     */
    public function findBySku(string $tenantId, Sku $sku): ?ProductVariantInterface;

    /**
     * Find variant by barcode within tenant
     *
     * @param string $tenantId
     * @param Barcode $barcode
     * @return ProductVariantInterface|null
     */
    public function findByBarcode(string $tenantId, Barcode $barcode): ?ProductVariantInterface;

    /**
     * Get all variants for a template
     *
     * @param string $templateId
     * @param bool $activeOnly
     * @return array<ProductVariantInterface>
     */
    public function getByTemplate(string $templateId, bool $activeOnly = true): array;

    /**
     * Get all variants for tenant
     *
     * @param string $tenantId
     * @param bool $activeOnly
     * @return array<ProductVariantInterface>
     */
    public function getAllForTenant(string $tenantId, bool $activeOnly = true): array;

    /**
     * Get variants by category
     *
     * @param string $tenantId
     * @param string $categoryCode
     * @param bool $activeOnly
     * @return array<ProductVariantInterface>
     */
    public function getByCategory(string $tenantId, string $categoryCode, bool $activeOnly = true): array;

    /**
     * Get standalone variants (not linked to template)
     *
     * @param string $tenantId
     * @param bool $activeOnly
     * @return array<ProductVariantInterface>
     */
    public function getStandaloneVariants(string $tenantId, bool $activeOnly = true): array;

    /**
     * Check if SKU exists in tenant
     *
     * @param string $tenantId
     * @param Sku $sku
     * @param string|null $excludeVariantId
     * @return bool
     */
    public function skuExists(string $tenantId, Sku $sku, ?string $excludeVariantId = null): bool;

    /**
     * Check if barcode exists in tenant
     *
     * @param string $tenantId
     * @param Barcode $barcode
     * @param string|null $excludeVariantId
     * @return bool
     */
    public function barcodeExists(string $tenantId, Barcode $barcode, ?string $excludeVariantId = null): bool;

    /**
     * Save variant
     *
     * @param ProductVariantInterface $variant
     * @return ProductVariantInterface
     */
    public function save(ProductVariantInterface $variant): ProductVariantInterface;

    /**
     * Delete variant
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Count variants for template
     *
     * @param string $templateId
     * @return int
     */
    public function countByTemplate(string $templateId): int;
}
