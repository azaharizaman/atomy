<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Contracts\JwtServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Api\ApiTestCase;

final class IdempotencyApiTest extends ApiTestCase
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

    public function test_post_rfqs_without_idempotency_key_returns_400(): void
    {
        $jwt = app(JwtServiceInterface::class);
        $token = $jwt->issueAccessToken('user-1', 'tenant-1');

        $response = $this->postJson('/api/v1/rfqs', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('code', 'idempotency_key_required');
    }

    public function test_phase1_idempotent_routes_are_named_and_use_middleware(): void
    {
        $names = [
            'v1.rfqs.store',
            'v1.rfqs.bulk-action',
            'v1.rfqs.duplicate',
            'v1.rfqs.invitations.store',
            'v1.rfqs.invitations.remind',
            'v1.rfq-templates.store',
            'v1.rfq-templates.duplicate',
            'v1.rfq-templates.apply',
            'v1.projects.store',
            'v1.tasks.store',
            'v1.operational-approvals.instances.store',
            'v1.operational-approvals.instances.decisions.store',
        ];

        foreach ($names as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, 'Missing route name: ' . $name);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('idempotency', $middleware, 'Route ' . $name . ' should use idempotency middleware');
        }
    }
}
