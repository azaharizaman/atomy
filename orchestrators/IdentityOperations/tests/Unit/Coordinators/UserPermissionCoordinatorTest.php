<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\Coordinators\UserPermissionCoordinator;
use Nexus\IdentityOperations\Contracts\UserPermissionServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\PermissionAssignRequest;
use Nexus\IdentityOperations\DTOs\PermissionAssignResult;
use Nexus\IdentityOperations\DTOs\PermissionRevokeRequest;
use Nexus\IdentityOperations\DTOs\PermissionRevokeResult;
use Psr\Log\LoggerInterface;

final class UserPermissionCoordinatorTest extends TestCase
{
    private $permissionService;
    private $contextDataProvider;
    private $logger;
    private $coordinator;

    protected function setUp(): void
    {
        $this->permissionService = $this->createMock(UserPermissionServiceInterface::class);
        $this->contextDataProvider = $this->createMock(UserContextProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new UserPermissionCoordinator(
            $this->permissionService,
            $this->contextDataProvider,
            $this->logger
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('UserPermissionCoordinator', $this->coordinator->getName());
    }

    public function testHasRequiredData(): void
    {
        $this->contextDataProvider->expects($this->once())
            ->method('userExists')
            ->with('user-1')
            ->willReturn(true);
        $this->assertTrue($this->coordinator->hasRequiredData('user-1'));
    }

    public function testAssignPermission(): void
    {
        $request = new PermissionAssignRequest(
            userId: 'user-123',
            permission: 'view',
            tenantId: 'tenant-1',
            assignedBy: 'admin-1'
        );
        $result = PermissionAssignResult::success('user-123', 'perm-1');

        $this->permissionService->expects($this->once())
            ->method('assign')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->assign($request));
    }

    public function testRevokePermission(): void
    {
        $request = new PermissionRevokeRequest(
            userId: 'user-1',
            permission: 'view',
            tenantId: 'tenant-1',
            revokedBy: 'admin-1'
        );
        $result = PermissionRevokeResult::success('user-1');

        $this->permissionService->expects($this->once())
            ->method('revoke')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->revoke($request));
    }

    public function testHasPermission(): void
    {
        $this->permissionService->expects($this->once())
            ->method('hasPermission')
            ->with('user-1', 'view', 'tenant-1')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->hasPermission('user-1', 'view', 'tenant-1'));
    }

    public function testGetUserPermissions(): void
    {
        $perms = ['view'];
        $this->permissionService->expects($this->once())
            ->method('getUserPermissions')
            ->with('user-1')
            ->willReturn($perms);

        $this->assertEquals($perms, $this->coordinator->getUserPermissions('user-1'));
    }

    public function testGetUserRoles(): void
    {
        $roles = ['admin'];
        $this->permissionService->expects($this->once())
            ->method('getUserRoles')
            ->with('user-1')
            ->willReturn($roles);

        $this->assertEquals($roles, $this->coordinator->getUserRoles('user-1'));
    }

    public function testAssignRole(): void
    {
        $this->permissionService->expects($this->once())
            ->method('assignRole')
            ->with('user-1', 'admin', 'tenant-1', 'admin-1')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->assignRole('user-1', 'admin', 'tenant-1', 'admin-1'));
    }

    public function testRevokeRole(): void
    {
        $this->permissionService->expects($this->once())
            ->method('revokeRole')
            ->with('user-1', 'admin', 'tenant-1', 'admin-1')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->revokeRole('user-1', 'admin', 'tenant-1', 'admin-1'));
    }
}
