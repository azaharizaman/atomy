<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use DateTimeImmutable;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Finance\ValueObjects\Money;

/**
 * Budget transaction entity contract
 * 
 * Represents a single budget transaction (commitment, release, or actual)
 * with full line-item detail and dual-currency tracking.
 */
interface BudgetTransactionInterface
{
    /**
     * Get unique transaction identifier
     */
    public function getId(): string;

    /**
     * Get budget identifier
     */
    public function getBudgetId(): string;

    /**
     * Get transaction type
     */
    public function getTransactionType(): TransactionType;

    /**
     * Get amount in reporting currency
     */
    public function getAmount(): Money;

    /**
     * Get amount in functional currency
     */
    public function getFunctionalAmount(): Money;

    /**
     * Get exchange rate used for conversion
     */
    public function getExchangeRate(): float;

    /**
     * Get source type (e.g., 'purchase_order_line', 'journal_entry_line')
     */
    public function getSourceType(): string;

    /**
     * Get source identifier
     */
    public function getSourceId(): string;

    /**
     * Get source line number
     */
    public function getSourceLineNumber(): ?int;

    /**
     * Get GL account identifier
     */
    public function getAccountId(): string;

    /**
     * Get cost center identifier
     */
    public function getCostCenterId(): ?string;

    /**
     * Get workflow approval identifier (if override was required)
     */
    public function getWorkflowApprovalId(): ?string;

    /**
     * Get transfer source budget identifier (for reallocation transactions)
     */
    public function getTransferFromBudgetId(): ?string;

    /**
     * Get transfer target budget identifier (for reallocation transactions)
     */
    public function getTransferToBudgetId(): ?string;

    /**
     * Get transaction posting timestamp
     */
    public function getPostedAt(): DateTimeImmutable;

    /**
     * Get user who posted the transaction
     */
    public function getPostedBy(): string;

    /**
     * Get transaction description
     */
    public function getDescription(): ?string;

    /**
     * Get transaction metadata
     */
    public function getMetadata(): array;

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): DateTimeImmutable;
}
