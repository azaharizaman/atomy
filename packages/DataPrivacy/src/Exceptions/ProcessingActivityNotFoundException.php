<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Exceptions;

/**
 * Exception thrown when processing activity is not found.
 */
class ProcessingActivityNotFoundException extends DataPrivacyException
{
    public static function withId(string $id): self
    {
        return new self("Processing activity with ID '{$id}' not found");
    }
}
