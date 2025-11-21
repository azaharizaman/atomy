<?php

declare(strict_types=1);

namespace Nexus\Assets\ValueObjects;

use JsonSerializable;

/**
 * Asset Tag Value Object
 *
 * Represents a unique asset identifier with tier-based formatting.
 * Tier 1: Sequential (ASSET-2025-0001)
 * Tier 3: UUID or Barcode format
 */
final readonly class AssetTag implements JsonSerializable
{
    public function __construct(
        public string $value
    ) {
        $this->validate();
    }

    /**
     * Validate asset tag
     */
    private function validate(): void
    {
        if (trim($this->value) === '') {
            throw new \InvalidArgumentException('Asset tag cannot be empty');
        }

        if (strlen($this->value) > 50) {
            throw new \InvalidArgumentException('Asset tag cannot exceed 50 characters');
        }
    }

    /**
     * Create from sequential number
     */
    public static function fromSequence(int $sequence, int $year): self
    {
        return new self(sprintf('ASSET-%d-%04d', $year, $sequence));
    }

    /**
     * Create from string
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Get string representation
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * JSON serialization
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * Check equality
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
