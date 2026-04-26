<?php

declare(strict_types=1);

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLineReadInterface;

class NormalizationSourceLine extends Model implements NormalizationSourceLineReadInterface
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

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getTenantId(): string
    {
        return (string) $this->tenant_id;
    }

    public function getQuoteSubmissionId(): string
    {
        return (string) $this->quote_submission_id;
    }

    public function getRfqLineItemId(): string
    {
        if ($this->rfq_line_item_id === null) {
            throw new DomainException('rfq_line_item_id is null');
        }

        return (string) $this->rfq_line_item_id;
    }

    public function getRawData(): array
    {
        return is_array($this->raw_data) ? $this->raw_data : [];
    }

    /**
     * @return array{source_description: string|null, rfq_line_item_id: string|null, quantity: string|null, uom: string|null, unit_price: string|null}
     */
    public function effectiveValues(): array
    {
        return [
            'source_description' => $this->source_description !== null ? (string) $this->source_description : null,
            'rfq_line_item_id' => $this->rfq_line_item_id !== null ? (string) $this->rfq_line_item_id : null,
            'quantity' => $this->source_quantity !== null ? number_format((float) $this->source_quantity, 4, '.', '') : null,
            'uom' => $this->source_uom !== null ? (string) $this->source_uom : null,
            'unit_price' => $this->source_unit_price !== null ? number_format((float) $this->source_unit_price, 4, '.', '') : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function providerProvenance(): ?array
    {
        $providerProvenance = $this->getRawData()['provider_provenance'] ?? null;

        return is_array($providerProvenance) ? $providerProvenance : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestOverrideAudit(): ?array
    {
        $overrideAudit = $this->getRawData()['override_audit'] ?? null;

        return is_array($overrideAudit) ? $overrideAudit : null;
    }

    public function hasBuyerOverride(): bool
    {
        $override = $this->getRawData()['override'] ?? null;

        return match (true) {
            $override === null, $override === '', $override === [] => false,
            is_string($override) => trim($override) !== '',
            default => true,
        };
    }
}
