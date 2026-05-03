<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<Rfq, $this>
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<QuoteSubmission, $this>
     */
    public function quoteSubmission(): BelongsTo
    {
        return $this->belongsTo(QuoteSubmission::class, 'quote_submission_id');
    }

    /**
     * @return BelongsTo<Award, $this>
     */
    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class, 'award_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
