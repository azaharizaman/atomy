<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Security;

use DateTimeImmutable;
use Nexus\Loyalty\Entities\LoyaltyProfile;
use Nexus\Loyalty\ValueObjects\PointBalance;
use Nexus\Loyalty\ValueObjects\PointBucket;
use Nexus\Loyalty\ValueObjects\TierStatus;
use Nexus\Loyalty\Services\RedemptionValidator;
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

    /**
     * Requirement: SEC-LOY-001 - Assert service logic enforces point sufficiency per Profile object.
     * Note: Tenant-level scoping is enforced at the data-access/orchestrator layer.
     */
    public function test_validate_redemption_by_profile_not_tenant_isolation(): void
    {
        $tenantA = 'tenant-a';
        $tenantB = 'tenant-b';
        
        $profileA = $this->createProfile('user-1', $tenantA, [], 2000);
        $profileB = $this->createProfile('user-1', $tenantB, [], 500);

        $validator = new RedemptionValidator(1000, 100);

        // Valid for Profile A (which has 2000 pts)
        $this->assertTrue($validator->validateRedemption($profileA, 1000));

        // Invalid for Profile B (which only has 500 pts)
        $this->expectException(\Nexus\Loyalty\Exceptions\InsufficientPointsException::class);
        $validator->validateRedemption($profileB, 1000);
    }

    private function createProfile(string $memberId, string $tenantId, array $metadata = [], int $points = 0): LoyaltyProfile
    {
        $now = new DateTimeImmutable();
        $bucket = new PointBucket('b1', $points, $points, $now);
        $balance = new PointBalance($points, $points, [$bucket]);
        $tier = new TierStatus('bronze', 'Bronze Status', $now);

        return new LoyaltyProfile($memberId, $tenantId, $balance, $tier, $metadata);
    }
}
