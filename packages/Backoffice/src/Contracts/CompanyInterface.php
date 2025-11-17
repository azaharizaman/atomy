<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Defines the structure and operations for a Company entity.
 *
 * Represents a legal business entity within the organizational structure.
 * Companies can form hierarchical relationships (parent-subsidiary).
 */
interface CompanyInterface
{
    /**
     * Get the unique identifier for the company.
     */
    public function getId(): string;

    /**
     * Get the unique company code.
     */
    public function getCode(): string;

    /**
     * Get the company name.
     */
    public function getName(): string;

    /**
     * Get the company registration number.
     */
    public function getRegistrationNumber(): ?string;

    /**
     * Get the company registration date.
     */
    public function getRegistrationDate(): ?\DateTimeInterface;

    /**
     * Get the registration jurisdiction.
     */
    public function getJurisdiction(): ?string;

    /**
     * Get the company status.
     */
    public function getStatus(): string;

    /**
     * Get the parent company ID.
     */
    public function getParentCompanyId(): ?string;

    /**
     * Get the financial year start month (1-12).
     */
    public function getFinancialYearStartMonth(): ?int;

    /**
     * Get the company industry.
     */
    public function getIndustry(): ?string;

    /**
     * Get the company size category.
     */
    public function getSize(): ?string;

    /**
     * Get the tax identification number.
     */
    public function getTaxId(): ?string;

    /**
     * Get additional metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get the last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeInterface;

    /**
     * Check if the company is active.
     */
    public function isActive(): bool;
}
