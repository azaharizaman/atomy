<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Contracts;

use DateTimeImmutable;
use Nexus\Finance\Domain\ValueObjects\Money;

/**
 * Journal Entry Line Interface
 * 
 * Represents a single line (debit or credit) in a journal entry.
 */
interface JournalEntryLineInterface
{
    /**
     * Get the line ID
     */
    public function getId(): string;

    /**
     * Get the journal entry ID this line belongs to
     */
    public function getJournalEntryId(): string;

    /**
     * Get the account ID
     */
    public function getAccountId(): string;

    /**
     * Get the debit amount
     */
    public function getDebitAmount(): Money;

    /**
     * Get the credit amount
     */
    public function getCreditAmount(): Money;

    /**
     * Get the line description/memo
     */
    public function getDescription(): ?string;

    /**
     * Check if this is a debit line
     */
    public function isDebit(): bool;

    /**
     * Check if this is a credit line
     */
    public function isCredit(): bool;

    /**
     * Get the net amount (debit - credit)
     */
    public function getNetAmount(): Money;
}
