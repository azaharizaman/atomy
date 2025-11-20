<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Sales\Contracts\QuotationInterface;
use Nexus\Sales\Contracts\SalesOrderLineInterface;
use Nexus\Sales\Enums\QuoteStatus;
use Nexus\Sales\ValueObjects\DiscountRule;

class Quotation extends Model implements QuotationInterface
{
    use HasUlids;

    protected $table = 'sales_quotations';

    protected $fillable = [
        'tenant_id',
        'quote_number',
        'customer_id',
        'quote_date',
        'valid_until',
        'status',
        'currency_code',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'discount_rule',
        'notes',
        'prepared_by',
        'sent_at',
        'accepted_at',
        'converted_to_order_id',
    ];

    protected $casts = [
        'quote_date' => 'datetime',
        'valid_until' => 'datetime',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total' => 'decimal:4',
        'discount_rule' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class, 'quotation_id');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getQuoteNumber(): string
    {
        return $this->quote_number;
    }

    public function getCustomerId(): string
    {
        return $this->customer_id;
    }

    public function getQuoteDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->quote_date);
    }

    public function getValidUntil(): ?DateTimeImmutable
    {
        return $this->valid_until
            ? DateTimeImmutable::createFromMutable($this->valid_until)
            : null;
    }

    public function getStatus(): QuoteStatus
    {
        return QuoteStatus::from($this->status);
    }

    public function getCurrencyCode(): string
    {
        return $this->currency_code;
    }

    public function getSubtotal(): float
    {
        return (float) $this->subtotal;
    }

    public function getTaxAmount(): float
    {
        return (float) $this->tax_amount;
    }

    public function getDiscountAmount(): float
    {
        return (float) $this->discount_amount;
    }

    public function getTotal(): float
    {
        return (float) $this->total;
    }

    public function getDiscountRule(): ?DiscountRule
    {
        if ($this->discount_rule === null) {
            return null;
        }

        return DiscountRule::fromArray($this->discount_rule);
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPreparedBy(): string
    {
        return $this->prepared_by;
    }

    public function getSentAt(): ?DateTimeImmutable
    {
        return $this->sent_at
            ? DateTimeImmutable::createFromMutable($this->sent_at)
            : null;
    }

    public function getAcceptedAt(): ?DateTimeImmutable
    {
        return $this->accepted_at
            ? DateTimeImmutable::createFromMutable($this->accepted_at)
            : null;
    }

    public function getConvertedToOrderId(): ?string
    {
        return $this->converted_to_order_id;
    }

    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'quote_number' => $this->getQuoteNumber(),
            'customer_id' => $this->getCustomerId(),
            'quote_date' => $this->getQuoteDate()->format('Y-m-d'),
            'valid_until' => $this->getValidUntil()?->format('Y-m-d'),
            'status' => $this->getStatus()->value,
            'currency_code' => $this->getCurrencyCode(),
            'subtotal' => $this->getSubtotal(),
            'tax_amount' => $this->getTaxAmount(),
            'discount_amount' => $this->getDiscountAmount(),
            'total' => $this->getTotal(),
            'discount_rule' => $this->getDiscountRule()?->toArray(),
            'notes' => $this->getNotes(),
            'prepared_by' => $this->getPreparedBy(),
            'sent_at' => $this->getSentAt()?->format('Y-m-d H:i:s'),
            'accepted_at' => $this->getAcceptedAt()?->format('Y-m-d H:i:s'),
            'converted_to_order_id' => $this->getConvertedToOrderId(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
