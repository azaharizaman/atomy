<?php

declare(strict_types=1);

namespace Nexus\Party\Enums;

/**
 * Address type enumeration.
 * 
 * Defines the purpose/classification of a party address.
 */
enum AddressType: string
{
    case BILLING = 'billing';
    case SHIPPING = 'shipping';
    case LEGAL = 'legal';
    case HEADQUARTERS = 'headquarters';
    case BRANCH = 'branch';
    case RESIDENTIAL = 'residential';
    case MAILING = 'mailing';
    
    /**
     * Check if this address type is for legal/official purposes.
     */
    public function isOfficial(): bool
    {
        return match($this) {
            self::LEGAL, self::HEADQUARTERS => true,
            default => false,
        };
    }
    
    /**
     * Get human-readable label for this address type.
     */
    public function label(): string
    {
        return match($this) {
            self::BILLING => 'Billing Address',
            self::SHIPPING => 'Shipping Address',
            self::LEGAL => 'Legal Address',
            self::HEADQUARTERS => 'Headquarters',
            self::BRANCH => 'Branch Office',
            self::RESIDENTIAL => 'Residential Address',
            self::MAILING => 'Mailing Address',
        };
    }
}
