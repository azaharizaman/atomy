<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\SecCode;

/**
 * Represents an ACH batch containing multiple entries.
 *
 * A batch groups entries with the same company and SEC code.
 * Each batch has header and control records in the NACHA file.
 */
final class AchBatch
{
    /**
     * @param string $id Unique identifier for the batch
     * @param SecCode $secCode Standard Entry Class code
     * @param string $companyName Company/Originator name (max 16 chars)
     * @param string $companyId Company identification (10 chars)
     * @param string $companyEntryDescription Entry description (max 10 chars)
     * @param RoutingNumber $originatingDfi Originating DFI routing number
     * @param \DateTimeImmutable $effectiveEntryDate Effective date for entries
     * @param array<AchEntry> $entries Array of ACH entries
     * @param string|null $companyDiscretionaryData Optional discretionary data (max 20 chars)
     * @param \DateTimeImmutable|null $companyDescriptiveDate Optional descriptive date
     * @param int $batchNumber Batch number within file
     */
    public function __construct(
        public readonly string $id,
        public readonly SecCode $secCode,
        public readonly string $companyName,
        public readonly string $companyId,
        public readonly string $companyEntryDescription,
        public readonly RoutingNumber $originatingDfi,
        public readonly \DateTimeImmutable $effectiveEntryDate,
        public readonly array $entries = [],
        public readonly ?string $companyDiscretionaryData = null,
        public readonly ?\DateTimeImmutable $companyDescriptiveDate = null,
        public readonly int $batchNumber = 1,
    ) {}

    /**
     * Create a new batch with entries.
     *
     * @param array<AchEntry> $entries
     */
    public static function create(
        string $id,
        SecCode $secCode,
        string $companyName,
        string $companyId,
        string $companyEntryDescription,
        RoutingNumber $originatingDfi,
        \DateTimeImmutable $effectiveEntryDate,
        array $entries = [],
    ): self {
        return new self(
            id: $id,
            secCode: $secCode,
            companyName: $companyName,
            companyId: $companyId,
            companyEntryDescription: $companyEntryDescription,
            originatingDfi: $originatingDfi,
            effectiveEntryDate: $effectiveEntryDate,
            entries: $entries,
        );
    }

    /**
     * Add an entry to the batch.
     */
    public function addEntry(AchEntry $entry): self
    {
        $entries = [...$this->entries, $entry];

        return new self(
            id: $this->id,
            secCode: $this->secCode,
            companyName: $this->companyName,
            companyId: $this->companyId,
            companyEntryDescription: $this->companyEntryDescription,
            originatingDfi: $this->originatingDfi,
            effectiveEntryDate: $this->effectiveEntryDate,
            entries: $entries,
            companyDiscretionaryData: $this->companyDiscretionaryData,
            companyDescriptiveDate: $this->companyDescriptiveDate,
            batchNumber: $this->batchNumber,
        );
    }

    /**
     * Add multiple entries to the batch.
     *
     * @param array<AchEntry> $entries
     */
    public function addEntries(array $entries): self
    {
        return new self(
            id: $this->id,
            secCode: $this->secCode,
            companyName: $this->companyName,
            companyId: $this->companyId,
            companyEntryDescription: $this->companyEntryDescription,
            originatingDfi: $this->originatingDfi,
            effectiveEntryDate: $this->effectiveEntryDate,
            entries: [...$this->entries, ...$entries],
            companyDiscretionaryData: $this->companyDiscretionaryData,
            companyDescriptiveDate: $this->companyDescriptiveDate,
            batchNumber: $this->batchNumber,
        );
    }

    /**
     * Set the batch number.
     */
    public function withBatchNumber(int $batchNumber): self
    {
        return new self(
            id: $this->id,
            secCode: $this->secCode,
            companyName: $this->companyName,
            companyId: $this->companyId,
            companyEntryDescription: $this->companyEntryDescription,
            originatingDfi: $this->originatingDfi,
            effectiveEntryDate: $this->effectiveEntryDate,
            entries: $this->entries,
            companyDiscretionaryData: $this->companyDiscretionaryData,
            companyDescriptiveDate: $this->companyDescriptiveDate,
            batchNumber: $batchNumber,
        );
    }

    /**
     * Get the entry count.
     */
    public function getEntryCount(): int
    {
        return count($this->entries);
    }

    /**
     * Get the addenda count.
     */
    public function getAddendaCount(): int
    {
        return array_reduce(
            $this->entries,
            fn (int $count, AchEntry $entry) => $count + ($entry->hasAddenda() ? 1 : 0),
            0
        );
    }

    /**
     * Get the total debit amount.
     */
    public function getTotalDebits(): Money
    {
        $total = Money::zero('USD');

        foreach ($this->entries as $entry) {
            if ($entry->isDebit()) {
                $total = $total->add($entry->amount);
            }
        }

        return $total;
    }

    /**
     * Get the total credit amount.
     */
    public function getTotalCredits(): Money
    {
        $total = Money::zero('USD');

        foreach ($this->entries as $entry) {
            if ($entry->isCredit()) {
                $total = $total->add($entry->amount);
            }
        }

        return $total;
    }

    /**
     * Get the entry hash (sum of routing number first 8 digits).
     */
    public function getEntryHash(): int
    {
        $hash = 0;

        foreach ($this->entries as $entry) {
            $hash += (int) mb_substr($entry->routingNumber->value, 0, 8);
        }

        // Return only last 10 digits
        return $hash % 10000000000;
    }

    /**
     * Get the formatted company name (max 16 chars).
     */
    public function getFormattedCompanyName(): string
    {
        return str_pad(mb_substr($this->companyName, 0, 16), 16);
    }

    /**
     * Get the formatted company ID (10 chars).
     */
    public function getFormattedCompanyId(): string
    {
        return str_pad(mb_substr($this->companyId, 0, 10), 10);
    }

    /**
     * Get the formatted entry description (10 chars).
     */
    public function getFormattedEntryDescription(): string
    {
        return str_pad(mb_substr($this->companyEntryDescription, 0, 10), 10);
    }

    /**
     * Get the service class code based on batch contents.
     *
     * 200 = Mixed debits and credits
     * 220 = Credits only
     * 225 = Debits only
     */
    public function getServiceClassCode(): int
    {
        $hasDebits = false;
        $hasCredits = false;

        foreach ($this->entries as $entry) {
            if ($entry->isDebit()) {
                $hasDebits = true;
            }
            if ($entry->isCredit()) {
                $hasCredits = true;
            }
        }

        if ($hasDebits && $hasCredits) {
            return 200;
        }

        if ($hasCredits) {
            return 220;
        }

        return 225;
    }

    /**
     * Check if the batch is balanced (debits = credits).
     */
    public function isBalanced(): bool
    {
        return $this->getTotalDebits()->equals($this->getTotalCredits());
    }

    /**
     * Check if the batch has any entries.
     */
    public function hasEntries(): bool
    {
        return $this->getEntryCount() > 0;
    }
}
