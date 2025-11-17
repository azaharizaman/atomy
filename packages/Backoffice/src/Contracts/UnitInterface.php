<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Defines the structure and operations for a Unit entity.
 *
 * Represents a cross-functional organizational unit that transcends
 * traditional hierarchy boundaries (e.g., project teams, committees).
 */
interface UnitInterface
{
    public function getId(): string;

    public function getCompanyId(): string;

    public function getCode(): string;

    public function getName(): string;

    public function getType(): string;

    public function getStatus(): string;

    public function getLeaderStaffId(): ?string;

    public function getDeputyLeaderStaffId(): ?string;

    public function getPurpose(): ?string;

    public function getObjectives(): ?string;

    public function getStartDate(): ?\DateTimeInterface;

    public function getEndDate(): ?\DateTimeInterface;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    public function getCreatedAt(): \DateTimeInterface;

    public function getUpdatedAt(): \DateTimeInterface;

    public function isActive(): bool;

    public function isTemporary(): bool;
}
