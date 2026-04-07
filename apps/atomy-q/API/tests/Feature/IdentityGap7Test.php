<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\JwtServiceInterface;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Tenant;
use App\Services\Identity\AtomyNoopAuditLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Illuminate\Support\Str;
use Nexus\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Identity\ValueObjects\WebAuthnAuthenticationOptions;
use Tests\TestCase;

final class IdentityGap7Test extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        // The audit adapter may not be autoloadable in this worktree yet; keep identity resolution stable for MFA tests.
        $this->app->instance(AuditLogRepositoryInterface::class, new AtomyNoopAuditLogRepository());

        $this->app->instance(MfaVerificationServiceInterface::class, new class implements MfaVerificationServiceInterface {
            public function verifyTotp(string $userId, string $code): bool
            {
                if ($code !== '123456') {
                    throw new \RuntimeException('Invalid TOTP');
                }

                return true;
            }

            public function generateWebAuthnAuthenticationOptions(?string $userId = null, bool $requireUserVerification = false): WebAuthnAuthenticationOptions
            {
                throw new \BadMethodCallException(__METHOD__ . ' not implemented for tests');
            }

            public function verifyWebAuthn(string $assertionResponseJson, string $expectedChallenge, string $expectedOrigin, ?string $userId = null): array
            {
                throw new \BadMethodCallException(__METHOD__ . ' not implemented for tests');
            }

            public function verifyBackupCode(string $userId, string $code): bool
            {
                throw new \RuntimeException('Invalid backup code');
            }

            public function verifyWithFallback(string $userId, array $credentials): array
            {
                throw new \BadMethodCallException(__METHOD__ . ' not implemented for tests');
            }

            public function isRateLimited(string $userId, string $method): bool
            {
                return false;
            }

            public function getRemainingBackupCodesCount(string $userId): int
            {
                return 0;
            }

            public function shouldRegenerateBackupCodes(string $userId): bool
            {
                return false;
            }

            public function recordVerificationAttempt(string $userId, string $method, bool $success, ?string $ipAddress = null, ?string $userAgent = null): void
            {
            }

            public function clearRateLimit(string $userId, string $method): bool
            {
                return true;
            }
        });
    }

    public function test_mfa_challenge_and_verify_flow(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-mfa',
            'name' => 'Tenant MFA',
            'email' => 'tenant-mfa@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::factory()->create([
            'email' => 'mfa@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
            'mfa_enabled' => true,
        ]);

        $challenge = $this->postJson('/api/v1/auth/login', [
            'email' => 'mfa@atomy.com',
            'password' => 'password',
        ]);

        $challenge->assertStatus(401);
        $challenge->assertJsonStructure(['message', 'challenge_id']);
        $challengeId = (string) $challenge->json('challenge_id');
        $this->assertNotSame('', $challengeId);

        $verify = $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $challengeId,
            'otp' => '123456',
        ]);

        $verify->assertOk();
        $verify->assertJsonStructure(['access_token', 'refresh_token', 'user' => ['id', 'tenantId']]);

        // Challenge is single-use.
        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $challengeId,
            'otp' => '123456',
        ])->assertStatus(401);
    }

    public function test_login_success(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-login-success',
            'name' => 'Tenant Login Success',
            'email' => 'tenant-login-success@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::factory()->create([
            'email' => 'test@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@atomy.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token', 'refresh_token', 'user']);
        $response->assertJsonPath('user.tenantId', (string) $user->tenant_id);
    }

    public function test_login_failure_invalid_credentials(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-login-failure',
            'name' => 'Tenant Login Failure',
            'email' => 'tenant-login-failure@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::factory()->create([
            'email' => 'test@atomy.com',
            'password_hash' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@atomy.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_locks_account_after_five_failed_attempts(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-login-lockout',
            'name' => 'Tenant Login Lockout',
            'email' => 'tenant-login-lockout@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::factory()->create([
            'email' => 'lockout@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'lockout@atomy.com',
                'password' => 'wrong',
            ])->assertStatus(401);
        }

        $user->refresh();
        $this->assertSame('locked', $user->status);
        $this->assertSame(5, (int) $user->failed_login_attempts);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'lockout@atomy.com',
            'password' => 'password',
        ])->assertStatus(401);
    }

    public function test_access_token_is_rejected_after_session_revocation(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-session-revoke',
            'name' => 'Tenant Session Revoke',
            'email' => 'tenant-session-revoke@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::factory()->create([
            'email' => 'session@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
        ]);

        static $routeRegistered = false;
        if (! $routeRegistered) {
            Route::middleware('jwt.auth')->get('/api/v1/_identity-gap7/ping', static function () {
                return response()->json(['ok' => true]);
            });

            $routeRegistered = true;
        }

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'session@atomy.com',
            'password' => 'password',
        ]);

        $loginResponse->assertOk();

        $accessToken = (string) $loginResponse->json('access_token');
        $sid = (string) app(JwtServiceInterface::class)->decode($accessToken)->sid;

        $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->getJson('/api/v1/_identity-gap7/ping')
            ->assertOk();

        app(SessionManagerInterface::class)->revokeSession($sid);

        $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->getJson('/api/v1/_identity-gap7/ping')
            ->assertStatus(401);
    }

    public function test_protected_route_allows_wildcard_permission_via_role(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-rbac-wildcard',
            'name' => 'Tenant RBAC Wildcard',
            'email' => 'tenant-rbac-wildcard@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::factory()->create([
            'email' => 'rbac-wildcard@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
        ]);

        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'manager',
            'description' => 'Manager',
        ]);

        $permission = Permission::query()->create([
            'name' => 'rfqs.*',
            'description' => 'All RFQ actions',
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        DB::table('role_permissions')->insert([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        static $routeRegistered = false;
        if (! $routeRegistered) {
            Route::middleware(['jwt.auth', 'tenant', 'nexus.can:rfqs.approve'])->get('/api/v1/_identity-gap7/rbac', static function () {
                return response()->json(['ok' => true]);
            });

            $routeRegistered = true;
        }

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'rbac-wildcard@atomy.com',
            'password' => 'password',
        ]);

        $loginResponse->assertOk();
        $accessToken = (string) $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->getJson('/api/v1/_identity-gap7/rbac')
            ->assertOk();
    }
}
