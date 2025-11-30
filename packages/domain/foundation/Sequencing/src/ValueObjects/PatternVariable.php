<?php

declare(strict_types=1);

namespace Nexus\Sequencing\ValueObjects;

/**
 * Value object representing a pattern variable with optional padding.
 */
final readonly class PatternVariable
{
    public function __construct(
        public string $name,
        public ?int $padding = null,
    ) {}

    /**
     * Parse a variable from pattern syntax (e.g., "COUNTER:5").
     */
    public static function parse(string $variable): self
    {
        if (str_contains($variable, ':')) {
            [$name, $paddingStr] = explode(':', $variable, 2);
            return new self($name, (int) $paddingStr);
        }

        return new self($variable);
    }

    /**
     * Format a value according to this variable's padding.
     */
    public function format(string|int $value): string
    {
        if ($this->padding === null) {
            return (string) $value;
        }

        return str_pad((string) $value, $this->padding, '0', STR_PAD_LEFT);
    }

    /**
     * Check if this is a counter variable.
     */
    public function isCounter(): bool
    {
        return strtoupper($this->name) === 'COUNTER';
    }

    /**
     * Check if this is a date/time variable.
     */
    public function isDateTime(): bool
    {
        return in_array(strtoupper($this->name), ['YEAR', 'YY', 'MONTH', 'DAY'], true);
    }

    /**
     * Check if this is a custom context variable.
     */
    public function isCustom(): bool
    {
        return !$this->isCounter() && !$this->isDateTime();
    }

    /**
     * Get the pattern representation (e.g., "{COUNTER:5}").
     */
    public function toPattern(): string
    {
        $pattern = '{' . $this->name;
        if ($this->padding !== null) {
            $pattern .= ':' . $this->padding;
        }
        return $pattern . '}';
    }
}
