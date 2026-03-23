<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteSubmission extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'quote_submissions';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'vendor_id',
        'vendor_name',
        'uploaded_by',
        'file_path',
        'file_type',
        'original_filename',
        'status',
        'submitted_at',
        'confidence',
        'line_items_count',
        'warnings_count',
        'errors_count',
        'error_code',
        'error_message',
        'processing_started_at',
        'processing_completed_at',
        'parsed_at',
        'retry_count',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'parsed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * @return BelongsTo<Rfq, $this>
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<NormalizationSourceLine>
     */
    public function normalizationSourceLines(): HasMany
    {
        return $this->hasMany(NormalizationSourceLine::class, 'quote_submission_id');
    }

    public function blockingIssueCount(): int
    {
        return max((int) ($this->errors_count ?? 0), 0);
    }
}
