<?php

declare(strict_types=1);

namespace Nexus\Audit\ValueObjects;

/**
 * Sequence Number Value Object
 * 
 * Immutable container for tenant-scoped sequence numbers.
 * Ensures monotonic ordering and gap detection.
 * 
 * Satisfies: REL-AUD-0301 (Log sequence integrity)
 */
final readonly class SequenceNumber
{
    public function __construct(
        public int $value,
        public string $tenantId
    ) {
        if ($value < 1) {
            throw new \InvalidArgumentException('Sequence number must be positive');
        }

        if (empty($tenantId)) {
            throw new \InvalidArgumentException('Tenant ID cannot be empty');
        }
    }

    /**
     * Create first sequence number for tenant
     */
    public static function first(string $tenantId): self
    {
        return new self(1, $tenantId);
    }

    /**
     * Get next sequence number
     */
    public function next(): self
    {
        return new self($this->value + 1, $this->tenantId);
    }

    /**
     * Check if this is the first sequence
     */
    public function isFirst(): bool
    {
        return $this->value === 1;
    }

    /**
     * Calculate gap from another sequence
     */
    public function gapFrom(self $other): int
    {
        if ($this->tenantId !== $other->tenantId) {
            throw new \InvalidArgumentException('Cannot calculate gap across different tenants');
        }

        return abs($this->value - $other->value) - 1;
    }

    /**
     * Check if consecutive to another sequence
     */
    public function isConsecutiveTo(self $other): bool
    {
        return $this->tenantId === $other->tenantId 
            && $this->value === $other->value + 1;
    }

    /**
     * Compare with another sequence
     */
    public function isGreaterThan(self $other): bool
    {
        if ($this->tenantId !== $other->tenantId) {
            throw new \InvalidArgumentException('Cannot compare sequences from different tenants');
        }

        return $this->value > $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
