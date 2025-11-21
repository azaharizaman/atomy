<?php

declare(strict_types=1);

namespace Nexus\Assets\Events;

use DateTimeInterface;

/**
 * Asset Depreciated Event (MEDIUM severity)
 *
 * Batch event published after monthly depreciation run.
 * Contains summary data for all assets depreciated in the batch.
 */
final readonly class AssetDepreciatedEvent
{
    /**
     * @param array<string> $depreciationRecordIds Array of depreciation record IDs
     */
    public function __construct(
        public string $tenantId,
        public DateTimeInterface $periodEndDate,
        public int $assetsProcessed,
        public float $totalDepreciationAmount,
        public array $depreciationRecordIds,
        public DateTimeInterface $processedAt
    ) {}

    /**
     * Convert to array for audit logging
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'period_end_date' => $this->periodEndDate->format('Y-m-d'),
            'assets_processed' => $this->assetsProcessed,
            'total_depreciation_amount' => $this->totalDepreciationAmount,
            'depreciation_record_count' => count($this->depreciationRecordIds),
            'processed_at' => $this->processedAt->format('Y-m-d H:i:s'),
        ];
    }
}
