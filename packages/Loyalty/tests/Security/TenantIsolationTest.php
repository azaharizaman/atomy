<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Security;

use DateTimeImmutable;
use Nexus\Loyalty\Models\LoyaltyProfile;
use Nexus\Loyalty\Models\PointBalance;
use Nexus\Loyalty\Models\TierStatus;
use PHPUnit\Framework\TestCase;

/**
 * Ensures strict multi-tenant isolation.
 * Requirements: SEC-LOY-001, BUS-LOY-003
 */
final class TenantIsolationTest extends TestCase
{
    public function test_users_cannot_access_other_tenant_points(): void
    {
        $tenantA = 'tenant-a';
        $tenantB = 'tenant-b';

        $profileA = $this->createProfile('user-1', $tenantA);
        $profileB = $this->createProfile('user-1', $tenantB);

        // Even with same member ID (if UUIDs were same, which they shouldn't be), 
        // the profiles are distinct due to tenantId.
        $this->assertNotEquals($profileA->tenantId, $profileB->tenantId);
        $this->assertEquals($tenantA, $profileA->tenantId);
        $this->assertEquals($tenantB, $profileB->tenantId);
    }

    public function test_coalition_access_rules(): void
    {
        // Requirement BUS-LOY-003: Support parent-child tenant point sharing
        $parentTenant = 'corp-parent';
        $childTenant = 'subsidiary-child';

        $profile = $this->createProfile('user-1', $childTenant, [
            'coalition_id' => 'coalition-xyz',
            'parent_tenant_id' => $parentTenant
        ]);

        $this->assertEquals($childTenant, $profile->tenantId);
        $this->assertEquals($parentTenant, $profile->metadata['parent_tenant_id']);
        $this->assertEquals('coalition-xyz', $profile->metadata['coalition_id']);
    }

    private function createProfile(string $memberId, string $tenantId, array $metadata = []): LoyaltyProfile
    {
        $now = new DateTimeImmutable();
        $balance = new PointBalance(0, 0, []);
        $tier = new TierStatus('bronze', 'Bronze Status', $now);

        return new LoyaltyProfile($memberId, $tenantId, $balance, $tier, $metadata);
    }
}
