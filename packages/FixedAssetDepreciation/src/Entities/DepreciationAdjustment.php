<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Entities;

use DateTimeInterface;
use JsonSerializable;

/**
 * Depreciation Adjustment Entity
 *
 * Represents a schedule adjustment record when useful life, salvage value,
 * or depreciation method changes occur.
 *
 * @package Nexus\FixedAssetDepreciation\Entities
 */
final class DepreciationAdjustment implements JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $scheduleId,
        public readonly string $assetId,
        public readonly string $tenantId,
        public readonly string $adjustmentType,
        public readonly array $previousValues,
        public readonly array $newValues,
        public readonly float $remainingDepreciationBefore,
        public readonly float $remainingDepreciationAfter,
        public readonly string $reason,
        public readonly DateTimeInterface $adjustmentDate,
        public readonly ?string $adjustedBy = null,
        public readonly ?string $approvedBy = null,
        public readonly ?DateTimeInterface $approvedAt = null,
    ) {}

    public function getScheduleId(): string
    {
        return $this->scheduleId;
    }

    public function getAssetId(): string
    {
        return $this->assetId;
    }

    public function getAdjustmentType(): string
    {
        return $this->adjustmentType;
    }

    public function getDepreciationDelta(): float
    {
        return $this->remainingDepreciationAfter - $this->remainingDepreciationBefore;
    }

    public function hasSignificantImpact(): bool
    {
        return abs($this->getDepreciationDelta()) > 0.05 * $this->remainingDepreciationBefore;
    }

    public function isApproved(): bool
    {
        return $this->approvedBy !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->scheduleId,
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'adjustment_type' => $this->adjustmentType,
            'previous_values' => $this->previousValues,
            'new_values' => $this->newValues,
            'remaining_depreciation_before' => $this->remainingDepreciationBefore,
            'remaining_depreciation_after' => $this->remainingDepreciationAfter,
            'depreciation_delta' => $this->getDepreciationDelta(),
            'reason' => $this->reason,
            'adjustment_date' => $this->adjustmentDate->format('Y-m-d H:i:s'),
            'adjusted_by' => $this->adjustedBy,
            'approved_by' => $this->approvedBy,
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'has_significant_impact' => $this->hasSignificantImpact(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
