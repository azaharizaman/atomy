<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Receivable\Contracts\UnappliedCashInterface;

/**
 * Unapplied Cash Model
 *
 * Tracks prepayments and deposits that have not yet been applied to invoices.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_id
 * @property float $amount
 * @property string $currency
 * @property \DateTimeInterface $receipt_date
 * @property string $payment_method
 * @property string|null $reference_number
 * @property string|null $notes
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class UnappliedCash extends Model implements UnappliedCashInterface
{
    use HasUlids;

    protected $table = 'unapplied_cash';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'amount',
        'currency',
        'receipt_date',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'receipt_date' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    // =====================================================
    // UnappliedCashInterface Implementation
    // =====================================================

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getCustomerId(): string
    {
        return $this->customer_id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getReceiptDate(): \DateTimeInterface
    {
        return $this->receipt_date;
    }

    public function getPaymentMethod(): string
    {
        return $this->payment_method;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->reference_number;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'receipt_date' => $this->receipt_date->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
        ];
    }
}
