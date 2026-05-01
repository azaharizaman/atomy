<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\Http;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Tests\Feature\Api\ApiTestCase;

final class AiStatusVisibilityTest extends ApiTestCase
{
    public function testItDoesNotLeakEndpointUrisOrAuthTokens(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_PROVIDER);
        config()->set('atomy.ai.provider.name', 'generic-public');
        config()->set('atomy.ai.provider.default_auth_token', 'provider-super-secret-token');
        config()->set('atomy.ai.endpoints.document.uri', 'https://provider.example.test/document/private');
        config()->set('atomy.ai.endpoints.document.auth_token', 'provider-document-secret-token');

        Http::fake([
            'https://provider.example.test/document/private/health' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();

        $payload = json_encode($response->json(), JSON_THROW_ON_ERROR);

        self::assertIsString($payload);
        self::assertStringNotContainsString('provider-super-secret-token', $payload);
        self::assertStringNotContainsString('provider-document-secret-token', $payload);
        self::assertStringNotContainsString('https://provider.example.test/document/private', $payload);
    }
}
