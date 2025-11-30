<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Contracts;

use DateTimeImmutable;

/**
 * Account Interface
 * 
 * Represents a general ledger account in the chart of accounts.
 */
interface AccountInterface
{
    /**
     * Get the unique identifier
     */
    public function getId(): string;

    /**
     * Get the account code (e.g., "1000", "2100-001")
     */
    public function getCode(): string;

    /**
     * Get the account name
     */
    public function getName(): string;

    /**
     * Get the account type (Asset, Liability, Equity, Revenue, Expense)
     */
    public function getType(): string;

    /**
     * Get the currency code
     */
    public function getCurrency(): string;

    /**
     * Get the parent account ID (null for root accounts)
     */
    public function getParentId(): ?string;

    /**
     * Check if this is a header/group account (not postable)
     */
    public function isHeader(): bool;

    /**
     * Check if this account is active
     */
    public function isActive(): bool;

    /**
     * Get the account description
     */
    public function getDescription(): ?string;

    /**
     * Get when this account was created
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get when this account was last updated
     */
    public function getUpdatedAt(): DateTimeImmutable;
}
