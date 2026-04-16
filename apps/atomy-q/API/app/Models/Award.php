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
        'vendor_id',
        'status',
        'amount',
        'currency',
        'split_details',
        'protest_id',
        'signoff_at',
        'signed_off_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'split_details' => 'array',
        'signoff_at' => 'datetime',
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
    public function signedOffByUser(): BelongsTo
    {
        return $this->signedOffByRelation('signedOffByUser');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->signedOffByRelation('creator');
    }

    /**
     * @return HasMany<Handoff>
     */
    public function handoffs(): HasMany
    {
        return $this->hasMany(Handoff::class, 'award_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    private function signedOffByRelation(string $relation): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_off_by', 'id', $relation);
    }
}
