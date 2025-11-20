<?php

declare(strict_types=1);

namespace Nexus\Party\Exceptions;

/**
 * Exception thrown when a party is not found.
 */
class PartyNotFoundException extends \RuntimeException
{
    public static function forId(string $id): self
    {
        return new self("Party with ID '{$id}' not found");
    }
    
    public static function forLegalName(string $legalName): self
    {
        return new self("Party with legal name '{$legalName}' not found");
    }
    
    public static function forTaxIdentity(string $country, string $taxNumber): self
    {
        return new self("Party with tax identity '{$country}: {$taxNumber}' not found");
    }
}
