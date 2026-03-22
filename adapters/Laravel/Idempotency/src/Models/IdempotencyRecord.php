<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyRecord extends Model
{
    protected $table = 'nexus_idempotency_records';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'tenant_id',
        'operation_ref',
        'client_key',
        'request_fingerprint',
        'attempt_token',
        'status',
        'result_envelope',
        'expires_at',
    ];

    protected $casts = [
        'result_envelope' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
