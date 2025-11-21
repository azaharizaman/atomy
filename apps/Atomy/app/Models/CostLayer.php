<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class CostLayer extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'quantity',
        'unit_cost',
        'remaining_quantity',
        'received_date',
        'receipt_reference_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'remaining_quantity' => 'decimal:4',
        'received_date' => 'date',
    ];
}
