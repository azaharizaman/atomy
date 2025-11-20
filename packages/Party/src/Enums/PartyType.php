<?php

declare(strict_types=1);

namespace Nexus\Party\Enums;

/**
 * Party type enumeration.
 * 
 * Defines whether a party is a natural person or a legal entity.
 */
enum PartyType: string
{
    case INDIVIDUAL = 'individual';
    case ORGANIZATION = 'organization';
    
    /**
     * Check if this party type is an individual (natural person).
     */
    public function isIndividual(): bool
    {
        return $this === self::INDIVIDUAL;
    }
    
    /**
     * Check if this party type is an organization (legal entity).
     */
    public function isOrganization(): bool
    {
        return $this === self::ORGANIZATION;
    }
    
    /**
     * Check if this party type requires a tax registration number.
     */
    public function requiresTaxRegistration(): bool
    {
        return $this === self::ORGANIZATION;
    }
    
    /**
     * Get human-readable label for this party type.
     */
    public function label(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'Individual',
            self::ORGANIZATION => 'Organization',
        };
    }
}
