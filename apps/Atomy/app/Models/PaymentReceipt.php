<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Receivable\Contracts\PaymentReceiptInterface;
use Nexus\Receivable\Enums\PaymentMethod;
use Nexus\Receivable\Enums\PaymentReceiptStatus;

/**
 * Payment Receipt Eloquent Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_id
 * @property string $receipt_number
 * @property string $receipt_date
 * @property float $amount
 * @property string $currency
 * @property float $exchange_rate
 * @property float|null $amount_in_invoice_currency
 * @property string $payment_method
 * @property string|null $bank_account
 * @property string|null $reference
 * @property string $status
 * @property string|null $gl_journal_id
 * @property array|null $allocations
 */
class PaymentReceipt extends Model implements PaymentReceiptInterface
{
    use HasUlids;

    protected $table = 'payment_receipts';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'receipt_number',
        'receipt_date',
        'amount',
        'currency',
        'exchange_rate',
        'amount_in_invoice_currency',
        'payment_method',
        'bank_account',
        'reference',
        'status',
        'gl_journal_id',
        'allocations',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'float',
        'exchange_rate' => 'float',
        'amount_in_invoice_currency' => 'float',
        'allocations' => 'array',
    ];

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

    public function getReceiptNumber(): string
    {
        return $this->receipt_number;
    }

    public function getReceiptDate(): DateTimeInterface
    {
        return new DateTimeImmutable($this->receipt_date->format('Y-m-d'));
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

    public function getAmountInInvoiceCurrency(): ?float
    {
        return $this->amount_in_invoice_currency;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return PaymentMethod::from($this->payment_method);
    }

    public function getBankAccount(): ?string
    {
        return $this->bank_account;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getStatus(): PaymentReceiptStatus
    {
        return PaymentReceiptStatus::from($this->status);
    }

    public function getGlJournalId(): ?string
    {
        return $this->gl_journal_id;
    }

    public function getAllocations(): array
    {
        return $this->allocations ?? [];
    }

    public function getAllocatedAmount(): float
    {
        $allocations = $this->getAllocations();
        
        return array_sum(array_values($allocations));
    }

    public function getUnallocatedAmount(): float
    {
        return $this->getAmount() - $this->getAllocatedAmount();
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return new DateTimeImmutable($this->created_at->format('Y-m-d H:i:s'));
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return new DateTimeImmutable($this->updated_at->format('Y-m-d H:i:s'));
    }

    // Eloquent Relationships

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }
}
