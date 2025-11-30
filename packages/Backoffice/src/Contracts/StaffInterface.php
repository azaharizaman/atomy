<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Defines the structure and operations for a Staff entity.
 *
 * Represents an employee or worker within the organization.
 */
interface StaffInterface
{
    public function getId(): string;

    public function getEmployeeId(): string;

    public function getStaffCode(): ?string;

    public function getFirstName(): string;

    public function getLastName(): string;

    public function getMiddleName(): ?string;

    public function getFullName(): string;

    public function getEmail(): ?string;

    public function getPhone(): ?string;

    public function getMobile(): ?string;

    public function getEmergencyContact(): ?string;

    public function getEmergencyPhone(): ?string;

    public function getType(): string;

    public function getStatus(): string;

    public function getHireDate(): \DateTimeInterface;

    public function getTerminationDate(): ?\DateTimeInterface;

    public function getPosition(): ?string;

    public function getGrade(): ?string;

    public function getSalaryBand(): ?string;

    public function getProbationEndDate(): ?\DateTimeInterface;

    public function getConfirmationDate(): ?\DateTimeInterface;

    public function getPhotoUrl(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    public function getCreatedAt(): \DateTimeInterface;

    public function getUpdatedAt(): \DateTimeInterface;

    public function isActive(): bool;

    public function isTerminated(): bool;
}
