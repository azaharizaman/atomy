<?php

declare(strict_types=1);

namespace Nexus\Party\Exceptions;

/**
 * Exception thrown when relationship validation fails.
 */
class InvalidRelationshipException extends \RuntimeException
{
    public static function individualRequired(string $relationshipType): self
    {
        return new self(
            "Relationship type '{$relationshipType}' requires the 'from' party to be an individual"
        );
    }
    
    public static function organizationRequired(string $relationshipType): self
    {
        return new self(
            "Relationship type '{$relationshipType}' requires the 'to' party to be an organization"
        );
    }
    
    public static function invalidDateRange(\DateTimeInterface $from, \DateTimeInterface $to): self
    {
        return new self(
            "Invalid relationship date range: 'effective_to' ({$to->format('Y-m-d')}) " .
            "must be after 'effective_from' ({$from->format('Y-m-d')})"
        );
    }
    
    public static function sameParty(string $partyId): self
    {
        return new self("A party cannot have a relationship with itself (party ID: {$partyId})");
    }
}
