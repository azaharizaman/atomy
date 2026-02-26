<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\DataProviders;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\DataProviders\TenantUserDataProvider;
use Nexus\IdentityOperations\DataProviders\TenantUserQueryInterface;

final class TenantUserDataProviderTest extends TestCase
{
    private readonly TenantUserQueryInterface|MockObject $tenantUserQuery;
    private readonly TenantUserDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->tenantUserQuery = $this->createMock(TenantUserQueryInterface::class);
        $this->dataProvider = new TenantUserDataProvider($this->tenantUserQuery);
    }

    public function testGetTenantUsers(): void
    {
        $users = [['id' => 'user-1', 'email' => 'test@example.com']];
        $this->tenantUserQuery->expects($this->once())
            ->method('findByTenantId')
            ->with('tenant-1')
            ->willReturn($users);

        $this->assertEquals($users, $this->dataProvider->getTenantUsers('tenant-1'));
    }

    public function testUserBelongsToTenant(): void
    {
        $this->tenantUserQuery->expects($this->once())
            ->method('userBelongsToTenant')
            ->with('user-1', 'tenant-1')
            ->willReturn(true);

        $this->assertTrue($this->dataProvider->userBelongsToTenant('user-1', 'tenant-1'));
    }

    public function testUserDoesNotBelongToTenant(): void
    {
        $this->tenantUserQuery->expects($this->once())
            ->method('userBelongsToTenant')
            ->with('user-1', 'tenant-1')
            ->willReturn(false);

        $this->assertFalse($this->dataProvider->userBelongsToTenant('user-1', 'tenant-1'));
    }

    public function testGetUserTenantRoles(): void
    {
        $roles = ['admin', 'editor'];
        $this->tenantUserQuery->expects($this->once())
            ->method('getUserRoles')
            ->with('user-1', 'tenant-1')
            ->willReturn($roles);

        $this->assertEquals($roles, $this->dataProvider->getUserTenantRoles('user-1', 'tenant-1'));
    }

    public function testIsTenantActive(): void
    {
        $this->tenantUserQuery->expects($this->once())
            ->method('isTenantActive')
            ->with('tenant-1')
            ->willReturn(true);

        $this->assertTrue($this->dataProvider->isTenantActive('tenant-1'));
    }

    public function testIsTenantInactive(): void
    {
        $this->tenantUserQuery->expects($this->once())
            ->method('isTenantActive')
            ->with('tenant-1')
            ->willReturn(false);

        $this->assertFalse($this->dataProvider->isTenantActive('tenant-1'));
    }
}
