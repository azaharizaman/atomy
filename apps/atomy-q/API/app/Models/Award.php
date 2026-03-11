<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Award extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'awards';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'comparison_run_id',
        'status',
        'winner_vendor_id',
        'split_allocation',
        'savings_amount',
        'savings_percentage',
        'standstill_until',
        'created_by',
    ];

    protected $casts = [
        'split_allocation' => 'array',
        'savings_amount' => 'decimal:2',
        'savings_percentage' => 'decimal:2',
        'standstill_until' => 'datetime',
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Handoff>
     */
    public function handoffs(): HasMany
    {
        return $this->hasMany(Handoff::class, 'award_id');
    }
}
