<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Defines the structure and operations for an Office entity.
 *
 * Represents a physical or virtual location where business operations occur.
 */
interface OfficeInterface
{
    public function getId(): string;

    public function getCompanyId(): string;

    public function getCode(): string;

    public function getName(): string;

    public function getType(): string;

    public function getStatus(): string;

    public function getParentOfficeId(): ?string;

    public function getAddressLine1(): string;

    public function getAddressLine2(): ?string;

    public function getCity(): string;

    public function getState(): ?string;

    public function getCountry(): string;

    public function getPostalCode(): string;

    public function getPhone(): ?string;

    public function getEmail(): ?string;

    public function getFax(): ?string;

    public function getTimezone(): ?string;

    public function getOperatingHours(): ?string;

    public function getStaffCapacity(): ?int;

    public function getFloorArea(): ?float;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    public function getCreatedAt(): \DateTimeInterface;

    public function getUpdatedAt(): \DateTimeInterface;

    public function isHeadOffice(): bool;

    public function isActive(): bool;
}
