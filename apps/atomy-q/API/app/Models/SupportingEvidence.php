<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SupportingEvidence extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'supporting_evidence';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'vendor_id',
        'quote_submission_id',
        'award_id',
        'reason',
        'original_filename',
        'file_type',
        'storage_path',
        'checksum',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
