<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\ValueObjects;

use Nexus\JournalEntry\Exceptions\InvalidJournalEntryNumberException;

/**
 * Journal Entry Number Value Object.
 *
 * Represents a validated journal entry number with optional prefix.
 * Format examples: "JE-2024-001234", "GJ001234", "2024-JE-001234"
 *
 * Immutable: all properties are readonly.
 */
final readonly class JournalEntryNumber
{
    /**
     * Create a new JournalEntryNumber instance.
     *
     * @param string $value The full journal entry number
     * @param string|null $prefix Optional extracted prefix
     * @param int|null $year Optional extracted year
     * @param int $sequence Extracted sequence number
     */
    public function __construct(
        public string $value,
        public ?string $prefix = null,
        public ?int $year = null,
        public int $sequence = 0
    ) {
        if (empty($value)) {
            throw InvalidJournalEntryNumberException::empty();
        }
    }

    /**
     * Create from a string value.
     *
     * Parses common patterns:
     * - JE-2024-001234 (prefix-year-sequence)
     * - GJ001234 (prefix-sequence)
     * - 001234 (sequence only)
     */
    public static function fromString(string $value): self
    {
        $value = trim($value);

        if (empty($value)) {
            throw InvalidJournalEntryNumberException::empty();
        }

        // Try to parse common patterns
        if (preg_match('/^([A-Z]+)-(\d{4})-(\d+)$/', $value, $matches)) {
            // Pattern: JE-2024-001234
            return new self(
                value: $value,
                prefix: $matches[1],
                year: (int) $matches[2],
                sequence: (int) $matches[3]
            );
        }

        if (preg_match('/^([A-Z]+)(\d+)$/', $value, $matches)) {
            // Pattern: GJ001234
            return new self(
                value: $value,
                prefix: $matches[1],
                year: null,
                sequence: (int) $matches[2]
            );
        }

        if (preg_match('/^(\d+)$/', $value, $matches)) {
            // Pattern: 001234 (sequence only)
            return new self(
                value: $value,
                prefix: null,
                year: null,
                sequence: (int) $matches[1]
            );
        }

        // Accept any non-empty string as a valid number
        return new self(value: $value);
    }

    /**
     * Create a journal entry number from components.
     *
     * This method assembles a journal entry number from pre-generated components.
     * The sequence number should be obtained externally (e.g., from Nexus\Sequencing
     * via an orchestrator) before calling this method.
     *
     * @param string $prefix Entry prefix (e.g., "JE", "GJ")
     * @param int $sequence Pre-generated sequence number (from external source)
     * @param int|null $year Optional year component
     * @param string $separator Separator character (default: "-")
     * @param int $sequencePadding Zero-padding length for sequence (default: 6)
     */
    public static function fromComponents(
        string $prefix,
        int $sequence,
        ?int $year = null,
        string $separator = '-',
        int $sequencePadding = 6
    ): self {
        $parts = [$prefix];

        if ($year !== null) {
            $parts[] = (string) $year;
        }

        $parts[] = str_pad((string) $sequence, $sequencePadding, '0', STR_PAD_LEFT);

        $value = implode($separator, $parts);

        return new self(
            value: $value,
            prefix: $prefix,
            year: $year,
            sequence: $sequence
        );
    }


    /**
     * Check if this number equals another.
     */
    public function equals(JournalEntryNumber $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the full value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get string representation.
     */
    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
