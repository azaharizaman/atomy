<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for BOM line/component entity.
 *
 * Represents a single component in a Bill of Materials.
 */
interface BomLineInterface
{
    /**
     * Get the unique identifier for this BOM line.
     */
    public function getId(): string;

    /**
     * Get the parent BOM ID.
     */
    public function getBomId(): string;

    /**
     * Get the line number/sequence.
     */
    public function getLineNumber(): int;

    /**
     * Get the component product ID.
     */
    public function getComponentProductId(): string;

    /**
     * Get the quantity required per parent output unit.
     */
    public function getQuantityPer(): float;

    /**
     * Get the unit of measure for this component.
     */
    public function getUom(): string;

    /**
     * Get the scrap percentage (0-100).
     */
    public function getScrapPercentage(): float;

    /**
     * Check if this is a phantom component (inline explosion).
     */
    public function isPhantom(): bool;

    /**
     * Get the operation number where this component is consumed.
     * Null means consumed at start.
     */
    public function getOperationNumber(): ?int;

    /**
     * Get the date this line becomes effective.
     */
    public function getEffectiveFrom(): ?\DateTimeImmutable;

    /**
     * Get the date this line expires.
     */
    public function getEffectiveTo(): ?\DateTimeImmutable;

    /**
     * Check if this line is effective at given date.
     */
    public function isEffective(\DateTimeImmutable $asOf): bool;

    /**
     * Get optional notes for this line.
     */
    public function getNotes(): ?string;
}
