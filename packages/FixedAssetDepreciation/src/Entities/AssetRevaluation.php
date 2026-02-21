<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Entities;

use Nexus\FixedAssetDepreciation\Enums\RevaluationType;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;
use Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount;

/**
 * Entity representing an asset revaluation event.
 *
 * This entity tracks revaluation events following IFRS IAS 16
 * revaluation model for property, plant, and equipment.
 *
 * @package Nexus\FixedAssetDepreciation\Entities
 */
final class AssetRevaluation
{
    /**
     * @param string $id Unique identifier
     * @param string $assetId The asset being revalued
     * @param string $tenantId The tenant identifier
     * @param DateTimeImmutable $revaluationDate Date of revaluation
     * @param RevaluationType $revaluationType Increment or decrement
     * @param BookValue $previousBookValue Book value before revaluation
     * @param BookValue $newBookValue Book value after revaluation
     * @param RevaluationAmount $revaluationAmount The revaluation delta
     * @param string|null $glAccountId GL account for revaluation reserve
     * @param string $reason Reason for revaluation
     * @param DateTimeImmutable $createdAt When created
     * @param string|null $journalEntryId Posted journal entry ID
     * @param DateTimeImmutable|null $postedAt When posted to GL
     * @param string|null $scheduleId Linked depreciation schedule
     * @param string|null $reversesRevaluationId If this reverses a prior revaluation
     * @param string|null $status Status (pending, posted, reversed)
     */
    public function __construct(
        public readonly string $id,
        public readonly string $assetId,
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $revaluationDate,
        public readonly RevaluationType $revaluationType,
        public readonly BookValue $previousBookValue,
        public readonly BookValue $newBookValue,
        public readonly RevaluationAmount $revaluationAmount,
        public readonly ?string $glAccountId,
        public readonly string $reason,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?string $journalEntryId = null,
        public readonly ?\DateTimeImmutable $postedAt = null,
        public readonly ?string $scheduleId = null,
        public readonly ?string $reversesRevaluationId = null,
        public readonly ?string $status = 'pending',
    ) {}

    /**
     * Create a new revaluation.
     *
     * @param string $assetId Asset ID
     * @param BookValue $previousBookValue Previous book value
     * @param BookValue $newBookValue New book value
     * @param RevaluationType $type Revaluation type
     * @param string $reason Reason
     * @param DateTimeImmutable|null $date Revaluation date
     * @param string|null $glAccountId GL account for reserve
     * @return self
     */
    public static function create(
        string $assetId,
        string $tenantId,
        BookValue $previousBookValue,
        BookValue $newBookValue,
        RevaluationType $type,
        string $reason,
        ?\DateTimeImmutable $date = null,
        ?string $glAccountId = null
    ): self {
        $previousValue = $previousBookValue->getNetBookValue();
        $newValue = $newBookValue->getNetBookValue();

        return new self(
            id: uniqid('rev_'),
            assetId: $assetId,
            tenantId: $tenantId,
            revaluationDate: $date ?? new \DateTimeImmutable(),
            revaluationType: $type,
            previousBookValue: $previousBookValue,
            newBookValue: $newBookValue,
            revaluationAmount: RevaluationAmount::fromValues(
                $previousValue,
                $newValue,
                'USD'
            ),
            glAccountId: $glAccountId,
            reason: $reason,
            createdAt: new \DateTimeImmutable(),
            journalEntryId: null,
            postedAt: null,
            scheduleId: null,
            reversesRevaluationId: null,
            status: 'pending',
        );
    }

    /**
     * Check if this is an increment.
     *
     * @return bool
     */
    public function isIncrement(): bool
    {
        return $this->revaluationType === RevaluationType::INCREMENT;
    }

    /**
     * Check if this is a decrement.
     *
     * @return bool
     */
    public function isDecrement(): bool
    {
        return $this->revaluationType === RevaluationType::DECREMENT;
    }

    /**
     * Check if posted to GL.
     *
     * @return bool
     */
    public function isPosted(): bool
    {
        return $this->status === 'posted' && $this->journalEntryId !== null;
    }

    /**
     * Check if pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if reversed.
     *
     * @return bool
     */
    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    /**
     * Get the revaluation amount as float.
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->revaluationAmount->amount;
    }

    /**
     * Get net book value before revaluation.
     *
     * @return float
     */
    public function getPreviousNetBookValue(): float
    {
        return $this->previousBookValue->getNetBookValue();
    }

    /**
     * Get net book value after revaluation.
     *
     * @return float
     */
    public function getNewNetBookValue(): float
    {
        return $this->newBookValue->getNetBookValue();
    }

    /**
     * Mark as posted.
     *
     * @param string $journalEntryId Journal entry ID
     * @return self New instance
     */
    public function withPosting(string $journalEntryId): self
    {
        return new self(
            id: $this->id,
            assetId: $this->assetId,
            tenantId: $this->tenantId,
            revaluationDate: $this->revaluationDate,
            revaluationType: $this->revaluationType,
            previousBookValue: $this->previousBookValue,
            newBookValue: $this->newBookValue,
            revaluationAmount: $this->revaluationAmount,
            glAccountId: $this->glAccountId,
            reason: $this->reason,
            createdAt: $this->createdAt,
            journalEntryId: $journalEntryId,
            postedAt: new \DateTimeImmutable(),
            scheduleId: $this->scheduleId,
            reversesRevaluationId: $this->reversesRevaluationId,
            status: 'posted',
        );
    }

    /**
     * Mark as reversed.
     *
     * @param string $originalRevaluationId Original revaluation ID
     * @return self New instance
     */
    public function asReversal(string $originalRevaluationId): self
    {
        return new self(
            id: uniqid('rev_'),
            assetId: $this->assetId,
            tenantId: $this->tenantId,
            revaluationDate: new \DateTimeImmutable(),
            revaluationType: $this->revaluationType === RevaluationType::INCREMENT
                ? RevaluationType::DECREMENT
                : RevaluationType::INCREMENT,
            previousBookValue: $this->newBookValue,
            newBookValue: $this->previousBookValue,
            revaluationAmount: $this->revaluationAmount->negate(),
            glAccountId: $this->glAccountId,
            reason: 'Reversal: ' . $this->reason,
            createdAt: new \DateTimeImmutable(),
            journalEntryId: null,
            postedAt: null,
            scheduleId: $this->scheduleId,
            reversesRevaluationId: $originalRevaluationId,
            status: 'pending',
        );
    }

    /**
     * Link to schedule.
     *
     * @param string $scheduleId Schedule ID
     * @return self New instance
     */
    public function withSchedule(string $scheduleId): self
    {
        return new self(
            id: $this->id,
            assetId: $this->assetId,
            tenantId: $this->tenantId,
            revaluationDate: $this->revaluationDate,
            revaluationType: $this->revaluationType,
            previousBookValue: $this->previousBookValue,
            newBookValue: $this->newBookValue,
            revaluationAmount: $this->revaluationAmount,
            glAccountId: $this->glAccountId,
            reason: $this->reason,
            createdAt: $this->createdAt,
            journalEntryId: $this->journalEntryId,
            postedAt: $this->postedAt,
            scheduleId: $scheduleId,
            reversesRevaluationId: $this->reversesRevaluationId,
            status: $this->status,
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assetId' => $this->assetId,
            'tenantId' => $this->tenantId,
            'revaluationDate' => $this->revaluationDate->format('Y-m-d'),
            'revaluationType' => $this->revaluationType->value,
            'previousBookValue' => $this->previousBookValue->getNetBookValue(),
            'newBookValue' => $this->newBookValue->getNetBookValue(),
            'revaluationAmount' => $this->revaluationAmount->amount,
            'glAccountId' => $this->glAccountId,
            'reason' => $this->reason,
            'status' => $this->status,
            'isPosted' => $this->isPosted(),
            'isReversed' => $this->isReversed(),
            'journalEntryId' => $this->journalEntryId,
            'postedAt' => $this->postedAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
