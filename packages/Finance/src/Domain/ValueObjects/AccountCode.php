<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Account Code Value Object
 * 
 * Immutable representation of a chart of accounts code.
 */
final readonly class AccountCode
{
    public function __construct(
        private string $code
    ) {
        $this->validate($code);
    }

    public static function fromString(string $code): self
    {
        return new self($code);
    }

    public function getValue(): string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    /**
     * Check if this code is a parent of another code
     * (e.g., "1000" is parent of "1000-001")
     */
    public function isParentOf(self $other): bool
    {
        return str_starts_with($other->code, $this->code . '-');
    }

    /**
     * Get the parent code (null if root level)
     * (e.g., "1000-001-002" -> "1000-001")
     */
    public function getParent(): ?self
    {
        $lastDash = strrpos($this->code, '-');
        
        if ($lastDash === false) {
            return null;
        }
        
        return new self(substr($this->code, 0, $lastDash));
    }

    /**
     * Get the level/depth in the hierarchy (0-based)
     * (e.g., "1000" -> 0, "1000-001" -> 1, "1000-001-002" -> 2)
     */
    public function getLevel(): int
    {
        return substr_count($this->code, '-');
    }

    private function validate(string $code): void
    {
        if (trim($code) === '') {
            throw new InvalidArgumentException('Account code cannot be empty');
        }

        if (strlen($code) > 50) {
            throw new InvalidArgumentException('Account code cannot exceed 50 characters');
        }

        // Allow alphanumeric, dashes, and underscores
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
            throw new InvalidArgumentException(
                'Account code can only contain letters, numbers, dashes, and underscores'
            );
        }
    }
}
