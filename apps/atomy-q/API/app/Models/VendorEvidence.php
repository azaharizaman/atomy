<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Tenant-scoped vendor governance evidence used for scoring and narratives.
 *
 * Evidence freshness, review status, expiry, domain, and type feed deterministic
 * governance scores before any AI-generated narrative is produced.
 */
final class VendorEvidence extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'vendor_evidence';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'domain',
        'type',
        'title',
        'source',
        'observed_at',
        'expires_at',
        'review_status',
        'reviewed_by',
        'notes',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
