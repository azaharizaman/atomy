<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Locale as LocaleModel;
use Nexus\Localization\Contracts\LocaleRepositoryInterface;
use Nexus\Localization\Enums\CurrencyPosition;
use Nexus\Localization\Enums\TextDirection;
use Nexus\Localization\Exceptions\CircularLocaleReferenceException;
use Nexus\Localization\Exceptions\LocaleNotFoundException;
use Nexus\Localization\ValueObjects\FallbackChain;
use Nexus\Localization\ValueObjects\Locale;
use Nexus\Localization\ValueObjects\LocaleSettings;

/**
 * Database locale repository implementation.
 *
 * Retrieves locale data from the locales table.
 */
final class DbLocaleRepository implements LocaleRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getLocaleSettings(Locale $locale): LocaleSettings
    {
        $model = LocaleModel::find($locale->code());

        if (!$model) {
            throw new LocaleNotFoundException($locale->code());
        }

        return new LocaleSettings(
            locale: $locale,
            name: $model->name,
            nativeName: $model->native_name,
            textDirection: TextDirection::from($model->text_direction),
            decimalSeparator: $model->decimal_separator,
            thousandsSeparator: $model->thousands_separator,
            dateFormat: $model->date_format,
            timeFormat: $model->time_format,
            datetimeFormat: $model->datetime_format,
            currencyPosition: CurrencyPosition::from($model->currency_position),
            firstDayOfWeek: $model->first_day_of_week,
            metadata: $model->metadata ?? [],
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFallbackChain(Locale $locale): FallbackChain
    {
        $chain = FallbackChain::create($locale);
        $current = $locale;

        // Build chain by traversing parent locales
        while (true) {
            $parent = $this->getParentLocale($current);

            if ($parent === null) {
                break;
            }

            try {
                $chain = $chain->addLocale($parent);
                $current = $parent;
            } catch (CircularLocaleReferenceException $e) {
                // Re-throw with context
                throw $e;
            }
        }

        return $chain;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentLocale(Locale $locale): ?Locale
    {
        $model = LocaleModel::find($locale->code());

        if (!$model || !$model->parent_locale_code) {
            return null;
        }

        return new Locale($model->parent_locale_code);
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveLocales(): array
    {
        return LocaleModel::active()
            ->orderBy('name')
            ->get()
            ->map(fn($model) => new Locale($model->code))
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllLocalesForAdmin(): array
    {
        return LocaleModel::orderBy('name')
            ->get()
            ->map(fn($model) => new Locale($model->code))
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function isActiveLocale(Locale $locale): bool
    {
        $model = LocaleModel::find($locale->code());

        return $model && $model->isActive();
    }

    /**
     * {@inheritDoc}
     */
    public function findByCode(string $code): ?Locale
    {
        $model = LocaleModel::find($code);

        if (!$model) {
            return null;
        }

        return new Locale($code);
    }
}
