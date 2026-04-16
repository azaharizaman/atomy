<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nexus\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Identity\ValueObjects\WebAuthnAuthenticationOptions;
use Tests\TestCase;

final class AuthTest extends TestCase
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

        // Replace the app's MFA verification service with a deterministic fake for feature tests.
        // The real runtime binding can be swapped later without changing these API-level assertions.
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

    public function test_login_validation_error(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors']);
    }

    public function test_login_invalid_credentials(): void
    {
        $user = $this->createTestUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user['email'],
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Invalid credentials']);
    }

    public function test_login_success(): void
    {
        $user = $this->createTestUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user['email'],
            'password' => $user['password'],
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
        ]);
        $this->assertTokenResponse($response);
        $response->assertJsonPath('user.tenantId', $user['tenant_id']);
    }

    public function test_login_ignores_wrong_tenant_id_when_email_matches(): void
    {
        $user = $this->createTestUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => (string) Str::ulid(),
            'email' => $user['email'],
            'password' => $user['password'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.tenantId', $user['tenant_id']);
    }

    public function test_refresh_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_valid_token(): void
    {
        $user = $this->createTestUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user['email'],
            'password' => $user['password'],
        ]);

        $loginResponse->assertOk();
        $this->assertTokenResponse($loginResponse);

        $refreshToken = (string) $loginResponse->json('refresh_token');

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
        ]);
        $this->assertTokenResponse($response);
    }

    public function test_sso_endpoint_returns_redirect_url(): void
    {
        $this->app['config']->set('services.oidc', [
            'issuer_url' => 'https://example.com',
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'redirect_uri' => 'https://app.example.com/callback',
        ]);

        $GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT'] = [
            'issuer' => 'https://example.com',
            'authorization_endpoint' => 'https://example.com/oauth2/authorize',
            'token_endpoint' => 'https://example.com/oauth2/token',
            'userinfo_endpoint' => 'https://example.com/oauth2/userinfo',
            'jwks_uri' => 'https://example.com/.well-known/jwks.json',
        ];

        $tenantId = (string) Str::ulid();

        $resp = $this->postJson('/api/v1/auth/sso', [
            'action' => 'init',
            'tenant_id' => $tenantId,
        ]);

        $resp->assertOk();
        $resp->assertJsonStructure(['data' => ['authorization_url', 'state']]);
        $this->assertIsString((string) $resp->json('data.authorization_url'));
        $this->assertNotSame('', (string) $resp->json('data.authorization_url'));
        $this->assertIsString((string) $resp->json('data.state'));
        $this->assertNotSame('', (string) $resp->json('data.state'));

        unset($GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT']);
    }

    public function test_sso_callback_returns_tokens_for_mock_authorization_code(): void
    {
        $this->app['config']->set('services.oidc', [
            'issuer_url' => 'https://example.com',
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'redirect_uri' => 'https://app.example.com/callback',
        ]);

        $GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT'] = [
            'issuer' => 'https://example.com',
            'authorization_endpoint' => 'https://example.com/oauth2/authorize',
            'token_endpoint' => 'https://example.com/oauth2/token',
            'userinfo_endpoint' => 'https://example.com/oauth2/userinfo',
            'jwks_uri' => 'https://example.com/.well-known/jwks.json',
        ];

        $tenantId = (string) Str::ulid();

        $init = $this->postJson('/api/v1/auth/sso', [
            'action' => 'init',
            'tenant_id' => $tenantId,
        ]);
        $init->assertOk();

        $state = (string) $init->json('data.state');

        // Provide deterministic claims via the SSO provider's mock hook.
        $this->app['config']->set('services.oidc.mock_id_token_claims', [
            'iss' => 'https://example.com',
            'sub' => 'user-123',
            'aud' => 'test-client',
            'exp' => 9999999999,
            'iat' => 1700000000,
            'email' => 'sso-user@example.com',
            'name' => 'SSO User',
            'groups' => ['admin'],
        ]);

        $cb = $this->postJson('/api/v1/auth/sso', [
            'action' => 'callback',
            'tenant_id' => $tenantId,
            'code' => 'mock_authorization_code',
            'state' => $state,
        ]);

        $cb->assertOk();
        $this->assertTokenResponse($cb);
        $cb->assertJsonStructure(['user' => ['id', 'email', 'name', 'role', 'tenantId']]);
        $cb->assertJsonFragment(['email' => 'sso-user@example.com', 'tenantId' => $tenantId]);

        unset($GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT']);
    }

    public function test_mfa_verify_endpoint_returns_message_and_verified(): void
    {
        $user = $this->createTestUser(['mfa_enabled' => true]);

        $challengeResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user['email'],
            'password' => $user['password'],
        ]);

        $challengeResponse->assertStatus(401);
        $challengeResponse->assertJsonFragment(['message' => 'Multi-factor authentication required']);
        $challengeId = (string) $challengeResponse->json('challenge_id');
        $this->assertNotSame('', $challengeId);

        $verify = $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $challengeId,
            'otp' => '123456',
        ]);

        $verify->assertOk();
        $this->assertTokenResponse($verify);
        $verify->assertJsonPath('user.tenantId', $user['tenant_id']);
    }

    public function test_mfa_verify_rejects_mismatched_optional_tenant_id(): void
    {
        $user = $this->createTestUser(['mfa_enabled' => true]);

        $challengeResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user['email'],
            'password' => $user['password'],
        ]);

        $challengeResponse->assertStatus(401);
        $challengeId = (string) $challengeResponse->json('challenge_id');

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge_id' => $challengeId,
            'tenant_id' => (string) \Illuminate\Support\Str::ulid(),
            'otp' => '123456',
        ])->assertStatus(401)
            ->assertJsonPath('message', 'Invalid or expired MFA challenge');
    }

    public function test_forgot_password_requires_email(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function test_forgot_password_returns_message(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'user@example.com',
        ])
            ->assertOk()
            ->assertJsonStructure(['message']);
    }

    public function test_logout_returns_message(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(501)
            ->assertJsonStructure(['message']);
    }

    public function test_device_trust_returns_message(): void
    {
        $this->postJson('/api/v1/auth/device-trust')
            ->assertStatus(501)
            ->assertJsonStructure(['message']);
    }

    private function assertTokenResponse(TestResponse $response): void
    {
        $tokenType = $response->json('token_type');
        $accessToken = $response->json('access_token');
        $refreshToken = $response->json('refresh_token');
        $expiresIn = $response->json('expires_in');

        $this->assertSame('Bearer', $tokenType);
        $this->assertIsString($accessToken);
        $this->assertNotSame('', $accessToken);
        $this->assertIsString($refreshToken);
        $this->assertNotSame('', $refreshToken);
        $this->assertIsInt($expiresIn);
        $this->assertGreaterThan(0, $expiresIn);
    }

    /**
     * @return array{tenant_id: string, email: string, password: string}
     */
    private function createTestUser(array $overrides = []): array
    {
        $tenantId = (string) Str::ulid();
        $email = 'user-' . Str::lower((string) Str::ulid()) . '@example.com';
        $password = 'password';

        Tenant::query()->create([
            'id' => $tenantId,
            'code' => 'tenant-' . Str::lower((string) Str::ulid()),
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        User::query()->create([
            'tenant_id' => $tenantId,
            'email' => $email,
            'name' => 'Test User',
            'password_hash' => Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
            ...$overrides,
        ]);

        return [
            'tenant_id' => $tenantId,
            'email' => $email,
            'password' => $password,
        ];
    }
}
