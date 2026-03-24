<?php
declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Vendor\Contracts\VendorProfileInterface;
use Nexus\Vendor\ValueObjects\VendorStatus;

class EloquentVendorProfile extends Model implements VendorProfileInterface {
    use HasUlids;

    protected $table = 'nexus_vendor_profiles';

    protected $fillable = ['tenant_id', 'party_id', 'status'];

    public function getId(): string {
        return (string) $this->id;
    }

    public function getPartyId(): string {
        return (string) $this->party_id;
    }

    public function getStatus(): VendorStatus {
        return VendorStatus::from($this->status);
    }
}
