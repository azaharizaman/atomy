<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NormalizationConflict extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'normalization_conflicts';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'source_line_id',
        'conflict_type',
        'description',
        'status',
        'resolution_data',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolution_data' => 'array',
        'resolved_at' => 'datetime',
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
     * @return BelongsTo<NormalizationSourceLine, $this>
     */
    public function sourceLine(): BelongsTo
    {
        return $this->belongsTo(NormalizationSourceLine::class, 'source_line_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
