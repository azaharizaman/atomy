<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Entities;

use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;

/**
 * Entity representing a single depreciation calculation for an asset in a period.
 *
 * @property-read string $id
 * @property-read string $assetId
 * @property-read string $scheduleId
 * @property-read string $periodId
 * @property-read DepreciationMethodType $methodType
 * @property-read DepreciationType $depreciationType
 * @property-read DepreciationAmount $depreciationAmount
 * @property-read BookValue $bookValueBefore
 * @property-read BookValue $bookValueAfter
 * @property-read \DateTimeImmutable $calculationDate
 * @property-read ?\DateTimeImmutable $postingDate
 * @property-read DepreciationStatus $status
 */
final class AssetDepreciation
{
    public function __construct(
        public readonly string $id,
        public readonly string $assetId,
        public readonly string $scheduleId,
        public readonly string $periodId,
        public readonly DepreciationMethodType $methodType,
        public readonly DepreciationType $depreciationType,
        public readonly DepreciationAmount $depreciationAmount,
        public readonly BookValue $bookValueBefore,
        public readonly BookValue $bookValueAfter,
        public readonly \DateTimeImmutable $calculationDate,
        public readonly ?\DateTimeImmutable $postingDate,
        public readonly DepreciationStatus $status,
    ) {}

    /**
     * Check if depreciation has been posted to GL.
     */
    public function isPosted(): bool
    {
        return $this->status === DepreciationStatus::POSTED;
    }

    /**
     * Check if depreciation can be reversed.
     */
    public function canBeReversed(): bool
    {
        return $this->status === DepreciationStatus::POSTED;
    }

    /**
     * Get the net book value after this depreciation.
     */
    public function getNetBookValue(): float
    {
        return $this->bookValueAfter->getNetBookValue();
    }

    /**
     * Get the depreciation amount as float.
     */
    public function getDepreciationAmount(): float
    {
        return $this->depreciationAmount->getAmount();
    }
}
