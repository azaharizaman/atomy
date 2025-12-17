<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Exceptions;

/**
 * Exception thrown when breach record is not found.
 */
class BreachRecordNotFoundException extends DataPrivacyException
{
    public static function withId(string $id): self
    {
        return new self("Breach record with ID '{$id}' not found");
    }
}
