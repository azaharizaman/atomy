<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

use DateTimeImmutable;
use Nexus\Product\Enums\ProductType;
use Nexus\Product\Enums\TrackingMethod;
use Nexus\Product\ValueObjects\Barcode;
use Nexus\Product\ValueObjects\DimensionSet;
use Nexus\Product\ValueObjects\Sku;

/**
 * Product Variant Interface
 *
 * Represents the transactable item (e.g., "T-Shirt Model X, Red, Size M").
 * All transactions (purchases, sales, inventory) reference variants.
 * Can exist standalone (templateId = null) or linked to template.
 */
interface ProductVariantInterface
{
    /**
     * Get unique identifier
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get tenant identifier
     *
     * @return string
     */
    public function getTenantId(): string;

    /**
     * Get template ID (null for standalone products)
     *
     * @return string|null
     */
    public function getTemplateId(): ?string;

    /**
     * Get Stock Keeping Unit
     *
     * @return Sku
     */
    public function getSku(): Sku;

    /**
     * Get variant name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get variant description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get barcode
     *
     * @return Barcode|null
     */
    public function getBarcode(): ?Barcode;

    /**
     * Get product type
     *
     * @return ProductType
     */
    public function getType(): ProductType;

    /**
     * Get tracking method
     *
     * @return TrackingMethod
     */
    public function getTrackingMethod(): TrackingMethod;

    /**
     * Get base unit of measure code
     *
     * @return string
     */
    public function getBaseUom(): string;

    /**
     * Get physical dimensions
     *
     * @return DimensionSet|null
     */
    public function getDimensions(): ?DimensionSet;

    /**
     * Get category code
     *
     * @return string|null
     */
    public function getCategoryCode(): ?string;

    /**
     * Get default revenue account code (for sales)
     *
     * @return string|null
     */
    public function getDefaultRevenueAccountCode(): ?string;

    /**
     * Get default cost account code (for purchases)
     *
     * @return string|null
     */
    public function getDefaultCostAccountCode(): ?string;

    /**
     * Get default inventory account code
     *
     * @return string|null
     */
    public function getDefaultInventoryAccountCode(): ?string;

    /**
     * Check if variant is active
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Check if variant can be sold
     *
     * @return bool
     */
    public function isSaleable(): bool;

    /**
     * Check if variant can be purchased
     *
     * @return bool
     */
    public function isPurchaseable(): bool;

    /**
     * Get attribute values (for template-based variants)
     * e.g., ['COLOR' => 'Red', 'SIZE' => 'M']
     *
     * @return array<string, string>
     */
    public function getAttributeValues(): array;

    /**
     * Get additional metadata
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get creation timestamp
     *
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get last update timestamp
     *
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable;
}
