<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Entities;

use Nexus\CostAccounting\Enums\AllocationMethod;

/**
 * Cost Pool Entity
 * 
 * Aggregates indirect costs for allocation to
 * receiving cost centers.
 */
class CostPool
{
    private string $id;
    private string $code;
    private string $name;
    private ?string $description;
    private string $costCenterId;
    private float $totalAmount;
    private AllocationMethod $allocationMethod;
    private string $status;
    private string $periodId;
    private string $tenantId;
    private array $allocationRules;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $code,
        string $name,
        string $costCenterId,
        string $periodId,
        string $tenantId,
        AllocationMethod $allocationMethod,
        float $totalAmount = 0.0,
        ?string $description = null,
        string $status = 'active'
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->costCenterId = $costCenterId;
        $this->periodId = $periodId;
        $this->tenantId = $tenantId;
        $this->allocationMethod = $allocationMethod;
        $this->totalAmount = $totalAmount;
        $this->description = $description;
        $this->status = $status;
        $this->allocationRules = [];
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCostCenterId(): string
    {
        return $this->costCenterId;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getAllocationMethod(): AllocationMethod
    {
        return $this->allocationMethod;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getAllocationRules(): array
    {
        return $this->allocationRules;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function addAllocationRule(CostAllocationRule $rule): void
    {
        $this->allocationRules[] = $rule;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeAllocationRule(string $ruleId): void
    {
        $this->allocationRules = array_filter(
            $this->allocationRules,
            fn($rule) => $rule->getId() !== $ruleId
        );
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateAmount(float $amount): void
    {
        $this->totalAmount = $amount;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeAllocationMethod(AllocationMethod $method): void
    {
        $this->allocationMethod = $method;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
