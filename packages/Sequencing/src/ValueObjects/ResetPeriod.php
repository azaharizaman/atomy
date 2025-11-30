<?php

declare(strict_types=1);

namespace Nexus\Sequencing\ValueObjects;

use Nexus\Sequencing\Exceptions\InvalidResetPeriodException;

/**
 * Value object representing when a sequence counter resets.
 */
enum ResetPeriod: string
{
    case NEVER = 'never';
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    /**
     * Create from string value.
     *
     * @throws InvalidResetPeriodException
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'never' => self::NEVER,
            'daily' => self::DAILY,
            'monthly' => self::MONTHLY,
            'yearly' => self::YEARLY,
            default => throw InvalidResetPeriodException::unknownPeriod($value),
        };
    }

    /**
     * Check if this reset period requires date-based reset checks.
     */
    public function requiresDateCheck(): bool
    {
        return $this !== self::NEVER;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEVER => 'Never (Continuous)',
            self::DAILY => 'Daily',
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
        };
    }
}
