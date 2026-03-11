<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'integrations';

    protected $fillable = [
        'tenant_id',
        'name',
        'connector_type',
        'config',
        'status',
        'last_health_check_at',
        'health_status',
        'created_by',
    ];

    protected $casts = [
        'config' => 'array',
        'last_health_check_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<IntegrationJob>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(IntegrationJob::class, 'integration_id');
    }
}
