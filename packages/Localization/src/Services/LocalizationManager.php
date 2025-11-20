<?php

declare(strict_types=1);

namespace Nexus\Localization\Services;

use Nexus\Localization\Contracts\LocaleRepositoryInterface;
use Nexus\Localization\Contracts\LocaleResolverInterface;
use Nexus\Localization\Contracts\TranslationRepositoryInterface;
use Nexus\Localization\Exceptions\FeatureNotImplementedException;
use Nexus\Localization\ValueObjects\FallbackChain;
use Nexus\Localization\ValueObjects\Locale;
use Nexus\Localization\ValueObjects\LocaleSettings;

/**
 * Localization manager service.
 *
 * Main orchestrator for localization operations. Provides high-level
 * API for locale resolution and formatting.
 */
final class LocalizationManager
{
    public function __construct(
        private readonly LocaleRepositoryInterface $localeRepository,
        private readonly LocaleResolverInterface $localeResolver,
        private readonly TranslationRepositoryInterface $translationRepository,
    ) {
    }

    /**
     * Get the current user's active locale.
     */
    public function getCurrentLocale(): Locale
    {
        return $this->localeResolver->resolve();
    }

    /**
     * Get locale settings for a specific locale.
     */
    public function getLocaleSettings(Locale $locale): LocaleSettings
    {
        return $this->localeRepository->getLocaleSettings($locale);
    }

    /**
     * Get fallback chain for a locale.
     */
    public function getFallbackChain(Locale $locale): FallbackChain
    {
        return $this->localeRepository->getFallbackChain($locale);
    }

    /**
     * Get all active locales available for user selection.
     *
     * @return array<int, Locale>
     */
    public function getAvailableLocales(): array
    {
        return $this->localeRepository->getActiveLocales();
    }

    /**
     * Translate a key for the current user's locale.
     *
     * PHASE 2 FEATURE - Currently throws FeatureNotImplementedException.
     *
     * @param array<string, mixed> $replacements
     * @throws FeatureNotImplementedException
     */
    public function translate(string $key, array $replacements = []): string
    {
        throw new FeatureNotImplementedException('Translation system');
    }

    /**
     * Translate a key for a specific locale.
     *
     * PHASE 2 FEATURE - Currently throws FeatureNotImplementedException.
     *
     * @param array<string, mixed> $replacements
     * @throws FeatureNotImplementedException
     */
    public function translateFor(Locale $locale, string $key, array $replacements = []): string
    {
        throw new FeatureNotImplementedException('Translation system');
    }

    /**
     * Check if a locale is active and available.
     */
    public function isLocaleActive(Locale $locale): bool
    {
        return $this->localeRepository->isActiveLocale($locale);
    }

    /**
     * Find locale by code string.
     */
    public function findLocale(string $code): ?Locale
    {
        return $this->localeRepository->findByCode($code);
    }
}
