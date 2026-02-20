<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Enums\CostTransactionType;

/**
 * Cost Transaction Interface
 * 
 * Defines operations for cost transaction management.
 */
interface CostTransactionInterface
{
    /**
     * Get the unique identifier
     */
    public function getId(): string;

    /**
     * Get the cost center ID
     */
    public function getCostCenterId(): string;

    /**
     * Get the cost element ID
     */
    public function getCostElementId(): string;

    /**
     * Get the product ID (if applicable)
     */
    public function getProductId(): ?string;

    /**
     * Get the transaction amount
     */
    public function getAmount(): float;

    /**
     * Get the transaction type
     */
    public function getTransactionType(): CostTransactionType;

    /**
     * Get the period ID
     */
    public function getPeriodId(): string;

    /**
     * Get the tenant ID
     */
    public function getTenantId(): string;

    /**
     * Get the transaction date
     */
    public function getTransactionDate(): \DateTimeImmutable;

    /**
     * Get the quantity (for unit-based transactions)
     */
    public function getQuantity(): ?float;

    /**
     * Get the unit price
     */
    public function getUnitPrice(): ?float;

    /**
     * Check if this is an actual cost transaction
     */
    public function isActual(): bool;

    /**
     * Check if this is a standard cost transaction
     */
    public function isStandard(): bool;
}
