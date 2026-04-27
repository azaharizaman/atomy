<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasUlids, HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'vendors';

    protected $fillable = [
        'tenant_id',
        'registration_number',
        'tax_id',
        'legal_name',
        'display_name',
        'country_of_registration',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'status',
        'onboarded_at',
        'metadata',
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

    public function getPrimaryContactPhoneAttribute(mixed $value): ?string
    {
        return self::trimmedOrNull($value);
    }

    public function setPrimaryContactPhoneAttribute(mixed $value): void
    {
        $this->attributes['primary_contact_phone'] = self::trimmedOrNull($value) ?? '';
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
