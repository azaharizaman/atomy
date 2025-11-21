<?php

declare(strict_types=1);

namespace Nexus\Assets\Events;

use DateTimeInterface;

/**
 * Asset Acquired Event (HIGH severity)
 *
 * Published when an asset is successfully acquired.
 * Triggers audit logging and optional GL posting (Tier 3).
 */
final readonly class AssetAcquiredEvent
{
    public function __construct(
        public string $assetId,
        public string $tenantId,
        public string $assetTag,
        public string $categoryId,
        public float $acquisitionCost,
        public DateTimeInterface $acquisitionDate,
        public ?string $purchaseOrderId = null,
        public ?string $vendorId = null,
        public bool $shouldPostToGL = false
    ) {}

    /**
     * Convert to array for audit logging
     */
    public function toArray(): array
    {
        return [
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'asset_tag' => $this->assetTag,
            'category_id' => $this->categoryId,
            'acquisition_cost' => $this->acquisitionCost,
            'acquisition_date' => $this->acquisitionDate->format('Y-m-d'),
            'purchase_order_id' => $this->purchaseOrderId,
            'vendor_id' => $this->vendorId,
            'should_post_to_gl' => $this->shouldPostToGL,
        ];
    }
}
