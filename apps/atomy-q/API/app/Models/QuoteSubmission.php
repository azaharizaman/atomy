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
        'file_path',
        'file_type',
        'status',
        'submitted_at',
        'confidence',
        'line_items_count',
        'warnings_count',
        'errors_count',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'submitted_at' => 'datetime',
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
     * @return HasMany<NormalizationSourceLine>
     */
    public function normalizationSourceLines(): HasMany
    {
        return $this->hasMany(NormalizationSourceLine::class, 'quote_submission_id');
    }
}
