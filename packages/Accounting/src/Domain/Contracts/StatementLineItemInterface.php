<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

/**
 * Interface for individual line items within financial statements.
 *
 * Represents a single row in a financial statement with its
 * account information, amounts, and categorization.
 */
interface StatementLineItemInterface
{
    /**
     * Get the unique identifier for this line item.
     */
    public function getId(): string;

    /**
     * Get the account code this line item relates to.
     */
    public function getAccountCode(): string;

    /**
     * Get the account name/description.
     */
    public function getAccountName(): string;

    /**
     * Get the monetary amount for this line item.
     */
    public function getAmount(): float;

    /**
     * Get the category/section this line item belongs to.
     */
    public function getCategory(): string;

    /**
     * Get the display order for this line item.
     */
    public function getDisplayOrder(): int;

    /**
     * Get the indentation level for hierarchical display.
     */
    public function getIndentLevel(): int;

    /**
     * Check if this is a subtotal/total row.
     */
    public function isSubtotal(): bool;

    /**
     * Get the parent line item ID if this is a child item.
     */
    public function getParentId(): ?string;

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
