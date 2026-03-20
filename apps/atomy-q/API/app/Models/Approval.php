<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Approval extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'approvals';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'comparison_run_id',
        'type',
        'status',
        'requested_by',
        'requested_at',
        'amount',
        'currency',
        'level',
        'notes',
        'approved_at',
        'approved_by',
        'snoozed_until',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'amount' => 'decimal:2',
        'level' => 'integer',
        'approved_at' => 'datetime',
        'snoozed_until' => 'datetime',
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
     * @return BelongsTo<ComparisonRun, $this>
     */
    public function comparisonRun(): BelongsTo
    {
        return $this->belongsTo(ComparisonRun::class, 'comparison_run_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<ApprovalHistory>
     */
    public function history(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'approval_id');
    }
}
