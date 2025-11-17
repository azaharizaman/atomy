<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\ValueObjects;

use Nexus\AuditLogger\Exceptions\InvalidAuditLevelException;

/**
 * Immutable value object representing audit log severity level
 * Satisfies: BUS-AUD-0146
 *
 * @package Nexus\AuditLogger\ValueObjects
 */
final class AuditLevel
{
    public const LOW = 1;
    public const MEDIUM = 2;
    public const HIGH = 3;
    public const CRITICAL = 4;

    private int $value;

    /**
     * @param int $value Must be 1 (Low), 2 (Medium), 3 (High), or 4 (Critical)
     * @throws InvalidAuditLevelException
     */
    public function __construct(int $value)
    {
        if (!in_array($value, [self::LOW, self::MEDIUM, self::HIGH, self::CRITICAL], true)) {
            throw new InvalidAuditLevelException($value);
        }

        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isLow(): bool
    {
        return $this->value === self::LOW;
    }

    public function isMedium(): bool
    {
        return $this->value === self::MEDIUM;
    }

    public function isHigh(): bool
    {
        return $this->value === self::HIGH;
    }

    public function isCritical(): bool
    {
        return $this->value === self::CRITICAL;
    }

    public function getLabel(): string
    {
        return match ($this->value) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }

    public static function low(): self
    {
        return new self(self::LOW);
    }

    public static function medium(): self
    {
        return new self(self::MEDIUM);
    }

    public static function high(): self
    {
        return new self(self::HIGH);
    }

    public static function critical(): self
    {
        return new self(self::CRITICAL);
    }
}
