<?php

declare(strict_types=1);

namespace Nexus\Uom\ValueObjects;

use Nexus\Uom\Contracts\DimensionInterface;

/**
 * Immutable Value Object representing a measurement dimension.
 *
 * Requirements: FR-UOM-201, FR-UOM-204, ARC-UOM-0027
 */
final readonly class Dimension implements DimensionInterface
{
    /**
     * Create a new Dimension.
     *
     * @param string $code Unique dimension code (e.g., 'mass', 'length')
     * @param string $name Human-readable name (e.g., 'Mass', 'Length')
     * @param string $baseUnit Base unit code for this dimension (e.g., 'kg', 'm')
     * @param bool $allowsOffset Whether this dimension allows offset conversions
     * @param string|null $description Optional description
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $baseUnit,
        public bool $allowsOffset = false,
        public ?string $description = null
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUnit(): string
    {
        return $this->baseUnit;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function allowsOffset(): bool
    {
        return $this->allowsOffset;
    }
}
