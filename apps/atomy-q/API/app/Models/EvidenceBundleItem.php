<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EvidenceBundleItem extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'evidence_bundle_items';

    protected $fillable = [
        'tenant_id',
        'evidence_bundle_id',
        'source_type',
        'source_id',
        'artifact_kind',
        'label',
        'storage_path',
        'checksum',
        'metadata',
        'included_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'included_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
