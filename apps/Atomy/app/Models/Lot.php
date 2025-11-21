<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Lot extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'lot_number',
        'product_id',
        'expiry_date',
        'manufacturing_date',
        'supplier_lot_number',
        'is_active',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'manufacturing_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function stockLevels(): HasMany
    {
        return $this->hasMany(LotStockLevel::class);
    }
}
