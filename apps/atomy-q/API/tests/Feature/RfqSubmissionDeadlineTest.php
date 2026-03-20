<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class RfqSubmissionDeadlineTest extends ApiTestCase
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

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => 'buyer-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'RFQ Deadline User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
        ]);

        return $user;
    }

    public function test_store_rfqs_requires_submission_deadline(): void
    {
        $user = $this->createUser();
        $tenantId = (string) $user->tenant_id;

        $response = $this->postJson(
            '/api/v1/rfqs',
            ['title' => 'No deadline'],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $messages = $response->json('details.submission_deadline');
        $this->assertIsArray($messages);
        $this->assertStringContainsStringIgnoringCase('required', (string) ($messages[0] ?? ''));
    }

    public function test_store_rfqs_accepts_submission_deadline(): void
    {
        $user = $this->createUser();
        $tenantId = (string) $user->tenant_id;
        $deadline = now()->addDays(10)->toAtomString();

        $response = $this->postJson(
            '/api/v1/rfqs',
            [
                'title' => 'With deadline',
                'submission_deadline' => $deadline,
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertCreated();
        $this->assertNotEmpty($response->json('data.id'));
    }

    public function test_store_rfqs_rejects_closing_before_submission(): void
    {
        $user = $this->createUser();
        $tenantId = (string) $user->tenant_id;
        $sub = now()->addDays(10)->toAtomString();
        $close = now()->addDays(5)->toAtomString();

        $response = $this->postJson(
            '/api/v1/rfqs',
            [
                'title' => 'Inverted dates',
                'submission_deadline' => $sub,
                'closing_date' => $close,
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $messages = $response->json('details.closing_date');
        $this->assertIsArray($messages);
        $this->assertStringContainsStringIgnoringCase('closing date', (string) ($messages[0] ?? ''));
    }

    public function test_update_rfqs_rejects_closing_before_submission(): void
    {
        $user = $this->createUser();
        $tenantId = (string) $user->tenant_id;

        $create = $this->postJson(
            '/api/v1/rfqs',
            [
                'title' => 'RFQ',
                'submission_deadline' => now()->addDays(20)->toAtomString(),
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );
        $create->assertCreated();
        $id = (string) $create->json('data.id');

        $response = $this->putJson(
            '/api/v1/rfqs/' . $id,
            [
                'title' => 'RFQ',
                'submission_deadline' => now()->addDays(20)->toAtomString(),
                'closing_date' => now()->addDays(5)->toAtomString(),
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $messages = $response->json('details.closing_date');
        $this->assertIsArray($messages);
        $this->assertStringContainsStringIgnoringCase('closing date', (string) ($messages[0] ?? ''));
    }
}
