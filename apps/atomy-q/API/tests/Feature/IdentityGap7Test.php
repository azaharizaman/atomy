<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\JwtServiceInterface;
use App\Http\Controllers\Api\V1\AuthController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
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
                if ($code !== 'BACKUP-OK') {
                    throw new \RuntimeException('Invalid backup code');
                }

                return true;
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

        $user = User::factory()->create([
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
        $this->assertAuditLogExists('user.mfa.challenge_issued', (string) $user->id, (string) $tenant->id);

        $verify = $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $challengeId,
            'tenant_id' => $tenant->id,
            'otp' => '123456',
        ]);

        $verify->assertOk();
        $verify->assertJsonStructure(['access_token', 'refresh_token', 'user' => ['id', 'tenantId']]);
        $this->assertAuditLogExists('user.mfa.verified', (string) $user->id, (string) $tenant->id);

        // Challenge is single-use.
        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $challengeId,
            'tenant_id' => $tenant->id,
            'otp' => '123456',
        ])->assertStatus(401);
    }

    public function test_mfa_verify_failure_persists_audit_log(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-mfa-failure',
            'name' => 'Tenant MFA Failure',
            'email' => 'tenant-mfa-failure@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::factory()->create([
            'email' => 'mfa-failure@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
            'mfa_enabled' => true,
        ]);

        $challenge = $this->postJson('/api/v1/auth/login', [
            'email' => 'mfa-failure@atomy.com',
            'password' => 'password',
        ]);

        $challenge->assertStatus(401);
        $challengeId = (string) $challenge->json('challenge_id');

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $challengeId,
            'tenant_id' => $tenant->id,
            'otp' => '000000',
        ])->assertStatus(401)->assertJsonFragment(['message' => 'Invalid MFA code']);

        $this->assertAuditLogExists('user.mfa.verification_failed', (string) $user->id, (string) $tenant->id);
    }

    public function test_mfa_verify_rejects_tampered_or_expired_challenge(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-mfa-expired',
            'name' => 'Tenant MFA Expired',
            'email' => 'tenant-mfa-expired@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $otherTenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-mfa-expired-other',
            'name' => 'Tenant MFA Expired Other',
            'email' => 'tenant-mfa-expired-other@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::factory()->create([
            'email' => 'mfa-expired@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
            'mfa_enabled' => true,
        ]);

        $tamperedChallenge = $this->postJson('/api/v1/auth/login', [
            'email' => 'mfa-expired@atomy.com',
            'password' => 'password',
        ]);

        $tamperedChallenge->assertStatus(401);
        $tamperedChallengeId = (string) $tamperedChallenge->json('challenge_id');

        DB::table('mfa_challenges')
            ->where('id', $tamperedChallengeId)
            ->update(['tenant_id' => $otherTenant->id]);

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $tamperedChallengeId,
            'tenant_id' => $tenant->id,
            'otp' => '123456',
        ])->assertStatus(401)->assertJsonFragment(['message' => 'Invalid or expired MFA challenge']);

        $expiredChallenge = $this->postJson('/api/v1/auth/login', [
            'email' => 'mfa-expired@atomy.com',
            'password' => 'password',
        ]);

        $expiredChallenge->assertStatus(401);
        $expiredChallengeId = (string) $expiredChallenge->json('challenge_id');

        DB::table('mfa_challenges')
            ->where('id', $expiredChallengeId)
            ->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $expiredChallengeId,
            'tenant_id' => $tenant->id,
            'otp' => '123456',
        ])->assertStatus(401)->assertJsonFragment(['message' => 'Invalid or expired MFA challenge']);
    }

    public function test_mfa_verify_supports_backup_code_path(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-mfa-backup',
            'name' => 'Tenant MFA Backup',
            'email' => 'tenant-mfa-backup@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::factory()->create([
            'email' => 'mfa-backup@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
            'mfa_enabled' => true,
        ]);

        $challenge = $this->postJson('/api/v1/auth/login', [
            'email' => 'mfa-backup@atomy.com',
            'password' => 'password',
        ]);

        $challenge->assertStatus(401);
        $challengeId = (string) $challenge->json('challenge_id');
        $this->assertNotSame('', $challengeId);

        $verify = $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $challengeId,
            'tenant_id' => $tenant->id,
            'otp' => 'BACKUP-OK',
        ]);

        $verify->assertOk();
        $verify->assertJsonStructure(['access_token', 'refresh_token', 'user' => ['id', 'tenantId']]);
        $this->assertAuditLogExists('user.mfa.verified', (string) $user->id, (string) $tenant->id);
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
        $this->assertAuditLogExists('user.authenticated', (string) $user->id, (string) $tenant->id);
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

        $user = User::factory()->create([
            'email' => 'test@atomy.com',
            'password_hash' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@atomy.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
        $this->assertAuditLogExists('user.login.failure', (string) $user->id, (string) $tenant->id);
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

        $this->assertSame(5, DB::table('audit_logs')->where('event', 'user.login.failure')->count());

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

    public function test_logout_persists_audit_log(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-logout',
            'name' => 'Tenant Logout',
            'email' => 'tenant-logout@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::factory()->create([
            'email' => 'logout@atomy.com',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'tenant_id' => $tenant->id,
        ]);

        static $routeRegistered = false;
        if (! $routeRegistered) {
            Route::middleware('jwt.auth')->post('/api/v1/_identity-gap7/logout', [AuthController::class, 'logout']);

            $routeRegistered = true;
        }

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'logout@atomy.com',
            'password' => 'password',
        ]);

        $loginResponse->assertOk();
        $accessToken = (string) $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/v1/_identity-gap7/logout')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Logged out successfully.']);

        $this->assertAuditLogExists('user.logged_out', (string) $user->id, (string) $tenant->id);
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

    public function test_users_index_returns_only_current_tenant_users(): void
    {
        $tenantA = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-a',
            'name' => 'Tenant Users A',
            'email' => 'tenant-users-a@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $tenantB = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-b',
            'name' => 'Tenant Users B',
            'email' => 'tenant-users-b@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $adminA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'email' => 'admin-a@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'tenant_id' => $tenantA->id,
            'email' => 'member-a@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'user',
        ]);

        User::factory()->create([
            'tenant_id' => $tenantB->id,
            'email' => 'member-b@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'user',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('admin-a@atomy.test'))
            ->getJson('/api/v1/users');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['email' => 'admin-a@atomy.test']);
        $response->assertJsonFragment(['email' => 'member-a@atomy.test']);
        $response->assertJsonMissing(['email' => 'member-b@atomy.test']);
        $response->assertJsonPath('meta.total', 2);

        $this->assertSame((string) $tenantA->id, (string) $adminA->tenant_id);
    }

    public function test_users_show_same_tenant_success_and_wrong_tenant_404(): void
    {
        $tenantA = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-show-a',
            'name' => 'Tenant Users Show A',
            'email' => 'tenant-users-show-a@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $tenantB = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-show-b',
            'name' => 'Tenant Users Show B',
            'email' => 'tenant-users-show-b@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $userA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'email' => 'show-a@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);

        $userB = User::factory()->create([
            'tenant_id' => $tenantB->id,
            'email' => 'show-b@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'user',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('show-a@atomy.test'))
            ->getJson('/api/v1/users/' . $userA->id);

        $response->assertOk();
        $response->assertJsonPath('data.email', 'show-a@atomy.test');
        $response->assertJsonPath('data.tenant_id', (string) $tenantA->id);

        $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('show-a@atomy.test'))
            ->getJson('/api/v1/users/' . $userB->id)
            ->assertStatus(404);
    }

    public function test_users_invite_creates_persisted_pending_record(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-invite',
            'name' => 'Tenant Users Invite',
            'email' => 'tenant-users-invite@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'invite-admin@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('invite-admin@atomy.test'))
            ->postJson('/api/v1/users/invite', [
                'email' => 'invitee@atomy.test',
                'name' => 'Invitee User',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.email', 'invitee@atomy.test');
        $response->assertJsonPath('data.name', 'Invitee User');
        $response->assertJsonPath('data.status', 'pending_activation');
        $response->assertJsonPath('data.tenant_id', (string) $tenant->id);

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'invitee@atomy.test',
            'name' => 'Invitee User',
            'status' => 'pending_activation',
        ]);
    }

    public function test_users_invite_duplicate_email_returns_409(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-invite-dup',
            'name' => 'Tenant Users Invite Dup',
            'email' => 'tenant-users-invite-dup@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'invite-dup-admin@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'duplicate@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'user',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('invite-dup-admin@atomy.test'))
            ->postJson('/api/v1/users/invite', [
                'email' => 'duplicate@atomy.test',
                'name' => 'Duplicate User',
            ])
            ->assertStatus(409)
            ->assertJsonFragment(['message' => 'A user with that email already exists.']);
    }

    public function test_users_suspend_updates_status_to_suspended(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-suspend',
            'name' => 'Tenant Users Suspend',
            'email' => 'tenant-users-suspend@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'suspend-admin@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);

        $target = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'suspend-target@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'user',
        ]);

        $otherTenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-suspend-other',
            'name' => 'Tenant Users Suspend Other',
            'email' => 'tenant-users-suspend-other@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $otherTarget = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'email' => 'suspend-other-target@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'user',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('suspend-admin@atomy.test'))
            ->postJson('/api/v1/users/' . $target->id . '/suspend');

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $target->id);
        $response->assertJsonPath('data.status', 'suspended');
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'tenant_id' => $tenant->id,
            'status' => 'suspended',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('suspend-admin@atomy.test'))
            ->postJson('/api/v1/users/' . $otherTarget->id . '/suspend')
            ->assertStatus(404);

        $this->assertDatabaseHas('users', [
            'id' => $otherTarget->id,
            'tenant_id' => $otherTenant->id,
            'status' => 'active',
        ]);
    }

    public function test_users_reactivate_updates_status_to_active(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-reactivate',
            'name' => 'Tenant Users Reactivate',
            'email' => 'tenant-users-reactivate@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'reactivate-admin@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);

        $target = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'reactivate-target@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'suspended',
            'role' => 'user',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('reactivate-admin@atomy.test'))
            ->postJson('/api/v1/users/' . $target->id . '/reactivate');

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $target->id);
        $response->assertJsonPath('data.status', 'active');
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);
    }

    public function test_users_roles_returns_real_role_data(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-roles',
            'name' => 'Tenant Users Roles',
            'email' => 'tenant-users-roles@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'roles-admin@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);

        Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'manager',
            'description' => 'Manager role',
        ]);

        $otherTenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-roles-other',
            'name' => 'Tenant Users Roles Other',
            'email' => 'tenant-users-roles-other@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        Role::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'other-manager',
            'description' => 'Other Manager role',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('roles-admin@atomy.test'))
            ->getJson('/api/v1/roles');

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'manager',
            'description' => 'Manager role',
            'tenant_id' => (string) $tenant->id,
        ]);
        $response->assertJsonMissing([
            'name' => 'other-manager',
        ]);
    }

    public function test_user_delegation_rules_endpoint_returns_honest_deferred_response(): void
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'code' => 'tenant-users-deferred',
            'name' => 'Tenant Users Deferred',
            'email' => 'tenant-users-deferred@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'deferred-admin@atomy.test',
            'password_hash' => Hash::make('password'),
            'status' => 'active',
            'role' => 'admin',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->loginAndGetToken('deferred-admin@atomy.test'))
            ->getJson('/api/v1/users/' . $user->id . '/delegation-rules')
            ->assertStatus(501)
            ->assertJsonPath('error', 'Not implemented');
    }

    /**
     * @return string
     */
    private function loginAndGetToken(string $email, string $password = 'password'): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertOk();

        return (string) $response->json('access_token');
    }

    private function assertAuditLogExists(string $event, string $subjectId, string $tenantId): void
    {
        $row = DB::table('audit_logs')
            ->where('event', $event)
            ->where('subject_id', $subjectId)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($row, sprintf('Expected audit event %s for subject %s.', $event, $subjectId));
        $this->assertSame($tenantId, $row->tenant_id);

        $properties = json_decode((string) $row->properties, true);
        $this->assertIsArray($properties, 'Audit log properties should be stored as JSON.');
        $this->assertSame($tenantId, $properties['tenant_id'] ?? null);
    }
}
