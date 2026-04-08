<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Laravel\Identity;

use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Laravel\Identity\Adapters\PermissionCheckerAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PermissionCheckerAdapterTest extends TestCase
{
    private PermissionRepositoryInterface $permissionRepository;
    private RoleRepositoryInterface $roleRepository;
    private UserRepositoryInterface $userRepository;
    private LoggerInterface $logger;
    private PermissionCheckerAdapter $adapter;

    protected function setUp(): void
    {
        $this->permissionRepository = $this->createMock(PermissionRepositoryInterface::class);
        $this->roleRepository = $this->createMock(RoleRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->adapter = new PermissionCheckerAdapter(
            $this->permissionRepository,
            $this->roleRepository,
            $this->userRepository,
            $this->logger,
        );
    }

    public function testHasPermissionMatchesExactDirectPermission(): void
    {
        $user = $this->makeUser('user-123', 'tenant-1');

        $this->userRepository
            ->expects($this->once())
            ->method('getUserPermissions')
            ->with('user-123', 'tenant-1')
            ->willReturn([$this->makePermission('users.create')]);

        $this->userRepository
            ->expects($this->once())
            ->method('getUserRoles')
            ->with('user-123', 'tenant-1')
            ->willReturn([]);

        $this->roleRepository
            ->expects($this->once())
            ->method('getRoleHierarchy')
            ->with('tenant-1')
            ->willReturn([]);

        $this->permissionRepository
            ->expects($this->never())
            ->method('findMatching');

        $this->assertTrue($this->adapter->hasPermission($user, 'users.create'));
    }

    public function testPermissionMatcherDoesNotMatchDifferentSpecificPermission(): void
    {
        $permission = $this->makePermission('rfqs.approve');

        $this->assertFalse($permission->matches('rfqs.view'));
        $this->assertTrue($permission->matches('rfqs.approve'));
    }

    public function testHasPermissionMatchesRoleWildcardPermission(): void
    {
        $user = $this->makeUser('user-123', 'tenant-1');
        $role = $this->makeRole('role-manager', 'tenant-1', 'manager');

        $this->userRepository
            ->method('getUserPermissions')
            ->with('user-123', 'tenant-1')
            ->willReturn([]);

        $this->userRepository
            ->method('getUserRoles')
            ->with('user-123', 'tenant-1')
            ->willReturn([$role]);

        $this->roleRepository
            ->method('getRoleHierarchy')
            ->with('tenant-1')
            ->willReturn([]);

        $this->roleRepository
            ->expects($this->once())
            ->method('getRolePermissions')
            ->with('role-manager')
            ->willReturn([$this->makePermission('rfqs.*')]);

        $this->permissionRepository
            ->expects($this->never())
            ->method('findMatching');

        $this->assertTrue($this->adapter->hasPermission($user, 'rfqs.view'));
    }

    public function testHasPermissionExpandsParentRoleHierarchy(): void
    {
        $user = $this->makeUser('user-123', 'tenant-1');
        $childRole = $this->makeRole('role-child', 'tenant-1', 'child', 'role-parent');
        $parentRole = $this->makeRole('role-parent', 'tenant-1', 'parent');

        $this->userRepository
            ->method('getUserPermissions')
            ->with('user-123', 'tenant-1')
            ->willReturn([]);

        $this->userRepository
            ->method('getUserRoles')
            ->with('user-123', 'tenant-1')
            ->willReturn([$childRole]);

        $this->roleRepository
            ->method('getRoleHierarchy')
            ->with('tenant-1')
            ->willReturn(['role-child' => 'role-parent']);

        $this->roleRepository
            ->expects($this->once())
            ->method('findById')
            ->with('role-parent')
            ->willReturn($parentRole);

        $this->roleRepository
            ->expects($this->exactly(2))
            ->method('getRolePermissions')
            ->willReturnCallback(function (string $roleId): array {
                return match ($roleId) {
                    'role-child' => [],
                    'role-parent' => [$this->makePermission('reports.export')],
                    default => [],
                };
            });

        $this->permissionRepository
            ->expects($this->never())
            ->method('findMatching');

        $this->assertTrue($this->adapter->hasPermission($user, 'reports.export'));
    }

    public function testHasPermissionMatchesGlobalWildcard(): void
    {
        $user = $this->makeUser('user-123', 'tenant-1');

        $this->userRepository
            ->method('getUserPermissions')
            ->with('user-123', 'tenant-1')
            ->willReturn([$this->makePermission('*')]);

        $this->userRepository
            ->method('getUserRoles')
            ->with('user-123', 'tenant-1')
            ->willReturn([]);

        $this->roleRepository
            ->method('getRoleHierarchy')
            ->with('tenant-1')
            ->willReturn([]);

        $this->assertTrue($this->adapter->hasPermission($user, 'anything.at.all'));
        $this->assertTrue($this->adapter->isSuperAdmin($user));
    }

    public function testHasPermissionReturnsFalseWithoutTenantScope(): void
    {
        $user = $this->makeUser('user-123', null);

        $this->userRepository->expects($this->never())->method('getUserPermissions');
        $this->userRepository->expects($this->never())->method('getUserRoles');
        $this->roleRepository->expects($this->never())->method('getRoleHierarchy');
        $this->permissionRepository->expects($this->never())->method('findMatching');

        $this->assertFalse($this->adapter->hasPermission($user, 'users.create'));
        $this->assertSame([], $this->adapter->getUserPermissions($user));
    }

    private function makeUser(string $id, ?string $tenantId): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($id);
        $user->method('getTenantId')->willReturn($tenantId);

        return $user;
    }

    private function makeRole(string $id, ?string $tenantId, string $name, ?string $parentRoleId = null): RoleInterface
    {
        $role = $this->createMock(RoleInterface::class);
        $role->method('getId')->willReturn($id);
        $role->method('getTenantId')->willReturn($tenantId);
        $role->method('getName')->willReturn($name);
        $role->method('getParentRoleId')->willReturn($parentRoleId);

        return $role;
    }

    private function makePermission(string $name): PermissionInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('getName')->willReturn($name);

        return $permission;
    }
}
