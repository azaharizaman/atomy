<?php

declare(strict_types=1);

namespace Nexus\Sequencing\ValueObjects;

use Nexus\Sequencing\Exceptions\InvalidOverflowBehaviorException;

/**
 * Value object representing how to handle counter overflow.
 */
enum OverflowBehavior: string
{
    case THROW_EXCEPTION = 'throw_exception';
    case SWITCH_PATTERN = 'switch_pattern';
    case EXTEND_PADDING = 'extend_padding';

    /**
     * Create from string value.
     *
     * @throws InvalidOverflowBehaviorException
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'throw_exception' => self::THROW_EXCEPTION,
            'switch_pattern' => self::SWITCH_PATTERN,
            'extend_padding' => self::EXTEND_PADDING,
            default => throw InvalidOverflowBehaviorException::unknownBehavior($value),
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::THROW_EXCEPTION => 'Throw Exception',
            self::SWITCH_PATTERN => 'Switch to New Pattern',
            self::EXTEND_PADDING => 'Extend Padding',
        };
    }

    /**
     * Get description of the behavior.
     */
    public function description(): string
    {
        return match ($this) {
            self::THROW_EXCEPTION => 'Stop generation and throw exception when counter exceeds maximum',
            self::SWITCH_PATTERN => 'Automatically migrate to a new pattern when counter is exhausted',
            self::EXTEND_PADDING => 'Increase padding size to accommodate larger numbers (e.g., 9999 → 10000)',
        };
    }
}
