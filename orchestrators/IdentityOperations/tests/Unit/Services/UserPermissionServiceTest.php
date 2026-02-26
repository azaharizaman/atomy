<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\Services\UserPermissionService;
use Nexus\IdentityOperations\Services\PermissionAssignerInterface;
use Nexus\IdentityOperations\Services\PermissionRevokerInterface;
use Nexus\IdentityOperations\Services\PermissionCheckerInterface;
use Nexus\IdentityOperations\Services\RoleAssignerInterface;
use Nexus\IdentityOperations\Services\RoleRevokerInterface;
use Nexus\IdentityOperations\Services\AuditLoggerInterface;
use Nexus\IdentityOperations\DTOs\PermissionAssignRequest;
use Nexus\IdentityOperations\DTOs\PermissionRevokeRequest;
use Psr\Log\LoggerInterface;

final class UserPermissionServiceTest extends TestCase
{
    private readonly PermissionAssignerInterface|MockObject $permissionAssigner;
    private readonly PermissionRevokerInterface|MockObject $permissionRevoker;
    private readonly PermissionCheckerInterface|MockObject $permissionChecker;
    private readonly RoleAssignerInterface|MockObject $roleAssigner;
    private readonly RoleRevokerInterface|MockObject $roleRevoker;
    private readonly AuditLoggerInterface|MockObject $auditLogger;
    private readonly LoggerInterface|MockObject $logger;
    private readonly UserPermissionService $service;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        
        $this->permissionAssigner = $this->createMock(PermissionAssignerInterface::class);
        $this->permissionRevoker = $this->createMock(PermissionRevokerInterface::class);
        $this->permissionChecker = $this->createMock(PermissionCheckerInterface::class);
        $this->roleAssigner = $this->createMock(RoleAssignerInterface::class);
        $this->roleRevoker = $this->createMock(RoleRevokerInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new UserPermissionService(
            $this->permissionAssigner,
            $this->permissionRevoker,
            $this->permissionChecker,
            $this->roleAssigner,
            $this->roleRevoker,
            $this->auditLogger,
            $this->logger
        );
    }

    public function testAssignPermissionSuccessfully(): void
    {
        $request = new PermissionAssignRequest(
            userId: 'user-123',
            permission: 'view.reports',
            tenantId: 'tenant-1',
            assignedBy: 'admin-1'
        );

        $this->permissionAssigner->expects($this->once())
            ->method('assign')
            ->willReturn('perm-123');

        $result = $this->service->assign($request);

        $this->assertTrue($result->success);
        $this->assertEquals('perm-123', $result->permissionId);
    }

    public function testAssignPermissionFailure(): void
    {
        $request = new PermissionAssignRequest(
            userId: 'user-1',
            permission: 'view',
            tenantId: 'tenant-1',
            assignedBy: 'admin-1'
        );
        $this->permissionAssigner->expects($this->once())
            ->method('assign')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->assign($request);
        $this->assertFalse($result->success);
    }

    public function testRevokePermissionSuccessfully(): void
    {
        $request = new PermissionRevokeRequest(
            userId: 'user-123',
            permission: 'view.reports',
            tenantId: 'tenant-1',
            revokedBy: 'admin-1'
        );

        $this->permissionRevoker->expects($this->once())
            ->method('revoke');

        $result = $this->service->revoke($request);

        $this->assertTrue($result->success);
    }

    public function testRevokePermissionFailure(): void
    {
        $request = new PermissionRevokeRequest(
            userId: 'user-1',
            permission: 'view',
            tenantId: 'tenant-1',
            revokedBy: 'admin-1'
        );
        $this->permissionRevoker->expects($this->once())
            ->method('revoke')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->revoke($request);
        $this->assertFalse($result->success);
    }

    public function testHasPermission(): void
    {
        $this->permissionChecker->expects($this->once())
            ->method('check')
            ->with('user-123', 'view.reports', 'tenant-1')
            ->willReturn(true);

        $this->assertTrue($this->service->hasPermission('user-123', 'view.reports', 'tenant-1'));
    }

    public function testGetUserPermissions(): void
    {
        $perms = [];
        $this->permissionChecker->expects($this->once())
            ->method('getAll')
            ->willReturn($perms);

        $this->assertEquals($perms, $this->service->getUserPermissions('user-1'));
    }

    public function testGetUserRoles(): void
    {
        $roles = [];
        $this->permissionChecker->expects($this->once())
            ->method('getRoles')
            ->willReturn($roles);

        $this->assertEquals($roles, $this->service->getUserRoles('user-1'));
    }

    public function testAssignRoleSuccessfully(): void
    {
        $this->roleAssigner->expects($this->once())
            ->method('assign')
            ->with('user-123', 'admin', 'tenant-1')
            ->willReturn('assignment-123');

        $result = $this->service->assignRole('user-123', 'admin', 'tenant-1', 'admin-1');

        $this->assertTrue($result);
    }

    public function testAssignRoleFailure(): void
    {
        $this->roleAssigner->expects($this->once())
            ->method('assign')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->assignRole('user-1', 'admin', 'tenant-1', 'admin-1');
        $this->assertFalse($result);
    }

    public function testRevokeRoleSuccessfully(): void
    {
        $this->roleRevoker->expects($this->once())
            ->method('revoke')
            ->with('user-123', 'admin', 'tenant-1');

        $result = $this->service->revokeRole('user-123', 'admin', 'tenant-1', 'admin-1');

        $this->assertTrue($result);
    }

    public function testRevokeRoleFailure(): void
    {
        $this->roleRevoker->expects($this->once())
            ->method('revoke')
            ->with('user-1', 'admin', 'tenant-1')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->revokeRole('user-1', 'admin', 'tenant-1', 'admin-1');
        $this->assertFalse($result);
    }
}
