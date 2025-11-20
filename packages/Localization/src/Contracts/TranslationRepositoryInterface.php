<?php

declare(strict_types=1);

namespace Nexus\Localization\Contracts;

use Nexus\Localization\ValueObjects\Locale;

/**
 * Translation repository interface.
 *
 * Handles translation key retrieval with locale fallback support.
 *
 * PHASE 2 FEATURE - Currently throws FeatureNotImplementedException.
 *
 * This interface defines the contract for future translation system implementation.
 * The application layer will implement this using database or file-based storage.
 */
interface TranslationRepositoryInterface
{
    /**
     * Translate a key for the given locale.
     *
     * Uses fallback chain if key not found in requested locale.
     * Returns the key itself if no translation found in entire chain.
     *
     * @param string $key Translation key (e.g., 'validation.required')
     * @param Locale $locale Target locale
     * @param array<string, mixed> $replacements Placeholder replacements
     * @return string Translated string with replacements applied
     *
     * @throws \Nexus\Localization\Exceptions\FeatureNotImplementedException (Phase 1)
     * @throws \Nexus\Localization\Exceptions\TranslationKeyNotFoundException (Phase 2)
     */
    public function translate(string $key, Locale $locale, array $replacements = []): string;

    /**
     * Check if a translation key exists for a locale.
     *
     * @throws \Nexus\Localization\Exceptions\FeatureNotImplementedException (Phase 1)
     */
    public function has(string $key, Locale $locale): bool;

    /**
     * Get all translations for a locale and optional group.
     *
     * @param string|null $group Optional group filter (e.g., 'validation', 'messages')
     * @return array<string, string>
     *
     * @throws \Nexus\Localization\Exceptions\FeatureNotImplementedException (Phase 1)
     */
    public function getAll(Locale $locale, ?string $group = null): array;
}
