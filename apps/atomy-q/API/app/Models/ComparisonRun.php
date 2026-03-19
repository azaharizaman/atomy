<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComparisonRun extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'comparison_runs';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'name',
        'description',
        'idempotency_key',
        'is_preview',
        'created_by',
        'request_payload',
        'matrix_payload',
        'scoring_payload',
        'approval_payload',
        'response_payload',
        'readiness_payload',
        'status',
        'version',
        'expires_at',
        'discarded_at',
        'discarded_by',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'request_payload' => 'array',
        'matrix_payload' => 'array',
        'scoring_payload' => 'array',
        'approval_payload' => 'array',
        'response_payload' => 'array',
        'readiness_payload' => 'array',
        'version' => 'integer',
        'expires_at' => 'datetime',
        'discarded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Rfq, $this>
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Approval>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'comparison_run_id');
    }

    /**
     * @return HasMany<Award>
     */
    public function awards(): HasMany
    {
        return $this->hasMany(Award::class, 'comparison_run_id');
    }
}
