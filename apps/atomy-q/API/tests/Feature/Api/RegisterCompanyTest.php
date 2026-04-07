<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class RegisterCompanyTest extends TestCase
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

    public function testRegisterCompanyCreatesTenantAndOwnerUser(): void
    {
        $response = $this->postJson('/api/v1/auth/register-company', $this->payload());

        $response->assertOk();
        $this->assertTokenResponse($response);
        $response->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
            'user' => [
                'id',
                'email',
                'name',
                'role',
                'tenantId',
            ],
            'bootstrap',
        ]);
        $response->assertJsonPath('user.email', 'owner@acme.test');
        $response->assertJsonPath('user.name', 'Ada Lovelace');
        $response->assertJsonPath('user.role', 'admin');

        $tenant = Tenant::query()->where('code', 'ACME')->first();
        $this->assertNotNull($tenant);
        $this->assertSame('Acme Corp', $tenant?->name);
        $this->assertSame('owner@acme.test', $tenant?->email);
        $this->assertSame('active', $tenant?->status);
        $this->assertSame('acme.local', $tenant?->domain);
        $this->assertSame('Asia/Kuala_Lumpur', $tenant?->timezone);
        $this->assertSame('en_MY', $tenant?->locale);
        $this->assertSame('MYR', $tenant?->currency);
        $this->assertSame(100, $tenant?->onboarding_progress);

        $user = User::query()
            ->where('tenant_id', (string) $tenant?->id)
            ->where('email', 'owner@acme.test')
            ->first();

        $this->assertNotNull($user);
        $this->assertSame('admin', $user?->role);
        $this->assertSame('active', $user?->status);
        $this->assertSame((string) $tenant?->id, (string) $user?->tenant_id);
    }

    public function testRegisterCompanyRejectsDuplicateCompanyCode(): void
    {
        $this->postJson('/api/v1/auth/register-company', $this->payload())->assertOk();

        $response = $this->postJson('/api/v1/auth/register-company', $this->payload([
            'company_name' => 'Another Name',
            'owner_email' => 'owner2@acme.test',
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.tenant_code.0', "Tenant code 'ACME' is already in use");
        $this->assertSame(1, Tenant::query()->count());
    }

    public function testRegisterCompanyRejectsDuplicateCompanyName(): void
    {
        $this->postJson('/api/v1/auth/register-company', $this->payload())->assertOk();

        $response = $this->postJson('/api/v1/auth/register-company', $this->payload([
            'tenant_code' => 'beta',
            'owner_email' => 'owner2@beta.test',
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.company_name.0', "Tenant name 'Acme Corp' is already in use");
        $this->assertSame(1, Tenant::query()->count());
    }

    public function testRegisterCompanyRequiresValidPayload(): void
    {
        $response = $this->postJson('/api/v1/auth/register-company', []);

        $response->assertStatus(422);
    }

    public function testRegisterCompanyRollsBackTenantWhenAdminCreationFails(): void
    {
        $this->app->instance(AdminCreatorAdapterInterface::class, new class implements AdminCreatorAdapterInterface {
            public function create(
                string $tenantId,
                string $email,
                string $password,
                string $firstName,
                string $lastName,
                bool $isAdmin = true,
                ?string $locale = null,
                ?string $timezone = null,
                ?array $metadata = null
            ): string {
                throw new \RuntimeException('Simulated admin creation failure');
            }
        });

        $response = $this->postJson('/api/v1/auth/register-company', $this->payload());

        $response->assertStatus(422);
        $response->assertJsonPath('errors.onboarding.0', 'Company onboarding failed');
        $this->assertSame(0, Tenant::query()->count());
        $this->assertSame(0, User::query()->count());
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'tenant_code' => 'acme',
            'company_name' => 'Acme Corp',
            'owner_name' => 'Ada Lovelace',
            'owner_email' => 'owner@acme.test',
            'owner_password' => 'secret123',
            'timezone' => 'Asia/Kuala_Lumpur',
            'locale' => 'en_MY',
            'currency' => 'MYR',
        ], $overrides);
    }

    private function assertTokenResponse(TestResponse $response): void
    {
        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertIsString((string) $response->json('access_token'));
        $this->assertNotSame('', (string) $response->json('access_token'));
        $this->assertIsString((string) $response->json('refresh_token'));
        $this->assertNotSame('', (string) $response->json('refresh_token'));
        $this->assertGreaterThan(0, (int) $response->json('expires_in'));
    }
}
