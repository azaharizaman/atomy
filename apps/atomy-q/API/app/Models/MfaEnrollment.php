<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class MfaEnrollment extends Model
{
    use HasUlids;

    protected $table = 'mfa_enrollments';
    protected $fillable = [
        'tenant_id',
        'user_id',
        'method',
        'secret',
        'is_active',
        'verified',
        'revoked',
        'is_primary',
        'verified_at',
        'last_used_at',
    ];
    protected $casts = [
        'secret' => 'encrypted',
        'is_active' => 'boolean',
        'verified' => 'boolean',
        'revoked' => 'boolean',
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];
}
