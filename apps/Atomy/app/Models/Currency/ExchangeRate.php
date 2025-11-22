<?php

declare(strict_types=1);

namespace App\Models\Currency;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Finance\ValueObjects\ExchangeRate as ExchangeRateVO;

/**
 * Exchange Rate Eloquent Model
 * 
 * Storage model for currency exchange rates.
 */
final class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'immutable_date',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Convert to package value object
     */
    public function toValueObject(): ExchangeRateVO
    {
        return ExchangeRateVO::create(
            fromCurrency: $this->from_currency,
            toCurrency: $this->to_currency,
            rate: $this->rate,
            effectiveDate: $this->effective_date
        );
    }

    /**
     * Get from currency relation
     */
    public function fromCurrencyModel(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency', 'code');
    }

    /**
     * Get to currency relation
     */
    public function toCurrencyModel(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency', 'code');
    }

    // Interface-like accessors
    public function getId(): string
    {
        return $this->id;
    }

    public function getFromCurrency(): string
    {
        return $this->from_currency;
    }

    public function getToCurrency(): string
    {
        return $this->to_currency;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getEffectiveDate(): DateTimeImmutable
    {
        return $this->effective_date;
    }

    public function getSource(): ?string
    {
        return $this->source;
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
