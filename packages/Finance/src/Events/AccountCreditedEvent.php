<?php

declare(strict_types=1);

namespace Nexus\Finance\Events;

use DateTimeImmutable;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\Finance\ValueObjects\AccountCode;
use Nexus\Finance\ValueObjects\Money;
use Nexus\Finance\ValueObjects\JournalEntryNumber;
use Symfony\Component\Uid\Ulid;

/**
 * Account Credited Event
 * 
 * Published when an account is credited as part of a journal entry.
 * Enables temporal queries and event replay for compliance audits.
 */
final readonly class AccountCreditedEvent implements EventInterface
{
    private string $eventId;

    public function __construct(
        public string $accountId,
        public AccountCode $accountCode,
        public Money $amount,
        public string $journalEntryId,
        public JournalEntryNumber $entryNumber,
        public DateTimeImmutable $occurredAt,
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
        return $this->accountId;
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
            'account_code' => $this->accountCode->getValue(),
            'amount' => [
                'amount' => $this->amount->getAmount(),
                'currency' => $this->amount->getCurrency()
            ],
            'journal_entry_id' => $this->journalEntryId,
            'entry_number' => $this->entryNumber->getValue()
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
        return null;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
