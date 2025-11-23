<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Exceptions;

use Nexus\FeatureFlags\Enums\FlagStrategy;

/**
 * Exception thrown when flag definition validation fails.
 */
final class InvalidFlagDefinitionException extends FeatureFlagException
{
    /**
     * Create exception for invalid flag name.
     *
     * @param string $name The invalid name
     * @param string $reason The reason it's invalid
     * @return self
     */
    public static function invalidName(string $name, string $reason): self
    {
        return new self(
            "Invalid flag name '{$name}': {$reason}"
        );
    }

    /**
     * Create exception for invalid value type.
     *
     * @param FlagStrategy $strategy The flag strategy
     * @param string $expected Expected type
     * @param string $actual Actual type
     * @return self
     */
    public static function invalidValueType(
        FlagStrategy $strategy,
        string $expected,
        string $actual
    ): self {
        return new self(
            "Invalid value type for strategy {$strategy->value}: expected {$expected}, got {$actual}"
        );
    }

    /**
     * Create exception for invalid percentage range.
     *
     * @param int $value The invalid percentage value
     * @return self
     */
    public static function invalidPercentageRange(int $value): self
    {
        return new self(
            "Percentage value must be between 0 and 100, got {$value}"
        );
    }

    /**
     * Create exception for invalid list item type.
     *
     * @param FlagStrategy $strategy The flag strategy
     * @param string $actualType Actual item type
     * @return self
     */
    public static function invalidListItem(FlagStrategy $strategy, string $actualType): self
    {
        return new self(
            "All items in {$strategy->value} value must be strings, got {$actualType}"
        );
    }

    /**
     * Create exception for custom evaluator class not found.
     *
     * @param string $className The class name
     * @return self
     */
    public static function customClassNotFound(string $className): self
    {
        return new self(
            "Custom evaluator class not found: {$className}"
        );
    }
}
