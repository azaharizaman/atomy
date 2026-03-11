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
        'file_name',
        'file_path',
        'status',
        'parse_confidence',
        'validation_result',
        'parsed_data',
        'uploaded_by',
    ];

    protected $casts = [
        'parse_confidence' => 'decimal:2',
        'validation_result' => 'array',
        'parsed_data' => 'array',
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
}
