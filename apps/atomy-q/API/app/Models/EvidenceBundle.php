<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvidenceBundle extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'evidence_bundles';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'comparison_run_id',
        'approval_id',
        'award_id',
        'type',
        'status',
        'version',
        'manifest',
        'checksum',
        'finalized_at',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'manifest' => 'array',
        'finalized_at' => 'datetime',
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
     * @return BelongsTo<Approval, $this>
     */
    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }

    /**
     * @return BelongsTo<Award, $this>
     */
    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class, 'award_id');
    }

    /**
     * @return HasMany<EvidenceBundleItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EvidenceBundleItem::class, 'evidence_bundle_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
