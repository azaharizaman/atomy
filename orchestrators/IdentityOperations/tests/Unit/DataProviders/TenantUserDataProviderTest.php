<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\DataProviders;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\DataProviders\TenantUserDataProvider;
use Nexus\IdentityOperations\DataProviders\TenantUserQueryInterface;

final class TenantUserDataProviderTest extends TestCase
{
    private $tenantUserQuery;
    private $dataProvider;

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
}
