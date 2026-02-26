<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\DataProviders;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\DataProviders\UserContextDataProvider;
use Nexus\IdentityOperations\DataProviders\UserQueryInterface;
use Nexus\IdentityOperations\DataProviders\PermissionQueryInterface;
use Nexus\IdentityOperations\DTOs\UserContext;

final class UserContextDataProviderTest extends TestCase
{
    private readonly UserQueryInterface|MockObject $userQuery;
    private readonly PermissionQueryInterface|MockObject $permissionQuery;
    private readonly UserContextDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->userQuery = $this->createMock(UserQueryInterface::class);
        $this->permissionQuery = $this->createMock(PermissionQueryInterface::class);
        $this->dataProvider = new UserContextDataProvider($this->userQuery, $this->permissionQuery);
    }

    public function testGetContextSuccessfully(): void
    {
        $userData = [
            'id' => 'user-123',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'tenant_id' => 'tenant-1',
            'status' => 'active',
        ];

        $this->userQuery->expects($this->once())
            ->method('findById')
            ->with('user-123')
            ->willReturn($userData);

        $this->permissionQuery->expects($this->once())
            ->method('getUserPermissions')
            ->with('user-123', 'tenant-1')
            ->willReturn(['view']);

        $this->permissionQuery->expects($this->once())
            ->method('getUserRoles')
            ->with('user-123', 'tenant-1')
            ->willReturn(['user']);

        $context = $this->dataProvider->getContext('user-123');

        $this->assertTrue($context->isAuthenticated());
        $this->assertEquals('user-123', $context->userId);
        $this->assertEquals(['view'], $context->permissions);
    }

    public function testGetContextAnonymous(): void
    {
        $this->userQuery->expects($this->once())
            ->method('findById')
            ->with('invalid')
            ->willReturn(null);

        // Ensure no permission/role lookups run for anonymous users
        $this->permissionQuery->expects($this->never())
            ->method('getUserPermissions');
        $this->permissionQuery->expects($this->never())
            ->method('getUserRoles');

        $context = $this->dataProvider->getContext('invalid');

        $this->assertFalse($context->isAuthenticated());
        $this->assertNull($context->userId);
    }
}
