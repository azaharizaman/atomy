<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfqProjectIdTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): \Illuminate\Foundation\Application
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

    public function test_rfqs_support_optional_project_id_column_and_attribute(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2026-0001',
            'title' => 'Test RFQ',
            'owner_id' => $user->id,
            'status' => 'draft',
            'project_id' => null,
        ]);

        $this->assertArrayHasKey('project_id', $rfq->getAttributes());
        $this->assertNull($rfq->project_id);
    }

    public function test_rfqs_can_store_project_id_when_provided(): void
    {
        $user = $this->createUser();
        $projectId = (string) Str::ulid();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2026-0002',
            'title' => 'Test RFQ with project',
            'owner_id' => $user->id,
            'status' => 'draft',
            'project_id' => $projectId,
        ]);

        $this->assertSame($projectId, $rfq->project_id);
    }

    private function createUser(): User
    {
        $tenantId = (string) Str::ulid();

        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'user-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Example Test User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }
}
