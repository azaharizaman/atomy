<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

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
