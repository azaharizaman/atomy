<?php
declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Repositories;

use Nexus\Vendor\Contracts\VendorRepositoryInterface;
use Nexus\Vendor\Contracts\VendorProfileInterface;
use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendorProfile;
use Nexus\Vendor\ValueObjects\VendorStatus;

final readonly class EloquentVendorRepository implements VendorRepositoryInterface {
    public function findById(string $tenantId, string $id): ?VendorProfileInterface {
        return EloquentVendorProfile::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
    }

    public function save(string $tenantId, VendorProfileInterface $vendor): void {
        EloquentVendorProfile::updateOrCreate(
            ['tenant_id' => $tenantId, 'id' => $vendor->getId()],
            [
                'party_id' => $vendor->getPartyId(),
                'status' => $vendor->getStatus()->value,
            ]
        );
    }
}
