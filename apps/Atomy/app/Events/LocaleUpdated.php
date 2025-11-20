<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nexus\Localization\ValueObjects\Locale;

/**
 * Event dispatched when a locale is updated.
 *
 * Used for cache invalidation via event-driven pattern.
 */
class LocaleUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Locale $locale,
    ) {
    }
}
