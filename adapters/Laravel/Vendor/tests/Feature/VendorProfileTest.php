<?php
declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendorProfile;
use Nexus\Adapter\Laravel\Vendor\Tests\TestCase;
use Nexus\Vendor\Contracts\VendorQueryInterface;
use Nexus\Vendor\ValueObjects\VendorStatus;

final class VendorProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_find_vendor_profile_via_repository(): void
    {
        $tenantId = (string) Str::ulid();
        $partyId = (string) Str::ulid();

        $profile = EloquentVendorProfile::create([
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
            'status' => VendorStatus::ACTIVE->value,
        ]);

        $repo = $this->app->make(VendorQueryInterface::class);
        $found = $repo->findById($tenantId, (string) $profile->id);

        $this->assertNotNull($found);
        $this->assertSame((string) $profile->id, $found->getId());
        $this->assertSame($partyId, $found->getPartyId());
    }

    public function test_find_by_id_does_not_return_row_for_wrong_tenant(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();

        $profile = EloquentVendorProfile::create([
            'tenant_id' => $tenantA,
            'party_id' => (string) Str::ulid(),
            'status' => VendorStatus::ACTIVE->value,
        ]);

        $repo = $this->app->make(VendorQueryInterface::class);

        $this->assertNull($repo->findById($tenantB, (string) $profile->id));
        $this->assertNotNull($repo->findById($tenantA, (string) $profile->id));
    }
}
