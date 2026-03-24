<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendorProfile;
use Nexus\Adapter\Laravel\Vendor\Tests\TestCase;
use Nexus\Vendor\Contracts\VendorRepositoryInterface;

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
            'status' => 'active',
        ]);

        $repo = $this->app->make(VendorRepositoryInterface::class);
        $found = $repo->findById($tenantId, (string) $profile->id);

        $this->assertNotNull($found);
        $this->assertSame((string) $profile->id, $found->getId());
        $this->assertSame($partyId, $found->getPartyId());
    }
}
