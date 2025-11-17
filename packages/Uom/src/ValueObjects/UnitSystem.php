<?php

declare(strict_types=1);

namespace Nexus\Uom\ValueObjects;

use Nexus\Uom\Contracts\UnitSystemInterface;

/**
 * Immutable Value Object representing a unit system.
 *
 * Requirements: FR-UOM-203, ARC-UOM-0027
 */
final readonly class UnitSystem implements UnitSystemInterface
{
    /**
     * Create a new UnitSystem.
     *
     * @param string $code Unique system code (e.g., 'metric', 'imperial')
     * @param string $name Human-readable name (e.g., 'Metric System')
     * @param string|null $description Optional description
     * @param bool $isSystemDefined Whether this is a system-defined system
     */
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null,
        public bool $isSystemDefined = true
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
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function isSystemDefined(): bool
    {
        return $this->isSystemDefined;
    }
}
