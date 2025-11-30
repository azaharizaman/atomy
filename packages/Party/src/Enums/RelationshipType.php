<?php

declare(strict_types=1);

namespace Nexus\Party\Enums;

/**
 * Party relationship type enumeration.
 * 
 * Defines how two parties are related to each other.
 */
enum RelationshipType: string
{
    case EMPLOYMENT_AT = 'employment_at';
    case CONTACT_FOR = 'contact_for';
    case SUBSIDIARY_OF = 'subsidiary_of';
    case PARTNER_OF = 'partner_of';
    case VENDOR_OF = 'vendor_of';
    case CUSTOMER_OF = 'customer_of';
    
    /**
     * Check if this relationship requires the "from" party to be an individual.
     */
    public function requiresIndividualFrom(): bool
    {
        return match($this) {
            self::EMPLOYMENT_AT, self::CONTACT_FOR => true,
            default => false,
        };
    }
    
    /**
     * Check if this relationship requires the "to" party to be an organization.
     */
    public function requiresOrganizationTo(): bool
    {
        return match($this) {
            self::EMPLOYMENT_AT, self::CONTACT_FOR, self::SUBSIDIARY_OF => true,
            default => false,
        };
    }
    
    /**
     * Check if this relationship type requires circular reference validation.
     */
    public function requiresCircularCheck(): bool
    {
        return match($this) {
            self::SUBSIDIARY_OF => true,
            default => false,
        };
    }
    
    /**
     * Get the inverse relationship type (if applicable).
     */
    public function getInverse(): ?self
    {
        return match($this) {
            self::VENDOR_OF => self::CUSTOMER_OF,
            self::CUSTOMER_OF => self::VENDOR_OF,
            default => null,
        };
    }
    
    /**
     * Get human-readable label for this relationship type.
     */
    public function label(): string
    {
        return match($this) {
            self::EMPLOYMENT_AT => 'Employed At',
            self::CONTACT_FOR => 'Contact For',
            self::SUBSIDIARY_OF => 'Subsidiary Of',
            self::PARTNER_OF => 'Partner Of',
            self::VENDOR_OF => 'Vendor Of',
            self::CUSTOMER_OF => 'Customer Of',
        };
    }
}
