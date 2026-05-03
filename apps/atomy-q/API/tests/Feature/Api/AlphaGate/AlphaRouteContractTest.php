<?php

declare(strict_types=1);

namespace Tests\Feature\Api\AlphaGate;

use App\Contracts\JwtServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Api\ApiTestCase;

#[Group('alpha-gate')]
final class AlphaRouteContractTest extends ApiTestCase
{
    #[DataProvider('protectedAlphaRoutes')]
    public function test_protected_alpha_routes_reject_missing_token(string $method, string $uri): void
    {
        $response = $this->json($method, $uri);

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Authentication required']);
    }

    #[DataProvider('protectedAlphaRoutes')]
    public function test_protected_alpha_routes_reject_invalid_token(string $method, string $uri): void
    {
        $response = $this->json($method, $uri, [], [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Invalid or expired token']);
    }

    #[DataProvider('protectedAlphaRoutes')]
    public function test_protected_alpha_routes_reject_missing_tenant_context(string $method, string $uri): void
    {
        $jwt = app(JwtServiceInterface::class);
        $token = $jwt->issueAccessToken('alpha-user', '');

        $response = $this->json($method, $uri, [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'Tenant context required']);
    }

    public function test_alpha_validation_errors_use_canonical_envelope(): void
    {
        $response = $this->postJson('/api/v1/rfqs', [], $this->authHeaders());

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.title.0', 'The title field is required.');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function protectedAlphaRoutes(): array
    {
        return [
            'dashboard kpis' => ['GET', '/api/v1/dashboard/kpis'],
            'rfq list' => ['GET', '/api/v1/rfqs'],
            'rfq counts' => ['GET', '/api/v1/rfqs/counts'],
            'vendor list' => ['GET', '/api/v1/vendors'],
            'quote list' => ['GET', '/api/v1/quote-submissions'],
            'comparison list' => ['GET', '/api/v1/comparison-runs'],
            'approval list' => ['GET', '/api/v1/approvals'],
            'award list' => ['GET', '/api/v1/awards'],
            'rfq evidence vault summary' => ['GET', '/api/v1/rfqs/rfq-alpha/evidence-vault'],
            'rfq evidence vault supporting evidence upload' => ['POST', '/api/v1/rfqs/rfq-alpha/evidence-vault/supporting-evidence'],
            'rfq evidence vault finalize' => ['POST', '/api/v1/rfqs/rfq-alpha/evidence-vault/award-pack/finalize'],
            'rfq evidence vault export' => ['GET', '/api/v1/rfqs/rfq-alpha/evidence-vault/award-pack/export'],
            'decision trail list' => ['GET', '/api/v1/decision-trail'],
            'project list' => ['GET', '/api/v1/projects'],
            'task list' => ['GET', '/api/v1/tasks'],
        ];
    }
}
