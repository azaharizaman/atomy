<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Journal Entry Line Item contract.
 *
 * Represents a single debit or credit posting to an account.
 * Each line item must have either a debit OR credit amount (not both non-zero).
 *
 * For multi-currency transactions:
 * - Transaction amount is in the original currency
 * - Base amount is converted using the exchange rate at posting time
 *
 * This interface defines the read-only contract for line items.
 */
interface JournalEntryLineInterface
{
    /**
     * Get the unique identifier (ULID).
     */
    public function getId(): string;

    /**
     * Get the parent journal entry ID.
     */
    public function getJournalEntryId(): string;

    /**
     * Get the account ID this line posts to.
     */
    public function getAccountId(): string;

    /**
     * Get the debit amount.
     *
     * Returns Money with zero amount if this is a credit line.
     */
    public function getDebitAmount(): Money;

    /**
     * Get the credit amount.
     *
     * Returns Money with zero amount if this is a debit line.
     */
    public function getCreditAmount(): Money;

    /**
     * Get the debit amount in base currency.
     *
     * For same-currency transactions, equals getDebitAmount().
     */
    public function getBaseDebitAmount(): Money;

    /**
     * Get the credit amount in base currency.
     *
     * For same-currency transactions, equals getCreditAmount().
     */
    public function getBaseCreditAmount(): Money;

    /**
     * Get the transaction currency code.
     */
    public function getCurrency(): string;

    /**
     * Get the exchange rate used for conversion.
     *
     * Returns 1.0 for base currency transactions.
     */
    public function getExchangeRate(): string;

    /**
     * Get the line description/narration.
     */
    public function getDescription(): ?string;

    /**
     * Check if this is a debit line.
     */
    public function isDebit(): bool;

    /**
     * Check if this is a credit line.
     */
    public function isCredit(): bool;

    /**
     * Get optional dimension: cost center ID.
     */
    public function getCostCenterId(): ?string;

    /**
     * Get optional dimension: project ID.
     */
    public function getProjectId(): ?string;

    /**
     * Get optional dimension: department ID.
     */
    public function getDepartmentId(): ?string;

    /**
     * Get optional metadata/dimensions.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get the line item sequence number.
     */
    public function getSequence(): int;
}
