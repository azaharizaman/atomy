<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Sales\Contracts\SalesOrderLineInterface;
use Nexus\Sales\ValueObjects\DiscountRule;

class QuotationLine extends Model implements SalesOrderLineInterface
{
    use HasUlids;

    protected $table = 'sales_quotation_lines';

    public $timestamps = false;

    protected $fillable = [
        'quotation_id',
        'product_variant_id',
        'quantity',
        'uom_code',
        'unit_price',
        'line_subtotal',
        'tax_amount',
        'discount_amount',
        'line_total',
        'discount_rule',
        'line_notes',
        'line_sequence',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'line_subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
        'discount_rule' => 'array',
        'line_sequence' => 'integer',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getParentId(): string
    {
        return $this->quotation_id;
    }

    public function getProductVariantId(): string
    {
        return $this->product_variant_id;
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function getUomCode(): string
    {
        return $this->uom_code;
    }

    public function getUnitPrice(): float
    {
        return (float) $this->unit_price;
    }

    public function getLineSubtotal(): float
    {
        return (float) $this->line_subtotal;
    }

    public function getTaxAmount(): float
    {
        return (float) $this->tax_amount;
    }

    public function getDiscountAmount(): float
    {
        return (float) $this->discount_amount;
    }

    public function getLineTotal(): float
    {
        return (float) $this->line_total;
    }

    public function getDiscountRule(): ?DiscountRule
    {
        if ($this->discount_rule === null) {
            return null;
        }

        return DiscountRule::fromArray($this->discount_rule);
    }

    public function getLineNotes(): ?string
    {
        return $this->line_notes;
    }

    public function getLineSequence(): int
    {
        return $this->line_sequence;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'quotation_id' => $this->getParentId(),
            'product_variant_id' => $this->getProductVariantId(),
            'quantity' => $this->getQuantity(),
            'uom_code' => $this->getUomCode(),
            'unit_price' => $this->getUnitPrice(),
            'line_subtotal' => $this->getLineSubtotal(),
            'tax_amount' => $this->getTaxAmount(),
            'discount_amount' => $this->getDiscountAmount(),
            'line_total' => $this->getLineTotal(),
            'discount_rule' => $this->getDiscountRule()?->toArray(),
            'line_notes' => $this->getLineNotes(),
            'line_sequence' => $this->getLineSequence(),
        ];
    }
}
