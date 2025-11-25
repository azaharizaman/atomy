<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for Bill of Materials entity.
 *
 * Represents a BOM with its components, version, and effectivity dates.
 */
interface BomInterface
{
    /**
     * Get the unique identifier for this BOM.
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;

    /**
     * Get the product ID this BOM is for.
     */
    public function getProductId(): string;

    /**
     * Get the BOM version number.
     */
    public function getVersion(): int;

    /**
     * Get the BOM type (standard, phantom, configurable).
     */
    public function getType(): string;

    /**
     * Get the BOM name/description.
     */
    public function getName(): string;

    /**
     * Get the quantity this BOM produces.
     */
    public function getOutputQuantity(): float;

    /**
     * Get the unit of measure for output.
     */
    public function getOutputUom(): string;

    /**
     * Get all BOM lines/components.
     *
     * @return array<BomLineInterface>
     */
    public function getLines(): array;

    /**
     * Get the date this BOM version becomes effective.
     */
    public function getEffectiveFrom(): ?\DateTimeImmutable;

    /**
     * Get the date this BOM version expires.
     */
    public function getEffectiveTo(): ?\DateTimeImmutable;

    /**
     * Check if this BOM is currently effective.
     */
    public function isEffective(\DateTimeImmutable $asOf): bool;

    /**
     * Check if this is the latest version.
     */
    public function isLatestVersion(): bool;

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable;

    /**
     * Get the current status (draft, released, obsolete).
     */
    public function getStatus(): string;
}
