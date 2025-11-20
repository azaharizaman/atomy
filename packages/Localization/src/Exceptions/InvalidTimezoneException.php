<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

/**
 * Exception thrown when an invalid IANA timezone identifier is provided.
 */
final class InvalidTimezoneException extends LocalizationException
{
    public function __construct(string $invalidTimezone)
    {
        parent::__construct(
            "Invalid IANA timezone identifier: '{$invalidTimezone}'. " .
            "See https://www.php.net/manual/en/timezones.php for valid identifiers."
        );
    }
}
