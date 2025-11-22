<?php

declare(strict_types=1);

namespace App\Models\Currency;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Currency\ValueObjects\Currency as CurrencyVO;

/**
 * Currency Eloquent Model
 * 
 * Storage model for ISO 4217 currency metadata.
 */
final class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'code',
        'name',
        'symbol',
        'decimal_places',
        'numeric_code',
        'is_active',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Convert to package value object
     */
    public function toValueObject(): CurrencyVO
    {
        return new CurrencyVO(
            code: $this->code,
            name: $this->name,
            symbol: $this->symbol,
            decimalPlaces: $this->decimal_places,
            numericCode: $this->numeric_code
        );
    }

    /**
     * Get exchange rates from this currency
     */
    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency', 'code');
    }

    /**
     * Get exchange rates to this currency
     */
    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency', 'code');
    }

    // Interface-like accessors for consistency
    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getDecimalPlaces(): int
    {
        return $this->decimal_places;
    }

    public function getNumericCode(): string
    {
        return $this->numeric_code;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updated_at;
    }
}
