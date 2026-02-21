<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Entities;

use Nexus\CostAccounting\Enums\CostCenterStatus;

/**
 * Cost Center Entity
 * 
 * Represents an organizational unit for cost collection
 * and responsibility tracking.
 */
class CostCenter
{
    private string $id;
    private string $code;
    private string $name;
    private ?string $description;
    private ?string $parentCostCenterId;
    private CostCenterStatus $status;
    private string $tenantId;
    private ?string $costCenterType;
    private ?string $budgetId;
    private ?string $responsiblePersonId;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $code,
        string $name,
        string $tenantId,
        CostCenterStatus $status,
        ?string $description = null,
        ?string $parentCostCenterId = null,
        ?string $costCenterType = null,
        ?string $budgetId = null,
        ?string $responsiblePersonId = null
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->tenantId = $tenantId;
        $this->status = $status;
        $this->description = $description;
        $this->parentCostCenterId = $parentCostCenterId;
        $this->costCenterType = $costCenterType;
        $this->budgetId = $budgetId;
        $this->responsiblePersonId = $responsiblePersonId;
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

    public function getParentCostCenterId(): ?string
    {
        return $this->parentCostCenterId;
    }

    public function getStatus(): CostCenterStatus
    {
        return $this->status;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getCostCenterType(): ?string
    {
        return $this->costCenterType;
    }

    public function getBudgetId(): ?string
    {
        return $this->budgetId;
    }

    public function getResponsiblePersonId(): ?string
    {
        return $this->responsiblePersonId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasParent(): bool
    {
        return $this->parentCostCenterId !== null;
    }

    public function isActive(): bool
    {
        return $this->status === CostCenterStatus::Active;
    }

    public function isRoot(): bool
    {
        return $this->parentCostCenterId === null;
    }

    public function changeStatus(CostCenterStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function assignParent(?string $parentCostCenterId): void
    {
        $this->parentCostCenterId = $parentCostCenterId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function linkBudget(string $budgetId): void
    {
        $this->budgetId = $budgetId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update(
        string $name,
        ?string $description = null,
        ?string $costCenterType = null,
        ?string $responsiblePersonId = null
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->costCenterType = $costCenterType;
        $this->responsiblePersonId = $responsiblePersonId;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
