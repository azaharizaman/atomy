<?php

declare(strict_types=1);

namespace Atomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Payable\Contracts\VendorBillLineInterface;

/**
 * Vendor Bill Line Eloquent model.
 *
 * @property string $id
 * @property string $bill_id
 * @property int $line_number
 * @property string $description
 * @property float $quantity
 * @property float $unit_price
 * @property float $line_amount
 * @property string $gl_account
 * @property string|null $tax_code
 * @property string|null $po_line_reference
 * @property string|null $grn_line_reference
 */
class VendorBillLine extends Model implements VendorBillLineInterface
{
    protected $table = 'vendor_bill_lines';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'bill_id',
        'line_number',
        'description',
        'quantity',
        'unit_price',
        'line_amount',
        'gl_account',
        'tax_code',
        'po_line_reference',
        'grn_line_reference',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'quantity' => 'float',
        'unit_price' => 'float',
        'line_amount' => 'float',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class, 'bill_id');
    }

    // VendorBillLineInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getBillId(): string
    {
        return $this->bill_id;
    }

    public function getLineNumber(): int
    {
        return $this->line_number;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unit_price;
    }

    public function getLineAmount(): float
    {
        return $this->line_amount;
    }

    public function getGlAccount(): string
    {
        return $this->gl_account;
    }

    public function getTaxCode(): ?string
    {
        return $this->tax_code;
    }

    public function getPoLineReference(): ?string
    {
        return $this->po_line_reference;
    }

    public function getGrnLineReference(): ?string
    {
        return $this->grn_line_reference;
    }
}
