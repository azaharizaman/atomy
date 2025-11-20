<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

/**
 * Exception thrown when a locale code does not conform to IETF BCP 47 format.
 *
 * Valid format: language code (2 lowercase letters) optionally followed by
 * underscore and region code (2 uppercase letters).
 * Examples: "en", "en_US", "ms_MY"
 */
final class InvalidLocaleCodeException extends LocalizationException
{
    public function __construct(string $invalidCode)
    {
        parent::__construct(
            "Invalid locale code: '{$invalidCode}'. " .
            "Expected IETF BCP 47 format: language code (2 lowercase letters) " .
            "optionally followed by underscore and region code (2 uppercase letters). " .
            "Examples: 'en', 'en_US', 'ms_MY'"
        );
    }
}
