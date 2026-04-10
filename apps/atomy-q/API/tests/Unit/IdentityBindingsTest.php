<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Nexus\IdentityOperations\Contracts\UserAuthenticationCoordinatorInterface;
use Illuminate\Support\Str;
use Tests\TestCase;

final class IdentityBindingsTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        return $app;
    }

    public function test_user_authentication_coordinator_resolves(): void
    {
        $coordinator = $this->app->make(UserAuthenticationCoordinatorInterface::class);

        $this->assertInstanceOf(UserAuthenticationCoordinatorInterface::class, $coordinator);
    }

    public function test_user_authentication_coordinator_authenticates_valid_credentials(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-unit-auth',
            'name' => 'Tenant Unit Auth',
            'email' => 'tenant-unit-auth@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'unit@example.com',
            'name' => 'Unit Test',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        $context = $this->app
            ->make(UserAuthenticationCoordinatorInterface::class)
            ->authenticate('unit@example.com', 'password', (string) $user->tenant_id);

        $this->assertSame((string) $user->id, $context->userId);
        $this->assertSame('unit@example.com', $context->email);
    }

    public function test_user_authentication_coordinator_surfaces_roles_and_permissions(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-unit-rbac',
            'name' => 'Tenant Unit RBAC',
            'email' => 'tenant-unit-rbac@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'rbac@example.com',
            'name' => 'RBAC User',
            'password_hash' => Hash::make('password'),
            'role' => 'user',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'manager',
            'description' => 'Manager role',
        ]);

        $permission = Permission::query()->create([
            'name' => 'rfqs.view',
            'description' => 'View RFQs',
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        DB::table('role_permissions')->insert([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        DB::table('user_permissions')->insert([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);

        $context = $this->app
            ->make(UserAuthenticationCoordinatorInterface::class)
            ->authenticate('rbac@example.com', 'password', (string) $user->tenant_id);

        $this->assertContains('manager', $context->roles);
        $this->assertContains('rfqs.view', $context->permissions);
    }
}
