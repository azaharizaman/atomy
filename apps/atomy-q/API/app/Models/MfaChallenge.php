<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MfaChallenge extends Model
{
    protected $table = 'mfa_challenges';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'tenant_id',
        'method',
        'expires_at',
        'consumed_at',
        'attempt_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'attempt_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

