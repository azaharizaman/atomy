<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

/**
 * Exception thrown when a locale code is not found in the repository.
 */
final class LocaleNotFoundException extends LocalizationException
{
    public function __construct(string $localeCode)
    {
        parent::__construct("Locale not found: {$localeCode}");
    }
}
