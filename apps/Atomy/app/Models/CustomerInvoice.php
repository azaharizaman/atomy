<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Receivable\Contracts\CustomerInvoiceInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceLineInterface;
use Nexus\Receivable\Enums\CreditTerm;
use Nexus\Receivable\Enums\InvoiceStatus;

/**
 * Customer Invoice Eloquent Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_id
 * @property string $invoice_number
 * @property string $invoice_date
 * @property string $due_date
 * @property string $currency
 * @property float $exchange_rate
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $total_amount
 * @property float $outstanding_balance
 * @property string $status
 * @property string|null $gl_journal_id
 * @property string|null $sales_order_id
 * @property string $credit_term
 * @property string|null $description
 */
class CustomerInvoice extends Model implements CustomerInvoiceInterface
{
    use HasUlids;

    protected $table = 'customer_invoices';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'total_amount',
        'outstanding_balance',
        'status',
        'gl_journal_id',
        'sales_order_id',
        'credit_term',
        'description',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'float',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'outstanding_balance' => 'float',
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

    public function getInvoiceNumber(): string
    {
        return $this->invoice_number;
    }

    public function getInvoiceDate(): DateTimeInterface
    {
        return new DateTimeImmutable($this->invoice_date->format('Y-m-d'));
    }

    public function getDueDate(): DateTimeInterface
    {
        return new DateTimeImmutable($this->due_date->format('Y-m-d'));
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getExchangeRate(): float
    {
        return $this->exchange_rate;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getTaxAmount(): float
    {
        return $this->tax_amount;
    }

    public function getTotalAmount(): float
    {
        return $this->total_amount;
    }

    public function getOutstandingBalance(): float
    {
        return $this->outstanding_balance;
    }

    public function getStatus(): InvoiceStatus
    {
        return InvoiceStatus::from($this->status);
    }

    public function getGlJournalId(): ?string
    {
        return $this->gl_journal_id;
    }

    public function getSalesOrderId(): ?string
    {
        return $this->sales_order_id;
    }

    public function getCreditTerm(): CreditTerm
    {
        return CreditTerm::from($this->credit_term);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return CustomerInvoiceLineInterface[]
     */
    public function getLines(): array
    {
        return $this->lines()->get()->all();
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return new DateTimeImmutable($this->created_at->format('Y-m-d H:i:s'));
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return new DateTimeImmutable($this->updated_at->format('Y-m-d H:i:s'));
    }

    public function isOverdue(DateTimeInterface $asOfDate): bool
    {
        return $asOfDate > $this->getDueDate() 
            && $this->getStatus()->contributesToBalance();
    }

    public function getDaysPastDue(DateTimeInterface $asOfDate): int
    {
        $dueDate = $this->getDueDate();
        $diff = $asOfDate->diff($dueDate);
        
        return $asOfDate > $dueDate ? $diff->days : -$diff->days;
    }

    public function isEligibleForDiscount(DateTimeInterface $paymentDate): bool
    {
        $creditTerm = $this->getCreditTerm();
        
        if (!$creditTerm->hasDiscount()) {
            return false;
        }

        $invoiceDate = $this->getInvoiceDate();
        $discountDeadline = $invoiceDate->modify('+' . $creditTerm->getDiscountDays() . ' days');

        return $paymentDate <= $discountDeadline;
    }

    public function calculateDiscount(DateTimeInterface $paymentDate): float
    {
        if (!$this->isEligibleForDiscount($paymentDate)) {
            return 0.0;
        }

        $discountPercent = $this->getCreditTerm()->getDiscountPercent();
        
        return $this->getOutstandingBalance() * ($discountPercent / 100);
    }

    // Eloquent Relationships

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerInvoiceLine::class, 'invoice_id');
    }
}
