<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'payslip_id',
        'component_id',
        'code',
        'name',
        'type',
        'amount',
        'is_statutory',
        'display_order',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_statutory' => 'boolean',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
