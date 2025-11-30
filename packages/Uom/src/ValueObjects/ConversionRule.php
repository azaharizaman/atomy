<?php

declare(strict_types=1);

namespace Nexus\Uom\ValueObjects;

use Nexus\Uom\Contracts\ConversionRuleInterface;

/**
 * Immutable Value Object representing a conversion rule between two units.
 *
 * Requirements: FR-UOM-102, FR-UOM-205, ARC-UOM-0027
 */
final readonly class ConversionRule implements ConversionRuleInterface
{
    /**
     * Create a new ConversionRule.
     *
     * @param string $fromUnit Source unit code
     * @param string $toUnit Target unit code
     * @param float $ratio Multiplication ratio (must be positive non-zero)
     * @param float $offset Addition offset (for temperature conversions)
     * @param bool $isBidirectional Whether inverse conversion is also valid
     */
    public function __construct(
        public string $fromUnit,
        public string $toUnit,
        public float $ratio,
        public float $offset = 0.0,
        public bool $isBidirectional = true
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getFromUnit(): string
    {
        return $this->fromUnit;
    }

    /**
     * {@inheritDoc}
     */
    public function getToUnit(): string
    {
        return $this->toUnit;
    }

    /**
     * {@inheritDoc}
     */
    public function getRatio(): float
    {
        return $this->ratio;
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(): float
    {
        return $this->offset;
    }

    /**
     * {@inheritDoc}
     */
    public function hasOffset(): bool
    {
        return $this->offset !== 0.0;
    }

    /**
     * {@inheritDoc}
     */
    public function isBidirectional(): bool
    {
        return $this->isBidirectional;
    }

    /**
     * Get the inverse conversion rule.
     *
     * @return self New rule for converting back
     * @throws \LogicException If conversion is not bidirectional
     */
    public function inverse(): self
    {
        if (!$this->isBidirectional) {
            throw new \LogicException("Cannot create inverse of one-way conversion");
        }

        // For formula: to = (from * ratio) + offset
        // Inverse: from = (to - offset) / ratio
        $inverseRatio = 1.0 / $this->ratio;
        $inverseOffset = -$this->offset / $this->ratio;

        return new self(
            fromUnit: $this->toUnit,
            toUnit: $this->fromUnit,
            ratio: $inverseRatio,
            offset: $inverseOffset,
            isBidirectional: true
        );
    }
}
