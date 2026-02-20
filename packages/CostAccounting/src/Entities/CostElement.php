<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Entities;

use Nexus\CostAccounting\Enums\CostElementType;

/**
 * Cost Element Entity
 * 
 * Categorizes costs by type (material, labor, overhead)
 * for tracking and allocation.
 */
class CostElement
{
    private string $id;
    private string $code;
    private string $name;
    private CostElementType $type;
    private string $costCenterId;
    private ?string $glAccountId;
    private string $tenantId;
    private bool $isActive;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $code,
        string $name,
        CostElementType $type,
        string $costCenterId,
        string $tenantId,
        ?string $glAccountId = null,
        bool $isActive = true
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->type = $type;
        $this->costCenterId = $costCenterId;
        $this->glAccountId = $glAccountId;
        $this->tenantId = $tenantId;
        $this->isActive = $isActive;
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

    public function getType(): CostElementType
    {
        return $this->type;
    }

    public function getCostCenterId(): string
    {
        return $this->costCenterId;
    }

    public function getGlAccountId(): ?string
    {
        return $this->glAccountId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isMaterial(): bool
    {
        return $this->type === CostElementType::Material;
    }

    public function isLabor(): bool
    {
        return $this->type === CostElementType::Labor;
    }

    public function isOverhead(): bool
    {
        return $this->type === CostElementType::Overhead;
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

    public function linkGLAccount(string $glAccountId): void
    {
        $this->glAccountId = $glAccountId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update(
        string $name,
        ?string $glAccountId = null
    ): void {
        $this->name = $name;
        $this->glAccountId = $glAccountId ?? $this->glAccountId;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
