<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\AuthorizationMatrixQueryInterface;
use Nexus\Treasury\Contracts\AuthorizationMatrixPersistInterface;
use Nexus\Treasury\Entities\AuthorizationLimit;
use Nexus\Treasury\Exceptions\AuthorizationLimitExceededException;
use Nexus\Treasury\Services\AuthorizationMatrixService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AuthorizationMatrixServiceTest extends TestCase
{
    private AuthorizationMatrixService $service;
    private MockObject $query;
    private MockObject $persist;

    protected function setUp(): void
    {
        $this->query = $this->createMock(AuthorizationMatrixQueryInterface::class);
        $this->persist = $this->createMock(AuthorizationMatrixPersistInterface::class);

        $this->service = new AuthorizationMatrixService(
            $this->query,
            $this->persist,
            null,
            new NullLogger()
        );
    }

    public function test_set_limit_creates_new_limit_for_user(): void
    {
        $this->query
            ->method('findByUserId')
            ->willReturn([]);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->setLimit(
            'tenant-001',
            'payment',
            Money::of(50000, 'USD'),
            'user-001'
        );

        $this->assertEquals('tenant-001', $result->getTenantId());
        $this->assertEquals('user-001', $result->getUserId());
        $this->assertEquals('payment', $result->getTransactionType());
        $this->assertEquals(50000, $result->getApprovalLimit()->getAmount());
    }

    public function test_set_limit_creates_new_limit_for_role(): void
    {
        $this->query
            ->method('findByRoleId')
            ->willReturn([]);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->setLimit(
            'tenant-001',
            'payment',
            Money::of(100000, 'USD'),
            null,
            'role-001'
        );

        $this->assertNull($result->getUserId());
        $this->assertEquals('role-001', $result->getRoleId());
    }

    public function test_set_limit_with_all_optional_parameters(): void
    {
        $this->query
            ->method('findByUserId')
            ->willReturn([]);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->setLimit(
            'tenant-001',
            'payment',
            Money::of(50000, 'USD'),
            'user-001',
            null,
            Money::of(100000, 'USD'),
            Money::of(500000, 'USD'),
            Money::of(2000000, 'USD'),
            true
        );

        $this->assertEquals(100000, $result->getDailyLimit()->getAmount());
        $this->assertEquals(500000, $result->getWeeklyLimit()->getAmount());
        $this->assertEquals(2000000, $result->getMonthlyLimit()->getAmount());
        $this->assertTrue($result->getRequiresDualApproval());
    }

    public function test_set_limit_updates_existing_limit(): void
    {
        $existingLimit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(25000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->method('findByUserId')
            ->willReturn([$existingLimit]);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->setLimit(
            'tenant-001',
            'payment',
            Money::of(100000, 'USD'),
            'user-001'
        );

        $this->assertEquals('TRE-AUTH-001', $result->getId());
        $this->assertEquals(100000, $result->getApprovalLimit()->getAmount());
    }

    public function test_can_authorize_returns_true_when_user_has_sufficient_limit(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(50000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->method('findEffectiveForUser')
            ->willReturn($limit);

        $result = $this->service->canAuthorize(
            'tenant-001',
            'user-001',
            'payment',
            Money::of(30000, 'USD')
        );

        $this->assertTrue($result);
    }

    public function test_can_authorize_returns_false_when_user_has_insufficient_limit(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(10000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->method('findEffectiveForUser')
            ->willReturn($limit);

        $result = $this->service->canAuthorize(
            'tenant-001',
            'user-001',
            'payment',
            Money::of(50000, 'USD')
        );

        $this->assertFalse($result);
    }

    public function test_can_authorize_returns_false_when_no_limit_exists(): void
    {
        $this->query
            ->method('findEffectiveForUser')
            ->willReturn(null);

        $result = $this->service->canAuthorize(
            'tenant-001',
            'user-001',
            'payment',
            Money::of(50000, 'USD')
        );

        $this->assertFalse($result);
    }

    public function test_validate_authorization_throws_exception_when_exceeded(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(10000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->method('findEffectiveForUser')
            ->willReturn($limit);

        $this->query
            ->method('findHighestLimitForUser')
            ->willReturn($limit);

        $this->expectException(AuthorizationLimitExceededException::class);

        $this->service->validateAuthorization(
            'tenant-001',
            'user-001',
            'payment',
            Money::of(50000, 'USD')
        );
    }

    public function test_validate_authorization_does_not_throw_when_within_limit(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(100000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->method('findEffectiveForUser')
            ->willReturn($limit);

        $this->service->validateAuthorization(
            'tenant-001',
            'user-001',
            'payment',
            Money::of(50000, 'USD')
        );

        $this->assertTrue(true);
    }

    public function test_get_limit_returns_limit_by_id(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(50000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-AUTH-001')
            ->willReturn($limit);

        $result = $this->service->getLimit('TRE-AUTH-001');

        $this->assertEquals($limit, $result);
    }

    public function test_get_limits_for_user_returns_array(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(50000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findByUserId')
            ->with('tenant-001', 'user-001')
            ->willReturn([$limit]);

        $result = $this->service->getLimitsForUser('tenant-001', 'user-001');

        $this->assertCount(1, $result);
        $this->assertEquals($limit, $result[0]);
    }

    public function test_get_limits_for_role_returns_array(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: null,
            roleId: 'role-001',
            transactionType: 'payment',
            approvalLimit: Money::of(100000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findByRoleId')
            ->with('tenant-001', 'role-001')
            ->willReturn([$limit]);

        $result = $this->service->getLimitsForRole('tenant-001', 'role-001');

        $this->assertCount(1, $result);
    }

    public function test_get_highest_limit_for_user_returns_highest(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(100000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findHighestLimitForUser')
            ->willReturn($limit);

        $result = $this->service->getHighestLimitForUser('tenant-001', 'user-001', 'payment');

        $this->assertNotNull($result);
        $this->assertEquals(100000, $result->getApprovalLimit()->getAmount());
    }

    public function test_requires_dual_approval_returns_true_when_required(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(50000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: true,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->method('findEffectiveForUser')
            ->willReturn($limit);

        $result = $this->service->requiresDualApproval('tenant-001', 'user-001', 'payment');

        $this->assertTrue($result);
    }

    public function test_requires_dual_approval_returns_false_when_not_required(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(50000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->method('findEffectiveForUser')
            ->willReturn($limit);

        $result = $this->service->requiresDualApproval('tenant-001', 'user-001', 'payment');

        $this->assertFalse($result);
    }

    public function test_delete_limit_removes_limit(): void
    {
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(50000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->method('find')
            ->willReturn($limit);

        $this->persist
            ->expects($this->once())
            ->method('delete')
            ->with('TRE-AUTH-001');

        $this->service->deleteLimit('TRE-AUTH-001');
    }

    public function test_delete_limits_for_user_removes_all(): void
    {
        $this->persist
            ->expects($this->once())
            ->method('deleteByUserId')
            ->with('tenant-001', 'user-001')
            ->willReturn(3);

        $result = $this->service->deleteLimitsForUser('tenant-001', 'user-001');

        $this->assertEquals(3, $result);
    }

    public function test_delete_limits_for_role_removes_all(): void
    {
        $this->persist
            ->expects($this->once())
            ->method('deleteByRoleId')
            ->with('tenant-001', 'role-001')
            ->willReturn(2);

        $result = $this->service->deleteLimitsForRole('tenant-001', 'role-001');

        $this->assertEquals(2, $result);
    }
}
