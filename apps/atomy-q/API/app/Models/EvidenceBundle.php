<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
