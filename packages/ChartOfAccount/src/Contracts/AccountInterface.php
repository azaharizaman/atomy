<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Contracts;

use DateTimeImmutable;
use Nexus\ChartOfAccount\Enums\AccountType;

/**
 * Defines the contract for a Chart of Accounts entry.
 *
 * An account represents a ledger category for recording financial transactions.
 * Accounts are organized in a hierarchical structure with header (group) accounts
 * containing postable (leaf) accounts.
 */
interface AccountInterface
{
    /**
     * Get the unique identifier (ULID).
     */
    public function getId(): string;

    /**
     * Get the account code.
     *
     * Account codes are alphanumeric identifiers that typically follow
     * a hierarchical pattern (e.g., '1000', '1000-001', '1000.001').
     */
    public function getCode(): string;

    /**
     * Get the account name.
     */
    public function getName(): string;

    /**
     * Get the account type.
     *
     * Determines normal balance (debit/credit) and financial statement classification.
     */
    public function getType(): AccountType;

    /**
     * Get the currency code (ISO 4217).
     *
     * Accounts may be currency-specific for multi-currency support.
     * Returns null for accounts that accept any currency.
     */
    public function getCurrency(): ?string;

    /**
     * Get the parent account ID.
     *
     * Returns null for top-level accounts.
     */
    public function getParentId(): ?string;

    /**
     * Check if this is a header (group) account.
     *
     * Header accounts cannot have transactions posted directly to them.
     * They serve as groupings for child accounts.
     */
    public function isHeader(): bool;

    /**
     * Check if the account can have transactions posted to it.
     *
     * Only non-header (leaf) accounts can be posted to.
     */
    public function isPostable(): bool;

    /**
     * Check if the account is active.
     *
     * Inactive accounts cannot receive new transactions but maintain
     * their historical data.
     */
    public function isActive(): bool;

    /**
     * Get the account description.
     */
    public function getDescription(): ?string;

    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get the last update timestamp.
     */
    public function getUpdatedAt(): DateTimeImmutable;
}
