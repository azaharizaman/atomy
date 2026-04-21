<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'vendors';

    protected $fillable = [
        'tenant_id',
        'name',
        'trading_name',
        'registration_number',
        'tax_id',
        'country_code',
        'email',
        'phone',
        'status',
        'onboarded_at',
        'metadata',
        'legal_name',
        'display_name',
        'country_of_registration',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'approved_by_user_id',
        'approved_at',
        'approval_note',
    ];

    protected $casts = [
        'metadata' => 'array',
        'onboarded_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $vendor): void {
            self::syncLegacyField($vendor, 'legal_name', ['name']);
            self::syncLegacyField($vendor, 'display_name', ['trading_name', 'name']);
            self::syncLegacyField($vendor, 'country_of_registration', ['country_code']);
            self::syncLegacyField($vendor, 'primary_contact_name', ['trading_name', 'name']);
            self::syncLegacyField($vendor, 'primary_contact_email', ['email']);
            self::syncLegacyField($vendor, 'primary_contact_phone', ['phone']);
        });
    }

    /**
     * @param list<string> $legacyAttributes
     */
    private static function syncLegacyField(self $vendor, string $canonicalAttribute, array $legacyAttributes): void
    {
        $currentValue = self::trimmedOrNull($vendor->getAttribute($canonicalAttribute));

        if ($currentValue !== null) {
            return;
        }

        if ($vendor->isDirty($canonicalAttribute)) {
            return;
        }

        foreach ($legacyAttributes as $legacyAttribute) {
            $legacyValue = self::trimmedOrNull($vendor->getAttribute($legacyAttribute));

            if ($legacyValue !== null) {
                $vendor->setAttribute($canonicalAttribute, $legacyValue);
                return;
            }
        }
    }

    private static function trimmedOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
