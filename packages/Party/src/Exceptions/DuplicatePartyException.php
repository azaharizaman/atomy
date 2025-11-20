<?php

declare(strict_types=1);

namespace Nexus\Party\Exceptions;

/**
 * Exception thrown when attempting to create a duplicate party.
 */
class DuplicatePartyException extends \RuntimeException
{
    public static function forLegalName(string $legalName): self
    {
        return new self("Party with legal name '{$legalName}' already exists");
    }
    
    public static function forTaxIdentity(string $country, string $taxNumber): self
    {
        return new self("Party with tax identity '{$country}: {$taxNumber}' already exists");
    }
}
