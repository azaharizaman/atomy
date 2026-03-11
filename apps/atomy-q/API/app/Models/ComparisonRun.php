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
        'run_type',
        'status',
        'scoring_model_id',
        'matrix_data',
        'scoring_data',
        'approval_data',
        'readiness_data',
        'decision_trail',
        'is_locked',
        'locked_at',
        'locked_by',
        'created_by',
    ];

    protected $casts = [
        'matrix_data' => 'array',
        'scoring_data' => 'array',
        'approval_data' => 'array',
        'readiness_data' => 'array',
        'decision_trail' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
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
     * @return BelongsTo<ScoringModel, $this>
     */
    public function scoringModel(): BelongsTo
    {
        return $this->belongsTo(ScoringModel::class, 'scoring_model_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
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
