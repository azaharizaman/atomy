<?php

declare(strict_types=1);

namespace Nexus\Assets\Events;

use DateTimeInterface;

/**
 * Depreciation Recorded Event (MEDIUM severity)
 *
 * Published after depreciation calculation.
 * Includes depreciation record ID and net book value change for Finance reconciliation.
 */
final readonly class DepreciationRecordedEvent
{
    public function __construct(
        public string $assetId,
        public string $tenantId,
        public string $depreciationRecordId,
        public float $depreciationAmount,
        public float $accumulatedDepreciation,
        public float $netBookValue,
        public float $netBookValueChange,
        public DateTimeInterface $periodEndDate,
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
            'depreciation_record_id' => $this->depreciationRecordId,
            'depreciation_amount' => $this->depreciationAmount,
            'accumulated_depreciation' => $this->accumulatedDepreciation,
            'net_book_value' => $this->netBookValue,
            'net_book_value_change' => $this->netBookValueChange,
            'period_end_date' => $this->periodEndDate->format('Y-m-d'),
            'should_post_to_gl' => $this->shouldPostToGL,
        ];
    }
}
