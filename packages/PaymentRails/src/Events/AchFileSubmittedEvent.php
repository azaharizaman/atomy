<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Events;

use Nexus\PaymentRails\Enums\FileStatus;
use Nexus\PaymentRails\Enums\RailType;

/**
 * Event dispatched when an ACH file is submitted.
 */
final class AchFileSubmittedEvent extends PaymentRailEvent
{
    /**
     * @param string $transactionId
     * @param string $tenantId
     * @param string $fileId
     * @param int $batchCount
     * @param int $entryCount
     * @param int $totalDebitCents
     * @param int $totalCreditCents
     * @param \DateTimeImmutable $effectiveDate
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $transactionId,
        string $tenantId,
        public readonly string $fileId,
        public readonly int $batchCount,
        public readonly int $entryCount,
        public readonly int $totalDebitCents,
        public readonly int $totalCreditCents,
        public readonly \DateTimeImmutable $effectiveDate,
        array $metadata = [],
    ) {
        parent::__construct($transactionId, RailType::ACH, $tenantId, $metadata);
    }

    public function getEventName(): string
    {
        return 'ach.file.submitted';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'file_id' => $this->fileId,
            'batch_count' => $this->batchCount,
            'entry_count' => $this->entryCount,
            'total_debit_cents' => $this->totalDebitCents,
            'total_credit_cents' => $this->totalCreditCents,
            'effective_date' => $this->effectiveDate->format('Y-m-d'),
        ]);
    }
}
