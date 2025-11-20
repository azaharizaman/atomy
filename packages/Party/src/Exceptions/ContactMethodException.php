<?php

declare(strict_types=1);

namespace Nexus\Party\Exceptions;

/**
 * Exception thrown when a contact method operation fails.
 */
class ContactMethodException extends \RuntimeException
{
    public static function notFound(string $id): self
    {
        return new self("Contact method with ID '{$id}' not found");
    }
    
    public static function invalidFormat(string $type, string $value): self
    {
        return new self("Invalid {$type} format: {$value}");
    }
    
    public static function duplicateValue(string $type, string $value): self
    {
        return new self("Contact method {$type} with value '{$value}' already exists");
    }
}
