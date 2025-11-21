<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Receivable\Contracts\ReceivableScheduleInterface;

/**
 * Receivable Schedule Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_invoice_id
 * @property \DateTimeInterface $due_date
 * @property float $amount_due
 * @property string $currency
 * @property \DateTimeInterface|null $paid_date
 * @property float|null $amount_paid
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class ReceivableSchedule extends Model implements ReceivableScheduleInterface
{
    use HasUlids;

    protected $table = 'receivable_schedules';

    protected $fillable = [
        'tenant_id',
        'customer_invoice_id',
        'due_date',
        'amount_due',
        'currency',
        'paid_date',
        'amount_paid',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'amount_due' => 'float',
        'paid_date' => 'datetime',
        'amount_paid' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id');
    }

    // =====================================================
    // ReceivableScheduleInterface Implementation
    // =====================================================

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getCustomerInvoiceId(): string
    {
        return $this->customer_invoice_id;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->due_date;
    }

    public function getAmountDue(): float
    {
        return $this->amount_due;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPaidDate(): ?\DateTimeInterface
    {
        return $this->paid_date;
    }

    public function getAmountPaid(): ?float
    {
        return $this->amount_paid;
    }

    public function isPaid(): bool
    {
        return $this->paid_date !== null && 
               $this->amount_paid !== null && 
               abs($this->amount_paid - $this->amount_due) < 0.01;
    }

    public function getOutstandingAmount(): float
    {
        return max(0.0, $this->amount_due - ($this->amount_paid ?? 0.0));
    }

    public function markAsPaid(\DateTimeInterface $paidDate, float $amountPaid): void
    {
        $this->paid_date = $paidDate;
        $this->amount_paid = $amountPaid;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_invoice_id' => $this->customer_invoice_id,
            'due_date' => $this->due_date->format('Y-m-d'),
            'amount_due' => $this->amount_due,
            'currency' => $this->currency,
            'paid_date' => $this->paid_date?->format('Y-m-d'),
            'amount_paid' => $this->amount_paid,
            'is_paid' => $this->isPaid(),
            'outstanding_amount' => $this->getOutstandingAmount(),
        ];
    }
}
