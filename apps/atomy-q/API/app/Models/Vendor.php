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
    ];

    protected $casts = [
        'metadata' => 'array',
        'onboarded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
