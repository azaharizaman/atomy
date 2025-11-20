<?php

declare(strict_types=1);

namespace Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Payable\Contracts\VendorInterface;
use Nexus\Payable\Enums\VendorStatus;

/**
 * Vendor Eloquent model.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string $status
 * @property string $payment_terms
 * @property float $qty_tolerance_percent
 * @property float $price_tolerance_percent
 * @property string|null $tax_id
 * @property array|null $bank_details
 * @property string $currency
 * @property string|null $email
 * @property string|null $phone
 * @property array|null $address
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 */
class Vendor extends Model implements VendorInterface
{
    protected $table = 'vendors';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'code',
        'name',
        'status',
        'payment_terms',
        'qty_tolerance_percent',
        'price_tolerance_percent',
        'tax_id',
        'bank_details',
        'currency',
        'email',
        'phone',
        'address',
    ];

    protected $casts = [
        'qty_tolerance_percent' => 'float',
        'price_tolerance_percent' => 'float',
        'bank_details' => 'array',
        'address' => 'array',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(VendorBill::class, 'vendor_id');
    }

    // VendorInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaymentTerms(): string
    {
        return $this->payment_terms;
    }

    public function getQtyTolerancePercent(): float
    {
        return $this->qty_tolerance_percent;
    }

    public function getPriceTolerancePercent(): float
    {
        return $this->price_tolerance_percent;
    }

    public function getTaxId(): ?string
    {
        return $this->tax_id;
    }

    public function getBankDetails(): ?array
    {
        return $this->bank_details;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?array
    {
        return $this->address;
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
