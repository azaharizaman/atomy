<?php

declare(strict_types=1);

namespace Nexus\Finance\Events;

use DateTimeImmutable;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\Finance\ValueObjects\JournalEntryNumber;
use Symfony\Component\Uid\Ulid;

/**
 * Journal Entry Reversed Event
 * 
 * Published when a posted journal entry is reversed.
 * Maintains immutable audit trail for compliance and creates reversal entry.
 */
final readonly class JournalEntryReversedEvent implements EventInterface
{
    private string $eventId;

    public function __construct(
        public string $originalJournalEntryId,
        public JournalEntryNumber $originalEntryNumber,
        public string $reversalJournalEntryId,
        public JournalEntryNumber $reversalEntryNumber,
        public DateTimeImmutable $reversalDate,
        public string $reason,
        public string $reversedBy,
        public string $tenantId,
        public DateTimeImmutable $occurredAt,
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
        return $this->originalJournalEntryId;
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
        return $this->occurredAt;
    }

    public function getPayload(): array
    {
        return [
            'original_entry_number' => $this->originalEntryNumber->getValue(),
            'reversal_entry_id' => $this->reversalJournalEntryId,
            'reversal_entry_number' => $this->reversalEntryNumber->getValue(),
            'reversal_date' => $this->reversalDate->format('Y-m-d'),
            'reason' => $this->reason,
            'reversed_by' => $this->reversedBy
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
        return $this->reversedBy;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
