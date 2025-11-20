<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Localization\ValueObjects\Locale as LocaleVO;

/**
 * Locale model.
 *
 * Represents a locale configuration with CLDR formatting rules.
 *
 * @property string $code
 * @property string|null $parent_locale_code
 * @property string $name
 * @property string $native_name
 * @property string $text_direction
 * @property string $status
 * @property string $decimal_separator
 * @property string $thousands_separator
 * @property string $date_format
 * @property string $time_format
 * @property string $datetime_format
 * @property string $currency_position
 * @property int $first_day_of_week
 * @property array $metadata
 */
class Locale extends Model
{
    protected $table = 'locales';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'parent_locale_code',
        'name',
        'native_name',
        'text_direction',
        'status',
        'decimal_separator',
        'thousands_separator',
        'date_format',
        'time_format',
        'datetime_format',
        'currency_position',
        'first_day_of_week',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'first_day_of_week' => 'integer',
    ];

    /**
     * Get parent locale relationship.
     */
    public function parent()
    {
        return $this->belongsTo(Locale::class, 'parent_locale_code', 'code');
    }

    /**
     * Get child locales relationship.
     */
    public function children()
    {
        return $this->hasMany(Locale::class, 'parent_locale_code', 'code');
    }

    /**
     * Get user preferences using this locale.
     */
    public function userPreferences()
    {
        return $this->hasMany(UserLocalePreference::class, 'locale_code', 'code');
    }

    /**
     * Convert to Locale value object.
     */
    public function toValueObject(): LocaleVO
    {
        return new LocaleVO($this->code);
    }

    /**
     * Scope to get active locales only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get draft locales.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get deprecated locales.
     */
    public function scopeDeprecated($query)
    {
        return $query->where('status', 'deprecated');
    }

    /**
     * Check if locale is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if locale is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if locale is deprecated.
     */
    public function isDeprecated(): bool
    {
        return $this->status === 'deprecated';
    }

    /**
     * Check if locale uses right-to-left text direction.
     */
    public function isRightToLeft(): bool
    {
        return $this->text_direction === 'rtl';
    }
}
