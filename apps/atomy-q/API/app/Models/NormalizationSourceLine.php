<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NormalizationSourceLine extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'normalization_source_lines';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'quote_submission_id',
        'vendor_id',
        'description',
        'quantity',
        'uom',
        'unit_price',
        'currency',
        'confidence',
        'rfq_line_id',
        'mapped_to_rfq_line_id',
        'override_data',
        'is_overridden',
        'validation_warning',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'confidence' => 'decimal:2',
        'override_data' => 'array',
        'is_overridden' => 'boolean',
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
     * @return BelongsTo<QuoteSubmission, $this>
     */
    public function quoteSubmission(): BelongsTo
    {
        return $this->belongsTo(QuoteSubmission::class, 'quote_submission_id');
    }

    /**
     * @return BelongsTo<RfqLineItem, $this>
     */
    public function rfqLine(): BelongsTo
    {
        return $this->belongsTo(RfqLineItem::class, 'rfq_line_id');
    }

    /**
     * @return BelongsTo<RfqLineItem, $this>
     */
    public function mappedToRfqLine(): BelongsTo
    {
        return $this->belongsTo(RfqLineItem::class, 'mapped_to_rfq_line_id');
    }

    /**
     * @return HasMany<NormalizationConflict>
     */
    public function conflicts(): HasMany
    {
        return $this->hasMany(NormalizationConflict::class, 'source_line_id');
    }
}
