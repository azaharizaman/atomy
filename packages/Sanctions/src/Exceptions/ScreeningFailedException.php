<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Exceptions;

use Nexus\Sanctions\Enums\SanctionsList;

/**
 * Exception thrown when a screening operation fails.
 * 
 * This can occur due to:
 * - API timeout or network error when accessing sanctions lists
 * - Data corruption or invalid format in list data
 * - System resource exhaustion
 * - Configuration issues preventing screening
 * 
 * @package Nexus\Sanctions\Exceptions
 */
final class ScreeningFailedException extends SanctionsException
{
    /**
     * Create exception for API timeout.
     *
     * @param string $partyId Party ID being screened
     * @param SanctionsList $list Sanctions list that timed out
     * @param int $timeoutSeconds Timeout duration
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function apiTimeout(
        string $partyId,
        SanctionsList $list,
        int $timeoutSeconds,
        ?\Throwable $previous = null
    ): self {
        return new self(
            "Screening API timeout for party {$partyId} on list {$list->value} after {$timeoutSeconds}s",
            408,
            $previous
        );
    }

    /**
     * Create exception for network error.
     *
     * @param string $partyId Party ID being screened
     * @param SanctionsList $list Sanctions list
     * @param string $error Network error details
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function networkError(
        string $partyId,
        SanctionsList $list,
        string $error,
        ?\Throwable $previous = null
    ): self {
        return new self(
            "Network error screening party {$partyId} on list {$list->value}: {$error}",
            503,
            $previous
        );
    }

    /**
     * Create exception for invalid list data.
     *
     * @param SanctionsList $list Sanctions list with invalid data
     * @param string $reason Reason for invalidity
     * @return self
     */
    public static function invalidListData(
        SanctionsList $list,
        string $reason
    ): self {
        return new self(
            "Invalid data in sanctions list {$list->value}: {$reason}",
            500
        );
    }

    /**
     * Create exception for multiple list failures.
     *
     * @param string $partyId Party ID being screened
     * @param array<SanctionsList> $failedLists Lists that failed
     * @param array<string> $errors Error messages per list
     * @return self
     */
    public static function multipleLists(
        string $partyId,
        array $failedLists,
        array $errors
    ): self {
        $listNames = array_map(fn($list) => $list->value, $failedLists);
        $errorSummary = implode('; ', $errors);
        
        return new self(
            "Screening failed for party {$partyId} on " . count($failedLists) . 
            " lists (" . implode(', ', $listNames) . "): {$errorSummary}",
            500
        );
    }

    /**
     * Create exception for fuzzy matching failure.
     *
     * @param string $partyId Party ID being screened
     * @param string $error Fuzzy matching error
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function fuzzyMatchingFailed(
        string $partyId,
        string $error,
        ?\Throwable $previous = null
    ): self {
        return new self(
            "Fuzzy matching failed for party {$partyId}: {$error}",
            500,
            $previous
        );
    }

    /**
     * Create exception for resource exhaustion.
     *
     * @param string $resource Resource that was exhausted (memory, CPU, etc.)
     * @param string $operation Operation that failed
     * @return self
     */
    public static function resourceExhausted(
        string $resource,
        string $operation
    ): self {
        return new self(
            "Resource exhausted ({$resource}) during {$operation}",
            503
        );
    }
}
