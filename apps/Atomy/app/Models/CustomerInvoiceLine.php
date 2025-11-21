<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Receivable\Contracts\CustomerInvoiceLineInterface;

/**
 * Customer Invoice Line Eloquent Model
 *
 * @property string $id
 * @property string $invoice_id
 * @property int $line_number
 * @property string $description
 * @property float $quantity
 * @property float $unit_price
 * @property float $line_amount
 * @property string $gl_account
 * @property string|null $tax_code
 * @property string|null $product_id
 * @property string|null $sales_order_line_reference
 */
class CustomerInvoiceLine extends Model implements CustomerInvoiceLineInterface
{
    use HasUlids;

    protected $table = 'customer_invoice_lines';

    protected $fillable = [
        'invoice_id',
        'line_number',
        'description',
        'quantity',
        'unit_price',
        'line_amount',
        'gl_account',
        'tax_code',
        'product_id',
        'sales_order_line_reference',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'quantity' => 'float',
        'unit_price' => 'float',
        'line_amount' => 'float',
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getInvoiceId(): string
    {
        return $this->invoice_id;
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

    public function getSalesOrderLineReference(): ?string
    {
        return $this->sales_order_line_reference;
    }

    public function getProductId(): ?string
    {
        return $this->product_id;
    }

    // Eloquent Relationships

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class, 'invoice_id');
    }
}
