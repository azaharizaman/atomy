<?php

declare(strict_types=1);

namespace Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Payable\Contracts\PaymentScheduleInterface;

/**
 * Payment Schedule Eloquent model.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $bill_id
 * @property string $vendor_id
 * @property float $scheduled_amount
 * @property \DateTimeInterface $due_date
 * @property float $early_payment_discount_percent
 * @property \DateTimeInterface|null $early_payment_discount_date
 * @property string $status
 * @property string|null $payment_id
 * @property string|null $gl_journal_id
 * @property string $currency
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class PaymentSchedule extends Model implements PaymentScheduleInterface
{
    protected $table = 'payment_schedules';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'bill_id',
        'vendor_id',
        'scheduled_amount',
        'due_date',
        'early_payment_discount_percent',
        'early_payment_discount_date',
        'status',
        'payment_id',
        'gl_journal_id',
        'currency',
    ];

    protected $casts = [
        'scheduled_amount' => 'float',
        'due_date' => 'datetime',
        'early_payment_discount_percent' => 'float',
        'early_payment_discount_date' => 'datetime',
    ];

    // PaymentScheduleInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getBillId(): string
    {
        return $this->bill_id;
    }

    public function getVendorId(): string
    {
        return $this->vendor_id;
    }

    public function getScheduledAmount(): float
    {
        return $this->scheduled_amount;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->due_date;
    }

    public function getEarlyPaymentDiscountPercent(): float
    {
        return $this->early_payment_discount_percent;
    }

    public function getEarlyPaymentDiscountDate(): ?\DateTimeInterface
    {
        return $this->early_payment_discount_date;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaymentId(): ?string
    {
        return $this->payment_id;
    }

    public function getGlJournalId(): ?string
    {
        return $this->gl_journal_id;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }
}
