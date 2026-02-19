<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Exceptions;

use RuntimeException;

/**
 * Exception for sanctions data operations.
 */
final class SanctionsDataException extends RuntimeException
{
    public static function screeningNotFound(string $partyId): self
    {
        return new self("Sanctions screening not found for party: {$partyId}");
    }

    public static function screeningFailed(string $partyId, string $reason): self
    {
        return new self("Sanctions screening failed for party {$partyId}: {$reason}");
    }

    public static function matchNotFound(string $matchId): self
    {
        return new self("Sanctions match not found: {$matchId}");
    }

    public static function listUnavailable(string $listName): self
    {
        return new self("Sanctions list unavailable: {$listName}");
    }
}
