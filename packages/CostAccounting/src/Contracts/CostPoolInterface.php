<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostPool;
use Nexus\CostAccounting\Enums\AllocationMethod;
use Nexus\CostAccounting\Enums\CostPoolStatus;

/**
 * Cost Pool Interface
 * 
 * Defines operations for cost pool management.
 */
interface CostPoolInterface
{
    /**
     * Get the unique identifier
     */
    public function getId(): string;

    /**
     * Get the pool code
     */
    public function getCode(): string;

    /**
     * Get the pool name
     */
    public function getName(): string;

    /**
     * Get the description
     */
    public function getDescription(): ?string;

    /**
     * Get the source cost center ID
     */
    public function getCostCenterId(): string;

    /**
     * Get the total amount in the pool
     */
    public function getTotalAmount(): float;

    /**
     * Get the allocation method
     */
    public function getAllocationMethod(): AllocationMethod;

    /**
     * Get the status
     */
    public function getStatus(): CostPoolStatus;

    /**
     * Get the period ID
     */
    public function getPeriodId(): string;

    /**
     * Get the tenant ID
     */
    public function getTenantId(): string;

    /**
     * Check if pool is active
     */
    public function isActive(): bool;

    /**
     * Update the total amount
     */
    public function withTotalAmount(float $amount): self;

    /**
     * Change allocation method
     */
    public function withAllocationMethod(AllocationMethod $method): self;
}
