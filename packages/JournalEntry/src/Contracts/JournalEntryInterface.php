<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\JournalEntry\Enums\JournalEntryStatus;

/**
 * Journal Entry entity contract.
 *
 * Represents a complete journal entry with multiple line items.
 * Journal entries follow double-entry bookkeeping principles where
 * total debits must equal total credits.
 *
 * Status Lifecycle:
 * - Draft: Created but not yet posted
 * - Posted: Finalized and immutable
 * - Reversed: Has been reversed with offsetting entry
 *
 * This interface defines the read-only contract for journal entries.
 * Creation and modification are handled by the manager/repository.
 */
interface JournalEntryInterface
{
    /**
     * Get the unique identifier (ULID).
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;

    /**
     * Get the journal entry number.
     *
     * Format varies by configuration (e.g., "JE-2024-001234").
     */
    public function getNumber(): string;

    /**
     * Get the posting date.
     *
     * This is the date the entry affects the general ledger.
     */
    public function getPostingDate(): \DateTimeImmutable;

    /**
     * Get the entry description/narration.
     */
    public function getDescription(): string;

    /**
     * Get the external reference (invoice number, document ID, etc.).
     */
    public function getReference(): ?string;

    /**
     * Get the current status.
     */
    public function getStatus(): JournalEntryStatus;

    /**
     * Get all line items.
     *
     * @return array<JournalEntryLineInterface>
     */
    public function getLines(): array;

    /**
     * Get the total of all debit amounts.
     */
    public function getTotalDebit(): Money;

    /**
     * Get the total of all credit amounts.
     */
    public function getTotalCredit(): Money;

    /**
     * Check if the entry is balanced (debits = credits).
     */
    public function isBalanced(): bool;

    /**
     * Get the fiscal period identifier.
     */
    public function getPeriodId(): ?string;

    /**
     * Get the source system identifier (for auto-generated entries).
     */
    public function getSourceSystem(): ?string;

    /**
     * Get the source document identifier.
     */
    public function getSourceDocumentId(): ?string;

    /**
     * Get the user who created this entry.
     */
    public function getCreatedBy(): ?string;

    /**
     * Get the user who posted this entry.
     */
    public function getPostedBy(): ?string;

    /**
     * Get the date/time when this entry was posted.
     */
    public function getPostedAt(): ?\DateTimeImmutable;

    /**
     * Get the ID of the entry this reverses (if this is a reversal entry).
     */
    public function getReversalOfId(): ?string;

    /**
     * Get the ID of the entry that reversed this (if this was reversed).
     */
    public function getReversedById(): ?string;

    /**
     * Check if this entry is a reversal entry.
     */
    public function isReversal(): bool;

    /**
     * Check if this entry has been reversed.
     */
    public function isReversed(): bool;

    /**
     * Get optional metadata/dimensions.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable;
}
