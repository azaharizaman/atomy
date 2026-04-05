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
        'quote_submission_id',
        'rfq_line_item_id',
        'source_vendor',
        'source_description',
        'source_quantity',
        'source_uom',
        'source_unit_price',
        'raw_data',
        'sort_order',
        'ai_confidence',
        'taxonomy_code',
        'mapping_version',
    ];

    protected $casts = [
        'source_quantity' => 'decimal:4',
        'source_unit_price' => 'decimal:4',
        'ai_confidence' => 'decimal:2',
        'raw_data' => 'array',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
    public function rfqLineItem(): BelongsTo
    {
        return $this->belongsTo(RfqLineItem::class, 'rfq_line_item_id');
    }

    /**
     * @return HasMany<NormalizationConflict>
     */
    public function conflicts(): HasMany
    {
        return $this->hasMany(NormalizationConflict::class, 'normalization_source_line_id');
    }
}
