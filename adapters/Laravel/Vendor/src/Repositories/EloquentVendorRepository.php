<?php
declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Repositories;

use Nexus\Vendor\Contracts\VendorQueryInterface;
use Nexus\Vendor\Contracts\VendorPersistInterface;
use Nexus\Vendor\Contracts\VendorProfileInterface;
use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendorProfile;

final readonly class EloquentVendorRepository implements VendorQueryInterface, VendorPersistInterface {
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
