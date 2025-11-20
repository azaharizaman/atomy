<?php

declare(strict_types=1);

namespace App\Repositories;

use Nexus\Localization\Contracts\TranslationRepositoryInterface;
use Nexus\Localization\Exceptions\FeatureNotImplementedException;
use Nexus\Localization\ValueObjects\Locale;

/**
 * Translation repository stub implementation.
 *
 * PHASE 2 FEATURE - All methods throw FeatureNotImplementedException.
 *
 * Future implementation will use either:
 * - Database table (translations) for user-customizable translations
 * - Laravel lang/ files via adapter pattern for static translations
 */
final class DbTranslationRepository implements TranslationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function translate(string $key, Locale $locale, array $replacements = []): string
    {
        throw new FeatureNotImplementedException('Translation system - Phase 2 feature');
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key, Locale $locale): bool
    {
        throw new FeatureNotImplementedException('Translation system - Phase 2 feature');
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(Locale $locale, ?string $group = null): array
    {
        throw new FeatureNotImplementedException('Translation system - Phase 2 feature');
    }
}
