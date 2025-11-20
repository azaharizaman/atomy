<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * User locale preference model.
 *
 * Stores user-level locale and timezone preferences.
 *
 * @property string $id
 * @property string $user_id
 * @property string $tenant_id
 * @property string $locale_code
 * @property string $timezone
 */
class UserLocalePreference extends Model
{
    use BelongsToTenant;

    protected $table = 'user_locale_preferences';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'tenant_id',
        'locale_code',
        'timezone',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = \Illuminate\Support\Str::ulid()->toBase32();
            }
        });
    }

    /**
     * Get the user that owns the preference.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant that owns the preference.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Get the locale.
     */
    public function locale()
    {
        return $this->belongsTo(Locale::class, 'locale_code', 'code');
    }

    /**
     * Get locale value object.
     */
    public function getLocaleValueObject(): \Nexus\Localization\ValueObjects\Locale
    {
        return new \Nexus\Localization\ValueObjects\Locale($this->locale_code);
    }

    /**
     * Get timezone value object.
     */
    public function getTimezoneValueObject(): \Nexus\Localization\ValueObjects\Timezone
    {
        return \Nexus\Localization\ValueObjects\Timezone::fromString($this->timezone);
    }
}
