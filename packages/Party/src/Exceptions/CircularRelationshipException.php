<?php

declare(strict_types=1);

namespace Nexus\Party\Exceptions;

/**
 * Exception thrown when a circular relationship is detected.
 */
class CircularRelationshipException extends \RuntimeException
{
    public static function detected(string $fromPartyId, string $toPartyId): self
    {
        return new self(
            "Circular relationship detected: Party '{$fromPartyId}' cannot have a relationship to '{$toPartyId}' " .
            "as it would create a circular reference in the organizational hierarchy"
        );
    }
    
    public static function maxDepthExceeded(string $partyId, int $maxDepth): self
    {
        return new self(
            "Maximum organizational hierarchy depth ({$maxDepth}) exceeded for party '{$partyId}'"
        );
    }
}
