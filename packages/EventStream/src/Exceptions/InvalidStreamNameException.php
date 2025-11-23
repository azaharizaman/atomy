<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * Invalid Stream Name Exception
 *
 * Thrown when a generated stream name fails validation rules:
 * - Exceeds maximum length (255 characters)
 * - Contains invalid characters (not alphanumeric/hyphen/underscore)
 * - Empty or whitespace-only components
 *
 * Requirements satisfied:
 * - SEC-EVS-7511: Stream name input validation
 * - FUN-EVS-7234: Stream naming validation enforcement
 *
 * @package Nexus\EventStream\Exceptions
 */
class InvalidStreamNameException extends EventStreamException
{
    public function __construct(
        string $message = 'Invalid stream name',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for length violation
     *
     * @param string $streamName The invalid stream name
     * @param int $actualLength Actual length
     * @param int $maxLength Maximum allowed length
     * @return self
     */
    public static function tooLong(string $streamName, int $actualLength, int $maxLength = 255): self
    {
        return new self(
            sprintf(
                'Stream name "%s" exceeds maximum length. Length: %d, Maximum: %d',
                $streamName,
                $actualLength,
                $maxLength
            )
        );
    }

    /**
     * Create exception for invalid characters
     *
     * @param string $streamName The invalid stream name
     * @param string $allowedPattern Description of allowed pattern
     * @return self
     */
    public static function invalidCharacters(string $streamName, string $allowedPattern = 'alphanumeric, hyphens, and underscores'): self
    {
        return new self(
            sprintf(
                'Stream name "%s" contains invalid characters. Only %s are allowed.',
                $streamName,
                $allowedPattern
            )
        );
    }

    /**
     * Create exception for empty component
     *
     * @param string $componentName Component that is empty (context, type, or id)
     * @return self
     */
    public static function emptyComponent(string $componentName): self
    {
        return new self(
            sprintf('Stream name component "%s" cannot be empty', $componentName)
        );
    }
}
