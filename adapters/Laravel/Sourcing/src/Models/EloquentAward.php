<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sourcing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Nexus\Sourcing\Contracts\AwardInterface;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $quotation_id
 * @property string $vendor_id
 * @property string|null $purchase_order_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class EloquentAward extends Model implements AwardInterface
{
    use HasUlids;

    protected $table = 'nexus_sourcing_awards';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'quotation_id',
        'vendor_id',
        'purchase_order_id',
    ];

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getQuotationId(): string
    {
        return (string) $this->quotation_id;
    }

    public function getVendorId(): string
    {
        return (string) $this->vendor_id;
    }
}
