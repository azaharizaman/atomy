<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Entities;

use Nexus\CostAccounting\Enums\AllocationMethod;

/**
 * Cost Allocation Rule Entity
 * 
 * Defines how costs are allocated from source
 * cost pools to receiving cost centers.
 */
class CostAllocationRule
{
    private string $id;
    private string $costPoolId;
    private string $receivingCostCenterId;
    private float $allocationRatio;
    private AllocationMethod $allocationMethod;
    private ?string $activityDriverId;
    private int $priority;
    private bool $isActive;
    private string $tenantId;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $costPoolId,
        string $receivingCostCenterId,
        float $allocationRatio,
        string $tenantId,
        AllocationMethod $allocationMethod = AllocationMethod::Direct,
        ?string $activityDriverId = null,
        int $priority = 0,
        bool $isActive = true
    ) {
        $this->id = $id;
        $this->costPoolId = $costPoolId;
        $this->receivingCostCenterId = $receivingCostCenterId;
        $this->allocationRatio = $allocationRatio;
        $this->tenantId = $tenantId;
        $this->allocationMethod = $allocationMethod;
        $this->activityDriverId = $activityDriverId;
        $this->priority = $priority;
        $this->isActive = $isActive;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCostPoolId(): string
    {
        return $this->costPoolId;
    }

    public function getReceivingCostCenterId(): string
    {
        return $this->receivingCostCenterId;
    }

    public function getAllocationRatio(): float
    {
        return $this->allocationRatio;
    }

    public function getAllocationMethod(): AllocationMethod
    {
        return $this->allocationMethod;
    }

    public function getActivityDriverId(): ?string
    {
        return $this->activityDriverId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function calculateAllocationAmount(float $poolTotalAmount): float
    {
        return $poolTotalAmount * $this->allocationRatio;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateRatio(float $ratio): void
    {
        if ($ratio < 0.0 || $ratio > 1.0) {
            throw new \InvalidArgumentException(
                'Allocation ratio must be between 0 and 1'
            );
        }
        $this->allocationRatio = $ratio;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatePriority(int $priority): void
    {
        $this->priority = $priority;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setActivityDriver(?string $activityDriverId): void
    {
        $this->activityDriverId = $activityDriverId;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
