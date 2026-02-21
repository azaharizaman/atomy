<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Events;

use DateTimeInterface;

/**
 * Depreciation Posted Event
 *
 * Dispatched when depreciation has been posted to the General Ledger.
 * Severity: MEDIUM
 *
 * @package Nexus\FixedAssetDepreciation\Events
 */
final readonly class DepreciationPostedEvent
{
    public function __construct(
        public string $depreciationId,
        public string $assetId,
        public string $tenantId,
        public string $periodId,
        public string $journalEntryId,
        public float $depreciationAmount,
        public string $expenseAccountId,
        public string $accumulatedDepreciationAccountId,
        public DateTimeInterface $postingDate,
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

    public function getJournalEntryId(): string
    {
        return $this->journalEntryId;
    }

    public function toArray(): array
    {
        return [
            'depreciation_id' => $this->depreciationId,
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'period_id' => $this->periodId,
            'journal_entry_id' => $this->journalEntryId,
            'depreciation_amount' => $this->depreciationAmount,
            'expense_account_id' => $this->expenseAccountId,
            'accumulated_depreciation_account_id' => $this->accumulatedDepreciationAccountId,
            'posting_date' => $this->postingDate->format('Y-m-d H:i:s'),
            'context' => $this->context,
        ];
    }
}
