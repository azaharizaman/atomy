<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LocaleUpdated;
use App\Repositories\CachedLocaleRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Flush locale cache when a locale is updated.
 *
 * Listens to LocaleUpdated event and invalidates cached locale data.
 */
class FlushLocaleCache
{
    public function __construct(
        private readonly CachedLocaleRepository $localeRepository,
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(LocaleUpdated $event): void
    {
        // Flush cache for the specific locale
        $this->localeRepository->flushLocaleCache($event->locale);

        // Also flush the active/all locale lists since they may have changed
        Cache::forget('locale:active_list');
        Cache::forget('locale:all_list');
    }
}
