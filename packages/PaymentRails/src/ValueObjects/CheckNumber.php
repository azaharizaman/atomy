<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\PaymentRails\Exceptions\InvalidCheckNumberException;

/**
 * Represents a check number with validation.
 *
 * Check numbers are sequential identifiers assigned to paper checks.
 * They must be numeric and within a valid range.
 */
final class CheckNumber
{
    /**
     * Maximum check number (typical banking limit).
     */
    private const MAX_CHECK_NUMBER = 999999999;

    /**
     * @var string The check number value
     */
    public readonly string $value;

    /**
     * @throws InvalidCheckNumberException
     */
    public function __construct(string $value)
    {
        $value = ltrim(trim($value), '0') ?: '0';

        if (!ctype_digit($value)) {
            throw InvalidCheckNumberException::nonNumeric($value);
        }

        if ((int) $value > self::MAX_CHECK_NUMBER) {
            throw InvalidCheckNumberException::exceedsMaximum($value, self::MAX_CHECK_NUMBER);
        }

        if ((int) $value < 1) {
            throw InvalidCheckNumberException::belowMinimum($value);
        }

        $this->value = $value;
    }

    /**
     * Create a check number from a string.
     *
     * @throws InvalidCheckNumberException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create a check number from an integer.
     *
     * @throws InvalidCheckNumberException
     */
    public static function fromInt(int $value): self
    {
        return new self((string) $value);
    }

    /**
     * Create a check number, returning null if invalid.
     */
    public static function tryFromString(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidCheckNumberException) {
            return null;
        }
    }

    /**
     * Get the numeric value.
     */
    public function toInt(): int
    {
        return (int) $this->value;
    }

    /**
     * Get the next check number.
     */
    public function next(): self
    {
        return new self((string) ($this->toInt() + 1));
    }

    /**
     * Get a check number N positions ahead.
     */
    public function advance(int $count): self
    {
        return new self((string) ($this->toInt() + $count));
    }

    /**
     * Format with leading zeros to a specific width.
     */
    public function formatted(int $width = 6): string
    {
        return str_pad($this->value, $width, '0', STR_PAD_LEFT);
    }

    /**
     * Get the string representation.
     */
    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Check equality with another check number.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Check if this check number is before another.
     */
    public function isBefore(self $other): bool
    {
        return $this->toInt() < $other->toInt();
    }

    /**
     * Check if this check number is after another.
     */
    public function isAfter(self $other): bool
    {
        return $this->toInt() > $other->toInt();
    }
}
