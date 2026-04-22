<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Rfq;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Requisition selected-vendor association.
 *
 * Tenant scoping is enforced by API/service queries because this model is also
 * used in tests and maintenance jobs where no request tenant context exists.
 */
class RequisitionSelectedVendor extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'requisition_selected_vendors';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'vendor_id',
        'selected_by_user_id',
        'selected_at',
    ];

    protected $casts = [
        'selected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Rfq, $this>
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function selectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by_user_id');
    }
}
