<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant Impersonation Log Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $original_user_id
 * @property string|null $reason
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $ended_at
 * @property int|null $duration_seconds
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class TenantImpersonation extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'original_user_id',
        'reason',
        'started_at',
        'ended_at',
        'duration_seconds',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
