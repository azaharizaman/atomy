<?php

declare(strict_types=1);

namespace Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Payable\Contracts\PaymentInterface;

/**
 * Payment Eloquent model.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $payment_number
 * @property \DateTimeInterface $payment_date
 * @property float $amount
 * @property string $currency
 * @property float $exchange_rate
 * @property string $payment_method
 * @property string $bank_account
 * @property string $reference
 * @property string $status
 * @property string|null $gl_journal_id
 * @property array $allocations
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class Payment extends Model implements PaymentInterface
{
    protected $table = 'payments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'payment_number',
        'payment_date',
        'amount',
        'currency',
        'exchange_rate',
        'payment_method',
        'bank_account',
        'reference',
        'status',
        'gl_journal_id',
        'allocations',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'float',
        'exchange_rate' => 'float',
        'allocations' => 'array',
    ];

    // PaymentInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getPaymentNumber(): string
    {
        return $this->payment_number;
    }

    public function getPaymentDate(): \DateTimeInterface
    {
        return $this->payment_date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getExchangeRate(): float
    {
        return $this->exchange_rate;
    }

    public function getPaymentMethod(): string
    {
        return $this->payment_method;
    }

    public function getBankAccount(): string
    {
        return $this->bank_account;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getGlJournalId(): ?string
    {
        return $this->gl_journal_id;
    }

    public function getAllocations(): array
    {
        return $this->allocations ?? [];
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
