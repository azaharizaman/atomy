<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderLineInterface;
use Nexus\Sales\Enums\PaymentTerm;
use Nexus\Sales\Enums\SalesOrderStatus;
use Nexus\Sales\ValueObjects\DiscountRule;

class SalesOrder extends Model implements SalesOrderInterface
{
    use HasUlids;

    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'order_date',
        'status',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'discount_rule',
        'payment_term',
        'payment_due_date',
        'shipping_address',
        'billing_address',
        'customer_purchase_order',
        'notes',
        'confirmed_at',
        'confirmed_by',
        'is_recurring',
        'recurrence_rule',
        'salesperson_id',
        'commission_percentage',
        'preferred_warehouse_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'payment_due_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total' => 'decimal:4',
        'exchange_rate' => 'decimal:8',
        'commission_percentage' => 'decimal:2',
        'discount_rule' => 'array',
        'recurrence_rule' => 'array',
        'is_recurring' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getOrderNumber(): string
    {
        return $this->order_number;
    }

    public function getCustomerId(): string
    {
        return $this->customer_id;
    }

    public function getOrderDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->order_date);
    }

    public function getStatus(): SalesOrderStatus
    {
        return SalesOrderStatus::from($this->status);
    }

    public function getCurrencyCode(): string
    {
        return $this->currency_code;
    }

    public function getExchangeRate(): ?float
    {
        return $this->exchange_rate ? (float) $this->exchange_rate : null;
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

    public function getPaymentTerm(): PaymentTerm
    {
        return PaymentTerm::from($this->payment_term);
    }

    public function getPaymentDueDate(): ?DateTimeImmutable
    {
        return $this->payment_due_date
            ? DateTimeImmutable::createFromMutable($this->payment_due_date)
            : null;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shipping_address;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billing_address;
    }

    public function getCustomerPurchaseOrder(): ?string
    {
        return $this->customer_purchase_order;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getConfirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmed_at
            ? DateTimeImmutable::createFromMutable($this->confirmed_at)
            : null;
    }

    public function getConfirmedBy(): ?string
    {
        return $this->confirmed_by;
    }

    public function isRecurring(): bool
    {
        return $this->is_recurring;
    }

    public function getRecurrenceRule(): ?string
    {
        return $this->recurrence_rule ? json_encode($this->recurrence_rule) : null;
    }

    public function getSalespersonId(): ?string
    {
        return $this->salesperson_id;
    }

    public function getCommissionPercentage(): ?float
    {
        return $this->commission_percentage ? (float) $this->commission_percentage : null;
    }

    public function getPreferredWarehouseId(): ?string
    {
        return $this->preferred_warehouse_id;
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
            'order_number' => $this->getOrderNumber(),
            'customer_id' => $this->getCustomerId(),
            'order_date' => $this->getOrderDate()->format('Y-m-d'),
            'status' => $this->getStatus()->value,
            'currency_code' => $this->getCurrencyCode(),
            'exchange_rate' => $this->getExchangeRate(),
            'subtotal' => $this->getSubtotal(),
            'tax_amount' => $this->getTaxAmount(),
            'discount_amount' => $this->getDiscountAmount(),
            'total' => $this->getTotal(),
            'discount_rule' => $this->getDiscountRule()?->toArray(),
            'payment_term' => $this->getPaymentTerm()->value,
            'payment_due_date' => $this->getPaymentDueDate()?->format('Y-m-d'),
            'shipping_address' => $this->getShippingAddress(),
            'billing_address' => $this->getBillingAddress(),
            'customer_purchase_order' => $this->getCustomerPurchaseOrder(),
            'notes' => $this->getNotes(),
            'confirmed_at' => $this->getConfirmedAt()?->format('Y-m-d H:i:s'),
            'confirmed_by' => $this->getConfirmedBy(),
            'is_recurring' => $this->isRecurring(),
            'recurrence_rule' => $this->getRecurrenceRule(),
            'salesperson_id' => $this->getSalespersonId(),
            'commission_percentage' => $this->getCommissionPercentage(),
            'preferred_warehouse_id' => $this->getPreferredWarehouseId(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
