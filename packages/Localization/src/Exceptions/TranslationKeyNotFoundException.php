<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

/**
 * Exception thrown when a translation key is not found.
 *
 * This exception is reserved for Phase 2 translation system implementation.
 */
final class TranslationKeyNotFoundException extends LocalizationException
{
    public function __construct(string $key, string $locale)
    {
        parent::__construct("Translation key '{$key}' not found for locale '{$locale}'");
    }
}
