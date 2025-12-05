<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable fiscal period identifier value object.
 * 
 * Represents a named fiscal period (e.g., "2024-Q1", "JAN-2024", "FY2024").
 * This is a lightweight identifier for referencing periods, not the full period entity.
 */
final readonly class Period implements Comparable, SerializableVO
{
    private string $name;

    /**
     * @param string $name Period name/identifier (e.g., "2024-Q1", "JAN-2024", "FY2024")
     * @throws InvalidValueException
     */
    public function __construct(string $name)
    {
        $trimmed = trim($name);
        
        if (empty($trimmed)) {
            throw new InvalidValueException('Period name cannot be empty');
        }

        if (strlen($trimmed) > 50) {
            throw new InvalidValueException('Period name cannot exceed 50 characters');
        }

        $this->name = $trimmed;
    }

    /**
     * Create a monthly period identifier (e.g., "JAN-2024")
     */
    public static function forMonth(int $year, int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidValueException("Month must be between 1 and 12, got: {$month}");
        }

        $monthNames = [
            1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AUG',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DEC'
        ];

        return new self($monthNames[$month] . '-' . $year);
    }

    /**
     * Create a quarterly period identifier (e.g., "2024-Q1")
     */
    public static function forQuarter(int $year, int $quarter): self
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new InvalidValueException("Quarter must be between 1 and 4, got: {$quarter}");
        }

        return new self($year . '-Q' . $quarter);
    }

    /**
     * Create a yearly period identifier (e.g., "FY2024")
     */
    public static function forYear(int $year): self
    {
        return new self('FY' . $year);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if this appears to be a monthly period (format: MMM-YYYY)
     */
    public function isMonthly(): bool
    {
        return preg_match('/^[A-Z]{3}-\d{4}$/', $this->name) === 1;
    }

    /**
     * Check if this appears to be a quarterly period (format: YYYY-QN)
     */
    public function isQuarterly(): bool
    {
        return preg_match('/^\d{4}-Q[1-4]$/', $this->name) === 1;
    }

    /**
     * Check if this appears to be a yearly period (format: FY + 4-digit year, e.g., FY2024)
     */
    public function isYearly(): bool
    {
        return preg_match('/^FY\d{4}$/', $this->name) === 1;
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another Period');
        }

        return strcmp($this->name, $other->name);
    }

    public function equals(Comparable $other): bool
    {
        return $other instanceof self && $this->name === $other->name;
    }

    public function greaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_monthly' => $this->isMonthly(),
            'is_quarterly' => $this->isQuarterly(),
            'is_yearly' => $this->isYearly(),
        ];
    }

    public function toString(): string
    {
        return $this->name;
    }

    public static function fromArray(array $data): static
    {
        return new self($data['name']);
    }
}
