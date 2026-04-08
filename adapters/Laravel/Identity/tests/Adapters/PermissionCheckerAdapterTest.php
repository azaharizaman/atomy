<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Tests\Adapters;

use Mockery;
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
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testHasPermissionSupportsWildcardAndInheritedRoles(): void
    {
        $permissionRepository = Mockery::mock(PermissionRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('debug')->andReturnNull()->zeroOrMoreTimes();

        $user = $this->makeUser('user-1', 'tenant-1');
        $directPermission = $this->makePermission('perm-1', 'users.view');
        $wildcardPermission = $this->makePermission('perm-2', 'rfqs.*');
        $rolePermission = $this->makePermission('perm-3', 'users.create');
        $managerRole = $this->makeRole('role-manager', 'manager', 'tenant-1');
        $adminRole = $this->makeRole('role-admin', 'admin', 'tenant-1');

        $userRepository->shouldReceive('getUserPermissions')
            ->with('user-1', 'tenant-1')
            ->andReturn([$directPermission])
            ->zeroOrMoreTimes();

        $userRepository->shouldReceive('getUserRoles')
            ->with('user-1', 'tenant-1')
            ->andReturn([$managerRole])
            ->zeroOrMoreTimes();

        $roleRepository->shouldReceive('getRoleHierarchy')
            ->with('tenant-1')
            ->andReturn([
                'role-manager' => 'role-admin',
                'role-admin' => null,
            ])->zeroOrMoreTimes();

        $roleRepository->shouldReceive('getRolePermissions')
            ->with('role-manager')
            ->andReturn([$wildcardPermission])
            ->zeroOrMoreTimes();

        $roleRepository->shouldReceive('getRolePermissions')
            ->with('role-admin')
            ->andReturn([$rolePermission])
            ->zeroOrMoreTimes();

        $roleRepository->shouldReceive('findById')
            ->with('role-admin')
            ->andReturn($adminRole)
            ->zeroOrMoreTimes();

        $permissionRepository->shouldReceive('findByNameOrNull')
            ->andReturnUsing(static function (string $name) use (
                $directPermission,
                $wildcardPermission,
                $rolePermission,
            ): ?PermissionInterface {
                return match ($name) {
                    'users.view' => $directPermission,
                    'rfqs.*' => $wildcardPermission,
                    'users.create' => $rolePermission,
                    default => null,
                };
            });

        $permissionChecker = new PermissionCheckerAdapter(
            $permissionRepository,
            $roleRepository,
            $userRepository,
            $logger,
        );

        self::assertTrue($permissionChecker->hasPermission($user, 'users.view'));
        self::assertTrue($permissionChecker->hasPermission($user, 'rfqs.approve'));
        self::assertTrue($permissionChecker->hasPermission($user, 'users.create'));
        self::assertTrue($permissionChecker->hasRole($user, 'manager'));
        self::assertTrue($permissionChecker->hasRole($user, 'admin'));
        self::assertSame(['users.view', 'rfqs.*', 'users.create'], array_map(
            static fn (PermissionInterface $permission): string => $permission->getName(),
            $permissionChecker->getUserPermissions($user),
        ));
    }

    public function testHasPermissionRejectsMissingPermission(): void
    {
        $permissionRepository = Mockery::mock(PermissionRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('debug')->andReturnNull()->zeroOrMoreTimes();

        $user = $this->makeUser('user-2', 'tenant-1');

        $userRepository->shouldReceive('getUserPermissions')
            ->andReturn([])->zeroOrMoreTimes();
        $userRepository->shouldReceive('getUserRoles')
            ->andReturn([])->zeroOrMoreTimes();
        $roleRepository->shouldReceive('getRoleHierarchy')
            ->andReturn([])->zeroOrMoreTimes();

        $permissionChecker = new PermissionCheckerAdapter(
            $permissionRepository,
            $roleRepository,
            $userRepository,
            $logger,
        );

        self::assertFalse($permissionChecker->hasPermission($user, 'rfqs.approve'));
        self::assertFalse($permissionChecker->hasRole($user, 'manager'));
        self::assertFalse($permissionChecker->isSuperAdmin($user));
    }

    public function testPermissionMatchDoesNotTreatDifferentSpecificPermissionAsEqual(): void
    {
        $permission = $this->makePermission('perm-specific', 'rfqs.approve');

        self::assertFalse($permission->matches('rfqs.view'));
        self::assertTrue($permission->matches('rfqs.approve'));
    }

    private function makeUser(string $id, string $tenantId): UserInterface
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('getTenantId')->andReturn($tenantId);

        return $user;
    }

    private function makeRole(string $id, string $name, string $tenantId): RoleInterface
    {
        $role = Mockery::mock(RoleInterface::class);
        $role->shouldReceive('getId')->andReturn($id);
        $role->shouldReceive('getName')->andReturn($name);
        $role->shouldReceive('getTenantId')->andReturn($tenantId);
        $role->shouldReceive('getDescription')->andReturn(null);
        $role->shouldReceive('isSystemRole')->andReturn(false);
        $role->shouldReceive('isSuperAdmin')->andReturn($name === 'super-admin');
        $role->shouldReceive('getParentRoleId')->andReturn(null);
        $role->shouldReceive('getCreatedAt')->andReturn(new \DateTimeImmutable());
        $role->shouldReceive('getUpdatedAt')->andReturn(new \DateTimeImmutable());
        $role->shouldReceive('requiresMfa')->andReturn(false);

        return $role;
    }

    private function makePermission(string $id, string $name): PermissionInterface
    {
        $permission = Mockery::mock(PermissionInterface::class);
        $permission->shouldReceive('getId')->andReturn($id);
        $permission->shouldReceive('getName')->andReturn($name);
        $permission->shouldReceive('getResource')->andReturn(explode('.', $name, 2)[0] ?? $name);
        $permission->shouldReceive('getAction')->andReturn(explode('.', $name, 2)[1] ?? '*');
        $permission->shouldReceive('getDescription')->andReturn(null);
        $permission->shouldReceive('isWildcard')->andReturn(str_contains($name, '*'));
        $permission->shouldReceive('getCreatedAt')->andReturn(new \DateTimeImmutable());
        $permission->shouldReceive('getUpdatedAt')->andReturn(new \DateTimeImmutable());
        $permission->shouldReceive('matches')->andReturnUsing(
            static fn (string $permissionName) => $name === '*' || $name === $permissionName || (str_ends_with($name, '.*') && str_starts_with($permissionName, rtrim($name, '*.') . '.')),
        );

        return $permission;
    }
}
