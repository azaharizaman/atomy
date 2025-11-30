<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Journal Entry Number Value Object
 * 
 * Immutable representation of a journal entry number with pattern support.
 */
final readonly class JournalEntryNumber
{
    public function __construct(
        private string $number
    ) {
        $this->validate($number);
    }

    /**
     * Create from a pattern (e.g., "JE-{YYYY}-{NNNN}")
     * 
     * @param string $pattern The pattern with placeholders
     * @param int $year The year value
     * @param int $sequence The sequence number
     */
    public static function fromPattern(string $pattern, int $year, int $sequence): self
    {
        $number = str_replace(
            ['{YYYY}', '{YY}', '{NNNN}', '{NNN}'],
            [
                (string) $year,
                substr((string) $year, -2),
                str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            ],
            $pattern
        );

        return new self($number);
    }

    /**
     * Create from a simple string
     */
    public static function fromString(string $number): self
    {
        return new self($number);
    }

    public function getValue(): string
    {
        return $this->number;
    }

    public function __toString(): string
    {
        return $this->number;
    }

    public function equals(self $other): bool
    {
        return $this->number === $other->number;
    }

    /**
     * Extract the year from the number (if it follows a pattern)
     */
    public function extractYear(): ?int
    {
        // Try to find a 4-digit year
        if (preg_match('/\b(20\d{2})\b/', $this->number, $matches)) {
            return (int) $matches[1];
        }

        // Try to find a 2-digit year
        if (preg_match('/\b(\d{2})\b/', $this->number, $matches)) {
            $year = (int) $matches[1];
            return $year < 50 ? 2000 + $year : 1900 + $year;
        }

        return null;
    }

    /**
     * Extract the sequence number from the number (if it follows a pattern)
     */
    public function extractSequence(): ?int
    {
        // Try to find trailing digits
        if (preg_match('/(\d+)$/', $this->number, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function validate(string $number): void
    {
        if (trim($number) === '') {
            throw new InvalidArgumentException('Journal entry number cannot be empty');
        }

        if (strlen($number) > 100) {
            throw new InvalidArgumentException('Journal entry number cannot exceed 100 characters');
        }
    }
}
