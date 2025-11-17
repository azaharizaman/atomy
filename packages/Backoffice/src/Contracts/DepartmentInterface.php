<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Defines the structure and operations for a Department entity.
 *
 * Represents a functional organizational unit within a company.
 */
interface DepartmentInterface
{
    public function getId(): string;

    public function getCompanyId(): string;

    public function getCode(): string;

    public function getName(): string;

    public function getType(): string;

    public function getStatus(): string;

    public function getParentDepartmentId(): ?string;

    public function getManagerStaffId(): ?string;

    public function getCostCenter(): ?string;

    public function getBudgetAmount(): ?float;

    public function getDescription(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    public function getCreatedAt(): \DateTimeInterface;

    public function getUpdatedAt(): \DateTimeInterface;

    public function isActive(): bool;
}
