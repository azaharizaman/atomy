<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\DataProviders;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\DataProviders\PermissionDataProvider;
use Nexus\IdentityOperations\DataProviders\PermissionQueryInterface;

final class PermissionDataProviderTest extends TestCase
{
    private readonly PermissionQueryInterface $permissionQuery;
    private readonly PermissionDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->permissionQuery = new class implements PermissionQueryInterface {
            public $findAllResult = [];
            public $findAllRolesResult = [];
            public $findRolePermissionsResult = [];
            public $existsResult = true;
            public $roleExistsResult = true;
            
            public $lastFindAllResult;
            public $lastFindAllRolesResult;
            public $lastFindRolePermissionsArg;
            public $lastExistsArg;
            public $lastRoleExistsArg;
            public $lastGetUserPermissionsArgs;
            public $lastGetUserRolesArgs;

            public function findAll(string $tenantId): array { 
                $this->lastFindAllResult = $tenantId;
                return $this->findAllResult; 
            }
            public function findAllRoles(string $tenantId): array { 
                $this->lastFindAllRolesResult = $tenantId;
                return $this->findAllRolesResult; 
            }
            public function findRolePermissions(string $roleId, string $tenantId): array { 
                $this->lastFindRolePermissionsArg = [$roleId, $tenantId];
                return $this->findRolePermissionsResult; 
            }
            public function exists(string $permission, string $tenantId): bool { 
                $this->lastExistsArg = [$permission, $tenantId];
                return $this->existsResult; 
            }
            public function roleExists(string $roleId, string $tenantId): bool { 
                $this->lastRoleExistsArg = [$roleId, $tenantId];
                return $this->roleExistsResult; 
            }
            
            public function getUserPermissions(string $userId, string $tenantId): array { 
                $this->lastGetUserPermissionsArgs = [$userId, $tenantId];
                return []; 
            }
            public function getUserRoles(string $userId, string $tenantId): array { 
                $this->lastGetUserRolesArgs = [$userId, $tenantId];
                return []; 
            }
        };
        $this->dataProvider = new PermissionDataProvider($this->permissionQuery);
    }

    public function testGetAllPermissions(): void
    {
        $permissions = [['id' => '1', 'name' => 'view', 'description' => 'View']];
        $this->permissionQuery->findAllResult = $permissions;
        $this->assertEquals($permissions, $this->dataProvider->getAllPermissions('tenant-1'));
        $this->assertEquals('tenant-1', $this->permissionQuery->lastFindAllResult);
    }

    public function testGetAllRoles(): void
    {
        $roles = [['id' => '1', 'name' => 'admin', 'permissions' => ['*']]];
        $this->permissionQuery->findAllRolesResult = $roles;
        $this->assertEquals($roles, $this->dataProvider->getAllRoles('tenant-1'));
        $this->assertEquals('tenant-1', $this->permissionQuery->lastFindAllRolesResult);
    }

    public function testGetRolePermissions(): void
    {
        $perms = ['view', 'edit'];
        $this->permissionQuery->findRolePermissionsResult = $perms;
        $this->assertEquals($perms, $this->dataProvider->getRolePermissions('role-1', 'tenant-1'));
        $this->assertEquals(['role-1', 'tenant-1'], $this->permissionQuery->lastFindRolePermissionsArg);
    }

    public function testPermissionExists(): void
    {
        $this->permissionQuery->existsResult = true;
        $this->assertTrue($this->dataProvider->permissionExists('view', 'tenant-1'));
        $this->assertEquals(['view', 'tenant-1'], $this->permissionQuery->lastExistsArg);
    }

    public function testRoleExists(): void
    {
        $this->permissionQuery->roleExistsResult = true;
        $this->assertTrue($this->dataProvider->roleExists('role-1', 'tenant-1'));
        $this->assertEquals(['role-1', 'tenant-1'], $this->permissionQuery->lastRoleExistsArg);
    }
}
