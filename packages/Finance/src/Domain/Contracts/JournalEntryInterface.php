<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Contracts;

use DateTimeImmutable;

/**
 * Journal Entry Interface
 * 
 * Represents a complete journal entry with multiple lines (debits and credits).
 */
interface JournalEntryInterface
{
    /**
     * Get the unique identifier
     */
    public function getId(): string;

    /**
     * Get the entry number (e.g., "JE-2024-001")
     */
    public function getEntryNumber(): string;

    /**
     * Get the entry date
     */
    public function getDate(): DateTimeImmutable;

    /**
     * Get the reference number (optional external reference)
     */
    public function getReference(): ?string;

    /**
     * Get the entry description
     */
    public function getDescription(): string;

    /**
     * Get the status (Draft, Posted, Reversed)
     */
    public function getStatus(): string;

    /**
     * Get all entry lines
     * 
     * @return array<JournalEntryLineInterface>
     */
    public function getLines(): array;

    /**
     * Get the total debit amount
     */
    public function getTotalDebit(): string;

    /**
     * Get the total credit amount
     */
    public function getTotalCredit(): string;

    /**
     * Check if the entry is balanced (debits = credits)
     */
    public function isBalanced(): bool;

    /**
     * Check if the entry is posted
     */
    public function isPosted(): bool;

    /**
     * Get the user who created this entry
     */
    public function getCreatedBy(): string;

    /**
     * Get when this entry was posted (null if not posted)
     */
    public function getPostedAt(): ?DateTimeImmutable;

    /**
     * Get when this entry was created
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get when this entry was last updated
     */
    public function getUpdatedAt(): DateTimeImmutable;
}
