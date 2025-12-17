<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Exceptions;

/**
 * Exception thrown when a data subject request is not found.
 */
class RequestNotFoundException extends DataPrivacyException
{
    public static function withId(string $id): self
    {
        return new self("Data subject request with ID '{$id}' not found");
    }
}
