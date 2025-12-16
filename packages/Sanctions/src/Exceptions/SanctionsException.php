<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Exceptions;

/**
 * Base exception for all Sanctions package exceptions.
 * 
 * This provides a common exception type that can be caught to handle
 * any sanctions-related error in a unified way.
 * 
 * @package Nexus\Sanctions\Exceptions
 */
class SanctionsException extends \Exception
{
    /**
     * Create exception for screening operation failure.
     *
     * @param string $partyId Party ID that failed screening
     * @param string $reason Reason for failure
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function screeningFailed(
        string $partyId,
        string $reason,
        ?\Throwable $previous = null
    ): static {
        return new static(
            "Screening failed for party {$partyId}: {$reason}",
            0,
            $previous
        );
    }

    /**
     * Create exception for invalid party data.
     *
     * @param string $partyId Party ID
     * @param array<string> $errors Validation errors
     * @return static
     */
    public static function invalidPartyData(
        string $partyId,
        array $errors
    ): static {
        $errorList = implode(', ', $errors);
        return new static(
            "Invalid party data for {$partyId}: {$errorList}"
        );
    }

    /**
     * Create exception for unavailable sanctions list.
     *
     * @param string $listName Sanctions list name
     * @param string $reason Reason for unavailability
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function listUnavailable(
        string $listName,
        string $reason,
        ?\Throwable $previous = null
    ): static {
        return new static(
            "Sanctions list '{$listName}' is unavailable: {$reason}",
            0,
            $previous
        );
    }

    /**
     * Create exception for configuration error.
     *
     * @param string $message Error message
     * @return static
     */
    public static function configurationError(string $message): static
    {
        return new static("Configuration error: {$message}");
    }

    /**
     * Create exception for data access error.
     *
     * @param string $operation Operation that failed
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function dataAccessError(
        string $operation,
        ?\Throwable $previous = null
    ): static {
        return new static(
            "Data access error during {$operation}",
            0,
            $previous
        );
    }
}
