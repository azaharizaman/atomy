<?php

declare(strict_types=1);

namespace Nexus\Uom\ValueObjects;

use Nexus\Uom\Contracts\UnitInterface;

/**
 * Immutable Value Object representing a unit of measurement.
 *
 * Requirements: ARC-UOM-0027, BUS-UOM-105
 */
final readonly class Unit implements UnitInterface
{
    /**
     * Create a new Unit.
     *
     * @param string $code Unique unit code (e.g., 'kg', 'm', 'lb')
     * @param string $name Human-readable name (e.g., 'Kilogram', 'Meter')
     * @param string $symbol Display symbol (e.g., 'kg', 'm')
     * @param string $dimension Dimension code this unit belongs to
     * @param string|null $system Unit system code (e.g., 'metric', 'imperial')
     * @param bool $isBaseUnit Whether this is the base unit for its dimension
     * @param bool $isSystemUnit Whether this is a system-defined (protected) unit
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
        public string $dimension,
        public ?string $system = null,
        public bool $isBaseUnit = false,
        public bool $isSystemUnit = true
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
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * {@inheritDoc}
     */
    public function getDimension(): string
    {
        return $this->dimension;
    }

    /**
     * {@inheritDoc}
     */
    public function getSystem(): ?string
    {
        return $this->system;
    }

    /**
     * {@inheritDoc}
     */
    public function isBaseUnit(): bool
    {
        return $this->isBaseUnit;
    }

    /**
     * {@inheritDoc}
     */
    public function isSystemUnit(): bool
    {
        return $this->isSystemUnit;
    }
}
