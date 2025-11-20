<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale to use when no user preference or tenant setting
    | is available. This is the final fallback in the resolution chain.
    |
    */
    'default_locale' => env('LOCALIZATION_DEFAULT_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Default Timezone
    |--------------------------------------------------------------------------
    |
    | The default timezone for users without a preference.
    |
    */
    'default_timezone' => env('LOCALIZATION_DEFAULT_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for locale data. Locale settings are very stable
    | so a long TTL (12 hours) is appropriate.
    |
    */
    'cache' => [
        'enabled' => env('LOCALIZATION_CACHE_ENABLED', true),
        'ttl' => env('LOCALIZATION_CACHE_TTL', 43200), // 12 hours in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | List of locale codes that are seeded by default. These represent
    | the initial set of locales available in the system.
    |
    */
    'seeded_locales' => [
        'en_US',
        'en',
        'ms_MY',
        'ms',
        'zh_CN',
        'zh',
        'id_ID',
        'th_TH',
        'vi_VN',
        'ja_JP',
        'ko_KR',
        'ar_SA',
        'fr_FR',
        'de_DE',
        'es_ES',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date/Time Format Styles
    |--------------------------------------------------------------------------
    |
    | Mapping of style names to IntlDateFormatter constants.
    | Used by DateTimeFormatter service.
    |
    */
    'date_styles' => [
        'none' => \IntlDateFormatter::NONE,
        'short' => \IntlDateFormatter::SHORT,
        'medium' => \IntlDateFormatter::MEDIUM,
        'long' => \IntlDateFormatter::LONG,
        'full' => \IntlDateFormatter::FULL,
    ],
];
