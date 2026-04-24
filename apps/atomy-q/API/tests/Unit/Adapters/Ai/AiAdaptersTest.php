<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\AtomyAiCapabilityCatalog;
use App\Adapters\Ai\ConfiguredAiEndpointRegistry;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Tests\TestCase;

final class AiAdaptersTest extends TestCase
{
    public function testCapabilityCatalogMemoizesDefinitionsAndDefinesExpectedCapabilities(): void
    {
        $catalog = new AtomyAiCapabilityCatalog();

        $definitions = $catalog->all();
        $definitionsAgain = $catalog->all();

        self::assertCount(14, $definitions);
        self::assertSame($definitions[0], $definitionsAgain[0]);
        self::assertSame(
            $definitions[0],
            $catalog->findByFeatureKey($definitions[0]->featureKey),
        );
        // AI ranking is plan-3 provider-backed functionality and must stay discoverable.
        self::assertNotNull($catalog->findByFeatureKey('vendor_ai_ranking'));
        // Manual vendor selection remains available even when AI ranking is unavailable.
        self::assertNotNull($catalog->findByFeatureKey('vendor_manual_selection'));
        // Recommendation endpoints remain stubbed and must not be advertised as live AI capability.
        self::assertNull($catalog->findByFeatureKey('recommendation_ai_endpoint'));
    }

    public function testConfiguredEndpointRegistryPreservesUnknownProviderKey(): void
    {
        $registry = new ConfiguredAiEndpointRegistry([
            'provider' => [
                'key' => '  Anthropic  ',
                'name' => '   ',
            ],
            'endpoints' => [
                'document' => [
                    'uri' => 'https://anthropic.example.test/document',
                ],
            ],
        ]);

        $endpointConfig = $registry->endpointConfig('document');

        self::assertSame('anthropic', $registry->providerName());
        self::assertInstanceOf(AiEndpointConfig::class, $endpointConfig);
        self::assertSame('anthropic', $endpointConfig->providerName);
    }
}
