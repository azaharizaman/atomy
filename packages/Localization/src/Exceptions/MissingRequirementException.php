<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

/**
 * Exception thrown when a required PHP extension or dependency is missing.
 *
 * The Localization package requires the PHP intl extension for
 * CLDR-authoritative formatting via NumberFormatter and IntlDateFormatter.
 */
final class MissingRequirementException extends LocalizationException
{
    public function __construct(string $requirement)
    {
        parent::__construct(
            "Missing requirement: {$requirement}. " .
            "Install via: apt-get install php8.2-intl (Ubuntu/Debian) " .
            "or brew install php@8.2 (macOS)"
        );
    }
}
