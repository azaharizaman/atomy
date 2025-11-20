<?php

declare(strict_types=1);

namespace Nexus\Localization\Contracts;

use Nexus\Localization\ValueObjects\FallbackChain;
use Nexus\Localization\ValueObjects\Locale;
use Nexus\Localization\ValueObjects\LocaleSettings;

/**
 * Repository interface for locale data retrieval.
 *
 * This interface must be implemented in the application layer to provide
 * locale metadata from the database or other storage.
 */
interface LocaleRepositoryInterface
{
    /**
     * Retrieve locale settings for a given locale.
     *
     * @throws \Nexus\Localization\Exceptions\LocaleNotFoundException
     */
    public function getLocaleSettings(Locale $locale): LocaleSettings;

    /**
     * Build complete fallback chain for a locale.
     *
     * Example: ms_MY → ms → en_US
     *
     * @throws \Nexus\Localization\Exceptions\CircularLocaleReferenceException
     * @throws \Nexus\Localization\Exceptions\UnsupportedLocaleException
     */
    public function getFallbackChain(Locale $locale): FallbackChain;

    /**
     * Get parent locale for a given locale.
     *
     * Returns null if no parent exists or system default is reached.
     */
    public function getParentLocale(Locale $locale): ?Locale;

    /**
     * Get all active locales (available for user selection).
     *
     * @return array<int, Locale>
     */
    public function getActiveLocales(): array;

    /**
     * Get all locales including draft and deprecated (admin UI only).
     *
     * @return array<int, Locale>
     */
    public function getAllLocalesForAdmin(): array;

    /**
     * Check if a locale exists and is active.
     */
    public function isActiveLocale(Locale $locale): bool;

    /**
     * Find locale by code.
     *
     * Returns null if not found.
     */
    public function findByCode(string $code): ?Locale;
}
