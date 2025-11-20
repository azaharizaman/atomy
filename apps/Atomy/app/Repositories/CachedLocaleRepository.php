<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Nexus\Localization\Contracts\LocaleRepositoryInterface;
use Nexus\Localization\ValueObjects\FallbackChain;
use Nexus\Localization\ValueObjects\Locale;
use Nexus\Localization\ValueObjects\LocaleSettings;

/**
 * Cached locale repository decorator.
 *
 * Wraps DbLocaleRepository with Redis caching for performance.
 * Cache TTL: 12 hours (43200 seconds) - locale settings are very stable.
 */
final class CachedLocaleRepository implements LocaleRepositoryInterface
{
    private const CACHE_TTL = 43200; // 12 hours
    private const CACHE_PREFIX = 'locale:';
    private const CACHE_TAG = 'locales';

    public function __construct(
        private readonly DbLocaleRepository $baseRepository,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleSettings(Locale $locale): LocaleSettings
    {
        $key = self::CACHE_PREFIX . 'settings:' . $locale->code();

        return Cache::tags([self::CACHE_TAG])->remember(
            $key,
            self::CACHE_TTL,
            fn() => $this->baseRepository->getLocaleSettings($locale)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFallbackChain(Locale $locale): FallbackChain
    {
        $key = self::CACHE_PREFIX . 'chain:' . $locale->code();

        return Cache::tags([self::CACHE_TAG])->remember(
            $key,
            self::CACHE_TTL,
            fn() => $this->baseRepository->getFallbackChain($locale)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getParentLocale(Locale $locale): ?Locale
    {
        $key = self::CACHE_PREFIX . 'parent:' . $locale->code();

        return Cache::tags([self::CACHE_TAG])->remember(
            $key,
            self::CACHE_TTL,
            fn() => $this->baseRepository->getParentLocale($locale)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveLocales(): array
    {
        $key = self::CACHE_PREFIX . 'active_list';

        return Cache::tags([self::CACHE_TAG])->remember(
            $key,
            self::CACHE_TTL,
            fn() => $this->baseRepository->getActiveLocales()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAllLocalesForAdmin(): array
    {
        $key = self::CACHE_PREFIX . 'all_list';

        return Cache::tags([self::CACHE_TAG])->remember(
            $key,
            self::CACHE_TTL,
            fn() => $this->baseRepository->getAllLocalesForAdmin()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isActiveLocale(Locale $locale): bool
    {
        // Don't cache this check - it's fast enough
        return $this->baseRepository->isActiveLocale($locale);
    }

    /**
     * {@inheritDoc}
     */
    public function findByCode(string $code): ?Locale
    {
        // Don't cache this - it's just a simple lookup
        return $this->baseRepository->findByCode($code);
    }

    /**
     * Flush cache for a specific locale.
     *
     * Called by LocaleUpdated event listener.
     */
    public function flushLocaleCache(Locale $locale): void
    {
        $code = $locale->code();

        Cache::forget(self::CACHE_PREFIX . 'settings:' . $code);
        Cache::forget(self::CACHE_PREFIX . 'chain:' . $code);
        Cache::forget(self::CACHE_PREFIX . 'parent:' . $code);
    }

    /**
     * Flush all locale caches.
     *
     * Useful when multiple locales are updated.
     */
    public function flushAllCaches(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }
}
