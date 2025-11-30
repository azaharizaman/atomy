<?php

declare(strict_types=1);

namespace Nexus\Assets\Events;

use DateTimeInterface;
use Nexus\Assets\Enums\DisposalMethod;

/**
 * Asset Disposed Event (CRITICAL severity)
 *
 * Published when asset is disposed.
 * Contains all data needed for GL posting (Tier 3).
 */
final readonly class AssetDisposedEvent
{
    public function __construct(
        public string $assetId,
        public string $tenantId,
        public string $assetTag,
        public DisposalMethod $disposalMethod,
        public float $originalAcquisitionCost,
        public float $accumulatedDepreciation,
        public float $netBookValue,
        public float $saleProceeds,
        public float $gainLoss,
        public DateTimeInterface $disposalDate,
        public ?string $reason = null,
        public bool $shouldPostToGL = false
    ) {}

    /**
     * Check if disposal resulted in gain
     */
    public function isGain(): bool
    {
        return $this->gainLoss > 0;
    }

    /**
     * Check if disposal resulted in loss
     */
    public function isLoss(): bool
    {
        return $this->gainLoss < 0;
    }

    /**
     * Convert to array for audit logging
     */
    public function toArray(): array
    {
        return [
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'asset_tag' => $this->assetTag,
            'disposal_method' => $this->disposalMethod->value,
            'original_acquisition_cost' => $this->originalAcquisitionCost,
            'accumulated_depreciation' => $this->accumulatedDepreciation,
            'net_book_value' => $this->netBookValue,
            'sale_proceeds' => $this->saleProceeds,
            'gain_loss' => $this->gainLoss,
            'disposal_date' => $this->disposalDate->format('Y-m-d'),
            'reason' => $this->reason,
            'should_post_to_gl' => $this->shouldPostToGL,
        ];
    }
}
