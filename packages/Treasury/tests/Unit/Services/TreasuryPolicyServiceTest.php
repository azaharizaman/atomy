<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyPersistInterface;
use Nexus\Treasury\Entities\TreasuryPolicy;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Services\TreasuryPolicyService;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TreasuryPolicyServiceTest extends TestCase
{
    private TreasuryPolicyService $service;
    private MockObject $query;
    private MockObject $persist;

    protected function setUp(): void
    {
        $this->query = $this->createMock(TreasuryPolicyQueryInterface::class);
        $this->persist = $this->createMock(TreasuryPolicyPersistInterface::class);

        $this->service = new TreasuryPolicyService(
            $this->query,
            $this->persist,
            null,
            new NullLogger()
        );
    }

    public function test_create_creates_new_policy(): void
    {
        $data = new TreasuryPolicyData(
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(50000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD'),
            approvalRequired: true,
            description: 'Test description'
        );

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->create('tenant-001', $data);

        $this->assertEquals('tenant-001', $result->getTenantId());
        $this->assertEquals('Test Policy', $result->getName());
        $this->assertEquals(TreasuryStatus::PENDING, $result->getStatus());
    }

    public function test_activate_activates_existing_policy(): void
    {
        $policy = $this->createMockPolicy();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-POL-001')
            ->willReturn($policy);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->activate('TRE-POL-001');

        $this->assertEquals(TreasuryStatus::ACTIVE, $result->getStatus());
    }

    public function test_deactivate_deactivates_existing_policy(): void
    {
        $policy = $this->createMockPolicy(TreasuryStatus::ACTIVE);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-POL-001')
            ->willReturn($policy);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->deactivate('TRE-POL-001');

        $this->assertEquals(TreasuryStatus::INACTIVE, $result->getStatus());
    }

    public function test_get_returns_policy_by_id(): void
    {
        $policy = $this->createMockPolicy();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-POL-001')
            ->willReturn($policy);

        $result = $this->service->get('TRE-POL-001');

        $this->assertEquals($policy, $result);
    }

    public function test_get_active_returns_effective_policy(): void
    {
        $activePolicy = $this->createMockPolicy(TreasuryStatus::ACTIVE);

        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->with('tenant-001')
            ->willReturn([$activePolicy]);

        $result = $this->service->getActive('tenant-001');

        $this->assertNotNull($result);
    }

    public function test_get_active_returns_null_when_no_active_policy(): void
    {
        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->with('tenant-001')
            ->willReturn([]);

        $result = $this->service->getActive('tenant-001');

        $this->assertNull($result);
    }

    public function test_delete_removes_policy(): void
    {
        $policy = $this->createMockPolicy();

        $this->query
            ->expects($this->once())
            ->method('find')
            ->with('TRE-POL-001')
            ->willReturn($policy);

        $this->persist
            ->expects($this->once())
            ->method('delete')
            ->with('TRE-POL-001');

        $this->service->delete('TRE-POL-001');
    }

    public function test_find_by_tenant_returns_policies_for_tenant(): void
    {
        $policies = [$this->createMockPolicy()];

        $this->query
            ->expects($this->once())
            ->method('findByTenantId')
            ->with('tenant-001')
            ->willReturn($policies);

        $result = $this->service->findByTenant('tenant-001');

        $this->assertCount(1, $result);
    }

    public function test_update_updates_policy_data(): void
    {
        $policy = $this->createMockPolicy(TreasuryStatus::ACTIVE);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-POL-001')
            ->willReturn($policy);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $data = new TreasuryPolicyData(
            name: 'Updated Policy',
            minimumCashBalance: Money::of(15000, 'USD'),
            maximumSingleTransaction: Money::of(75000, 'USD'),
            approvalThreshold: Money::of(7500, 'USD'),
            approvalRequired: true,
            description: 'Updated description'
        );

        $result = $this->service->update('TRE-POL-001', $data);

        $this->assertEquals('Updated Policy', $result->getName());
    }

    public function test_get_active_returns_effective_policy_when_in_date_range(): void
    {
        $now = new DateTimeImmutable();
        $yesterday = $now->modify('-1 day');
        $tomorrow = $now->modify('+1 day');

        $policy = new TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(50000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD'),
            approvalRequired: true,
            status: TreasuryStatus::ACTIVE,
            effectiveFrom: $yesterday,
            effectiveTo: $tomorrow,
            description: null,
            createdAt: $now,
            updatedAt: $now
        );

        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->with('tenant-001')
            ->willReturn([$policy]);

        $result = $this->service->getActive('tenant-001');

        $this->assertNotNull($result);
        $this->assertEquals('TRE-POL-001', $result->getId());
    }

    private function createMockPolicy(TreasuryStatus $status = TreasuryStatus::PENDING): TreasuryPolicy
    {
        $now = new DateTimeImmutable();

        return new TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Test Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(50000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD'),
            approvalRequired: true,
            status: $status,
            effectiveFrom: $now,
            effectiveTo: null,
            description: null,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
