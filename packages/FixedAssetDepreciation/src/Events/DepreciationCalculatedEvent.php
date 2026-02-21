<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Events;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;

/**
 * Depreciation Calculated Event
 *
 * Dispatched when a single depreciation calculation is completed successfully.
 * Severity: MEDIUM
 *
 * @package Nexus\FixedAssetDepreciation\Events
 */
final readonly class DepreciationCalculatedEvent
{
    public function __construct(
        public string $depreciationId,
        public string $assetId,
        public string $tenantId,
        public string $periodId,
        public DepreciationMethodType $methodType,
        public DepreciationType $depreciationType,
        public DepreciationAmount $depreciationAmount,
        public float $bookValueBefore,
        public float $bookValueAfter,
        public DateTimeInterface $calculationDate,
        public array $context = [],
    ) {}

    public function getAssetId(): string
    {
        return $this->assetId;
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getDepreciationAmount(): float
    {
        return $this->depreciationAmount->amount;
    }

    public function getNetBookValueChange(): float
    {
        return $this->bookValueBefore - $this->bookValueAfter;
    }

    public function toArray(): array
    {
        return [
            'depreciation_id' => $this->depreciationId,
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'period_id' => $this->periodId,
            'method_type' => $this->methodType->value,
            'depreciation_type' => $this->depreciationType->value,
            'depreciation_amount' => $this->depreciationAmount->amount,
            'currency' => $this->depreciationAmount->currency,
            'book_value_before' => $this->bookValueBefore,
            'book_value_after' => $this->bookValueAfter,
            'calculation_date' => $this->calculationDate->format('Y-m-d H:i:s'),
            'context' => $this->context,
        ];
    }
}
