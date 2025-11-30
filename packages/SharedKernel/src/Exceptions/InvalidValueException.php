<?php

declare(strict_types=1);

namespace Nexus\SharedKernel\Exceptions;

/**
 * Invalid Value Exception
 *
 * Thrown when a value object receives invalid data that cannot be validated.
 * This is the base exception for all value object validation failures.
 */
class InvalidValueException extends \InvalidArgumentException
{
    /**
     * Create exception for invalid format
     */
    public static function invalidFormat(string $expected, string $actual): self
    {
        return new self(
            sprintf('Invalid format: expected %s, got %s', $expected, $actual)
        );
    }

    /**
     * Create exception for out of range value
     */
    public static function outOfRange(string $field, mixed $value, mixed $min, mixed $max): self
    {
        return new self(
            sprintf('%s value %s is out of range [%s, %s]', $field, $value, $min, $max)
        );
    }
}
