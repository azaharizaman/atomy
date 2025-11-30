<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * UpcasterFailedException
 *
 * Thrown when event upcasting fails due to validation errors,
 * missing required fields, or transformation errors.
 *
 * Requirements satisfied:
 * - MAI-EVS-7810: Fail-fast upcasting error handling
 * - REL-EVS-7415: Clear upcasting failure context
 *
 * @package Nexus\EventStream\Exceptions
 */
class UpcasterFailedException extends EventStreamException
{
    public function __construct(
        public readonly string $eventType,
        public readonly int $fromVersion,
        public readonly int $toVersion,
        public readonly string $reason,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = sprintf(
                'Failed to upcast event "%s" from version %d to %d: %s',
                $eventType,
                $fromVersion,
                $toVersion,
                $reason
            );
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for missing required field after upcasting
     */
    public static function missingRequiredField(
        string $eventType,
        int $fromVersion,
        int $toVersion,
        string $fieldName
    ): self {
        return new self(
            $eventType,
            $fromVersion,
            $toVersion,
            sprintf('Required field "%s" is missing after upcasting', $fieldName)
        );
    }

    /**
     * Create exception for invalid transformation result
     */
    public static function invalidTransformation(
        string $eventType,
        int $fromVersion,
        int $toVersion,
        string $reason
    ): self {
        return new self(
            $eventType,
            $fromVersion,
            $toVersion,
            sprintf('Invalid transformation: %s', $reason)
        );
    }

    /**
     * Create exception for version gap in upcaster chain
     */
    public static function versionGap(
        string $eventType,
        int $fromVersion,
        int $expectedVersion
    ): self {
        return new self(
            $eventType,
            $fromVersion,
            $expectedVersion,
            sprintf('Version gap detected: no upcaster from v%d to v%d', $fromVersion, $expectedVersion)
        );
    }
}
