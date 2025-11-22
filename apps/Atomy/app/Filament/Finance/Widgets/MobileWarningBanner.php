<?php

declare(strict_types=1);

namespace App\Filament\Finance\Widgets;

use Filament\Widgets\Widget;

/**
 * Mobile Warning Banner
 * 
 * Displays warning message when accessing Finance panel from mobile devices.
 * Finance module is optimized for desktop use only.
 */
class MobileWarningBanner extends Widget
{
    protected static string $view = 'filament.finance.widgets.mobile-warning-banner';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -10; // Display at the top

    /**
     * Check if widget should be visible (mobile only)
     */
    public static function canView(): bool
    {
        // Simple mobile detection via user agent
        $userAgent = request()->header('User-Agent', '');
        
        return str_contains(strtolower($userAgent), 'mobile')
            || str_contains(strtolower($userAgent), 'android')
            || str_contains(strtolower($userAgent), 'iphone')
            || str_contains(strtolower($userAgent), 'ipod')
            || str_contains(strtolower($userAgent), 'blackberry')
            || str_contains(strtolower($userAgent), 'windows phone');
    }
}
