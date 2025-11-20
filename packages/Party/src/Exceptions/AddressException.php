<?php

declare(strict_types=1);

namespace Nexus\Party\Exceptions;

/**
 * Exception thrown when a party address operation fails.
 */
class AddressException extends \RuntimeException
{
    public static function notFound(string $id): self
    {
        return new self("Address with ID '{$id}' not found");
    }
    
    public static function noPrimaryAddress(string $partyId): self
    {
        return new self("No primary address found for party '{$partyId}'");
    }
}
