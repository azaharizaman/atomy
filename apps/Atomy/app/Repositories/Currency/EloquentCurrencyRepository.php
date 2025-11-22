<?php

declare(strict_types=1);

namespace App\Repositories\Currency;

use App\Models\Currency\Currency as CurrencyModel;
use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\ValueObjects\Currency;

/**
 * Eloquent Currency Repository
 */
final readonly class EloquentCurrencyRepository implements CurrencyRepositoryInterface
{
    public function findByCode(string $code): ?Currency
    {
        $model = CurrencyModel::where('code', $code)->first();

        return $model ? $model->toValueObject() : null;
    }

    public function getAll(): array
    {
        $currencies = [];
        
        foreach (CurrencyModel::where('is_active', true)->get() as $model) {
            $currencies[$model->code] = $model->toValueObject();
        }

        return $currencies;
    }

    public function exists(string $code): bool
    {
        return CurrencyModel::where('code', $code)->exists();
    }

    public function save(Currency $currency): void
    {
        CurrencyModel::updateOrCreate(
            ['code' => $currency->code],
            [
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'decimal_places' => $currency->decimalPlaces,
                'numeric_code' => $currency->numericCode,
                'is_active' => true,
            ]
        );
    }
}
