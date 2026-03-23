<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for {@see \Nexus\Idempotency\Domain\IdempotencyRecord} persistence.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $operation_ref
 * @property string $client_key
 * @property string $request_fingerprint
 * @property string $attempt_token
 * @property string $status
 * @property string|null $result_envelope
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $last_transition_at
 */
final class IdempotencyRecordModel extends Model
{
    public $timestamps = false;

    protected $table = 'nexus_idempotency_records';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'operation_ref',
        'client_key',
        'request_fingerprint',
        'attempt_token',
        'status',
        'result_envelope',
        'created_at',
        'last_transition_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'last_transition_at' => 'datetime',
        ];
    }
}
