<?php

declare(strict_types=1);

namespace Nexus\Receivable\ValueObjects;

use InvalidArgumentException;

/**
 * Aging Bucket Value Object
 *
 * Represents a time range bucket for Accounts Receivable aging reports.
 * Immutable and validated on construction.
 */
final readonly class AgingBucket
{
    public function __construct(
        private string $label,
        private int $minDays,
        private int $maxDays
    ) {
        if (empty(trim($label))) {
            throw new InvalidArgumentException('Aging bucket label cannot be empty');
        }

        if ($minDays < 0) {
            throw new InvalidArgumentException('Minimum days cannot be negative');
        }

        if ($maxDays < $minDays && $maxDays !== -1) {
            throw new InvalidArgumentException('Maximum days must be greater than or equal to minimum days');
        }
    }

    public static function current(): self
    {
        return new self('Current', 0, 0);
    }

    public static function days1to30(): self
    {
        return new self('1-30 Days', 1, 30);
    }

    public static function days31to60(): self
    {
        return new self('31-60 Days', 31, 60);
    }

    public static function days61to90(): self
    {
        return new self('61-90 Days', 61, 90);
    }

    public static function over90Days(): self
    {
        return new self('Over 90 Days', 91, -1); // -1 = unlimited max
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getMinDays(): int
    {
        return $this->minDays;
    }

    public function getMaxDays(): int
    {
        return $this->maxDays;
    }

    /**
     * Check if a given number of days falls within this bucket
     */
    public function contains(int $days): bool
    {
        if ($days < $this->minDays) {
            return false;
        }

        if ($this->maxDays === -1) {
            return true; // Unlimited max
        }

        return $days <= $this->maxDays;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
