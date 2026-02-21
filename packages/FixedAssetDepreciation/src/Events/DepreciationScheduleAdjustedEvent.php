<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Events;

use DateTimeInterface;

/**
 * Depreciation Schedule Adjusted Event
 *
 * Dispatched when a depreciation schedule has been modified due to
 * changes in useful life, salvage value, or depreciation method.
 * Severity: MEDIUM
 *
 * @package Nexus\FixedAssetDepreciation\Events
 */
final readonly class DepreciationScheduleAdjustedEvent
{
    public function __construct(
        public string $scheduleId,
        public string $assetId,
        public string $tenantId,
        public string $adjustmentType,
        public array $previousValues,
        public array $newValues,
        public float $remainingDepreciationBefore,
        public float $remainingDepreciationAfter,
        public string $reason,
        public DateTimeInterface $adjustmentDate,
        public array $context = [],
    ) {}

    public function getAssetId(): string
    {
        return $this->assetId;
    }

    public function getScheduleId(): string
    {
        return $this->scheduleId;
    }

    public function hasRemainingDepreciationChanged(): bool
    {
        return abs($this->remainingDepreciationBefore - $this->remainingDepreciationAfter) > 0.01;
    }

    public function toArray(): array
    {
        return [
            'schedule_id' => $this->scheduleId,
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'adjustment_type' => $this->adjustmentType,
            'previous_values' => $this->previousValues,
            'new_values' => $this->newValues,
            'remaining_depreciation_before' => $this->remainingDepreciationBefore,
            'remaining_depreciation_after' => $this->remainingDepreciationAfter,
            'reason' => $this->reason,
            'adjustment_date' => $this->adjustmentDate->format('Y-m-d H:i:s'),
            'context' => $this->context,
        ];
    }
}
