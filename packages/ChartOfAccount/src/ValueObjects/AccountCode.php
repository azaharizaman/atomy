<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\ValueObjects;

use Nexus\ChartOfAccount\Exceptions\InvalidAccountException;

/**
 * Account Code Value Object.
 *
 * Represents a validated account code with support for hierarchical structures.
 * Account codes can use various separators (dash, dot, or none) to indicate hierarchy.
 *
 * Examples:
 * - Numeric: '1000', '1001', '1002'
 * - Dash-separated: '1000-001', '1000-002'
 * - Dot-separated: '1000.001', '1000.002'
 *
 * This is an immutable value object.
 */
final readonly class AccountCode
{
    /**
     * Valid separator characters for hierarchical codes.
     */
    private const array VALID_SEPARATORS = ['-', '.'];

    /**
     * Maximum allowed length for account codes.
     */
    private const int MAX_LENGTH = 50;

    /**
     * @param string $value The validated account code
     */
    private function __construct(
        private string $value
    ) {}

    /**
     * Create an AccountCode from a string.
     *
     * @param string $code The account code string
     * @return self Valid AccountCode instance
     * @throws InvalidAccountException If code format is invalid
     */
    public static function fromString(string $code): self
    {
        $trimmed = trim($code);

        if ($trimmed === '') {
            throw new InvalidAccountException('Account code cannot be empty');
        }

        if (strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidAccountException(
                sprintf('Account code cannot exceed %d characters', self::MAX_LENGTH)
            );
        }

        // Validate format: alphanumeric with optional separators
        if (!preg_match('/^[a-zA-Z0-9]+([.\-][a-zA-Z0-9]+)*$/', $trimmed)) {
            throw new InvalidAccountException(
                'Account code must be alphanumeric, optionally separated by dots or dashes'
            );
        }

        return new self($trimmed);
    }

    /**
     * Get the account code value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the hierarchy level (1-based).
     *
     * Level is determined by the number of segments separated by dots or dashes.
     * A code without separators is level 1.
     *
     * Examples:
     * - '1000' -> Level 1
     * - '1000-001' -> Level 2
     * - '1000-001-01' -> Level 3
     */
    public function getLevel(): int
    {
        $segments = $this->getSegments();

        return count($segments);
    }

    /**
     * Get the code segments.
     *
     * @return array<string> Array of code segments
     */
    public function getSegments(): array
    {
        // Split by either dash or dot
        return preg_split('/[.\-]/', $this->value) ?: [$this->value];
    }

    /**
     * Get the parent account code.
     *
     * Returns null if this is a top-level code (no parent).
     *
     * Examples:
     * - '1000' -> null
     * - '1000-001' -> AccountCode('1000')
     * - '1000-001-01' -> AccountCode('1000-001')
     */
    public function getParent(): ?self
    {
        $segments = $this->getSegments();

        if (count($segments) <= 1) {
            return null;
        }

        // Detect the separator used
        $separator = $this->detectSeparator();

        // Remove last segment
        array_pop($segments);

        return new self(implode($separator ?? '-', $segments));
    }

    /**
     * Check if this code is a parent of another code.
     *
     * @param self $other The potential child code
     * @return bool True if this code is a parent of the other
     */
    public function isParentOf(self $other): bool
    {
        $thisSegments = $this->getSegments();
        $otherSegments = $other->getSegments();

        // Parent must have fewer segments
        if (count($thisSegments) >= count($otherSegments)) {
            return false;
        }

        // Check if all parent segments match
        foreach ($thisSegments as $index => $segment) {
            if ($segment !== $otherSegments[$index]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this code is a child of another code.
     *
     * @param self $other The potential parent code
     * @return bool True if this code is a child of the other
     */
    public function isChildOf(self $other): bool
    {
        return $other->isParentOf($this);
    }

    /**
     * Check if this code is a direct child (one level below) of another code.
     *
     * @param self $other The potential parent code
     * @return bool True if this code is a direct child
     */
    public function isDirectChildOf(self $other): bool
    {
        return $this->isChildOf($other)
            && $this->getLevel() === $other->getLevel() + 1;
    }

    /**
     * Detect the separator used in this code.
     *
     * @return string|null The separator character, or null if none
     */
    private function detectSeparator(): ?string
    {
        foreach (self::VALID_SEPARATORS as $separator) {
            if (str_contains($this->value, $separator)) {
                return $separator;
            }
        }

        return null;
    }

    /**
     * Check equality with another AccountCode.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get string representation.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
