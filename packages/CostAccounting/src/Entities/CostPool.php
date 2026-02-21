<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Entities;

use Nexus\CostAccounting\Enums\AllocationMethod;
use Nexus\CostAccounting\Enums\CostPoolStatus;

/**
 * Cost Pool Entity
 * 
 * Aggregates indirect costs for allocation to
 * receiving cost centers.
 */
readonly class CostPool
{
    private string $id;
    private string $code;
    private string $name;
    private ?string $description;
    private string $costCenterId;
    private float $totalAmount;
    private AllocationMethod $allocationMethod;
    private CostPoolStatus $status;
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
        CostPoolStatus $status = CostPoolStatus::Active,
        array $allocationRules = [],
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null
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
        $this->allocationRules = $allocationRules;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
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

    public function getStatus(): CostPoolStatus
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
        return $this->status === CostPoolStatus::Active;
    }

    public function withAllocationRule(CostAllocationRule $rule): self
    {
        return new self(
            $this->id,
            $this->code,
            $this->name,
            $this->costCenterId,
            $this->periodId,
            $this->tenantId,
            $this->allocationMethod,
            $this->totalAmount,
            $this->description,
            $this->status,
            [...$this->allocationRules, $rule],
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function withoutAllocationRule(string $ruleId): self
    {
        $filteredRules = array_values(array_filter(
            $this->allocationRules,
            fn($rule) => $rule->getId() !== $ruleId
        ));

        return new self(
            $this->id,
            $this->code,
            $this->name,
            $this->costCenterId,
            $this->periodId,
            $this->tenantId,
            $this->allocationMethod,
            $this->totalAmount,
            $this->description,
            $this->status,
            $filteredRules,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function withTotalAmount(float $amount): self
    {
        return new self(
            $this->id,
            $this->code,
            $this->name,
            $this->costCenterId,
            $this->periodId,
            $this->tenantId,
            $this->allocationMethod,
            $amount,
            $this->description,
            $this->status,
            $this->allocationRules,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function withAllocationMethod(AllocationMethod $method): self
    {
        return new self(
            $this->id,
            $this->code,
            $this->name,
            $this->costCenterId,
            $this->periodId,
            $this->tenantId,
            $method,
            $this->totalAmount,
            $this->description,
            $this->status,
            $this->allocationRules,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function withStatus(CostPoolStatus $status): self
    {
        return new self(
            $this->id,
            $this->code,
            $this->name,
            $this->costCenterId,
            $this->periodId,
            $this->tenantId,
            $this->allocationMethod,
            $this->totalAmount,
            $this->description,
            $status,
            $this->allocationRules,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }
}
