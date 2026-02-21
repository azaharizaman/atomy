<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Events;

use DateTimeInterface;

/**
 * Depreciation Reversed Event
 *
 * Dispatched when a depreciation calculation has been reversed.
 * Severity: HIGH
 *
 * @package Nexus\FixedAssetDepreciation\Events
 */
final readonly class DepreciationReversedEvent
{
    public function __construct(
        public string $depreciationId,
        public string $assetId,
        public string $tenantId,
        public string $periodId,
        public float $reversedAmount,
        public float $newAccumulatedDepreciation,
        public float $newNetBookValue,
        public string $reason,
        public ?string $reversalJournalEntryId = null,
        public DateTimeInterface $reversalDate,
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

    public function getReversedAmount(): float
    {
        return $this->reversedAmount;
    }

    public function toArray(): array
    {
        return [
            'depreciation_id' => $this->depreciationId,
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'period_id' => $this->periodId,
            'reversed_amount' => $this->reversedAmount,
            'new_accumulated_depreciation' => $this->newAccumulatedDepreciation,
            'new_net_book_value' => $this->newNetBookValue,
            'reason' => $this->reason,
            'reversal_journal_entry_id' => $this->reversalJournalEntryId,
            'reversal_date' => $this->reversalDate->format('Y-m-d H:i:s'),
            'context' => $this->context,
        ];
    }
}
