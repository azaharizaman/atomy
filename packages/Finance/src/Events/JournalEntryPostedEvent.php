<?php

declare(strict_types=1);

namespace Nexus\Finance\Events;

use DateTimeImmutable;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\Finance\ValueObjects\JournalEntryNumber;
use Nexus\Finance\ValueObjects\Money;
use Symfony\Component\Uid\Ulid;

/**
 * Journal Entry Posted Event
 * 
 * Published when a journal entry is posted to the general ledger.
 * This event provides immutable audit trail for SOX Section 404 compliance.
 */
final readonly class JournalEntryPostedEvent implements EventInterface
{
    private string $eventId;

    public function __construct(
        public string $journalEntryId,
        public JournalEntryNumber $entryNumber,
        public DateTimeImmutable $entryDate,
        public string $description,
        public Money $totalDebit,
        public Money $totalCredit,
        public DateTimeImmutable $postedAt,
        public string $postedBy,
        public string $tenantId,
        public int $version = 1,
        public ?string $causationId = null,
        public ?string $correlationId = null,
        public array $metadata = []
    ) {
        $this->eventId = (string) new Ulid();
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getAggregateId(): string
    {
        return $this->journalEntryId;
    }

    public function getEventType(): string
    {
        return self::class;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->postedAt;
    }

    public function getPayload(): array
    {
        return [
            'entry_number' => $this->entryNumber->getValue(),
            'entry_date' => $this->entryDate->format('Y-m-d'),
            'description' => $this->description,
            'total_debit' => [
                'amount' => $this->totalDebit->getAmount(),
                'currency' => $this->totalDebit->getCurrency()
            ],
            'total_credit' => [
                'amount' => $this->totalCredit->getAmount(),
                'currency' => $this->totalCredit->getCurrency()
            ],
            'posted_by' => $this->postedBy
        ];
    }

    public function getCausationId(): ?string
    {
        return $this->causationId;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getUserId(): ?string
    {
        return $this->postedBy;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
