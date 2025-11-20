<?php

declare(strict_types=1);

namespace Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Payable\Contracts\VendorBillInterface;
use Nexus\Payable\Contracts\VendorBillLineInterface;

/**
 * Vendor Bill Eloquent model.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $vendor_id
 * @property string $bill_number
 * @property \DateTimeInterface $bill_date
 * @property \DateTimeInterface $due_date
 * @property string $currency
 * @property float $exchange_rate
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $total_amount
 * @property string $status
 * @property string $matching_status
 * @property string|null $gl_journal_id
 * @property string|null $description
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class VendorBill extends Model implements VendorBillInterface
{
    protected $table = 'vendor_bills';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'vendor_id',
        'bill_number',
        'bill_date',
        'due_date',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'matching_status',
        'gl_journal_id',
        'description',
    ];

    protected $casts = [
        'bill_date' => 'datetime',
        'due_date' => 'datetime',
        'exchange_rate' => 'float',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class, 'bill_id');
    }

    // VendorBillInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getVendorId(): string
    {
        return $this->vendor_id;
    }

    public function getBillNumber(): string
    {
        return $this->bill_number;
    }

    public function getBillDate(): \DateTimeInterface
    {
        return $this->bill_date;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->due_date;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMatchingStatus(): string
    {
        return $this->matching_status;
    }

    public function getGlJournalId(): ?string
    {
        return $this->gl_journal_id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<VendorBillLineInterface>
     */
    public function getLines(): array
    {
        return $this->lines->all();
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
