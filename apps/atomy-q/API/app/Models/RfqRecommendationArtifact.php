<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqRecommendationArtifact extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'rfq_recommendation_artifacts';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'feature_key',
        'status',
        'canonical_payload',
        'provenance',
    ];

    protected $casts = [
        'canonical_payload' => 'array',
        'provenance' => 'array',
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
}
