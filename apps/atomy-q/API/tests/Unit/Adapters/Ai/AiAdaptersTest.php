<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\AtomyAiCapabilityCatalog;
use App\Adapters\Ai\ConfiguredAiEndpointRegistry;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Tests\TestCase;

final class AiAdaptersTest extends TestCase
{
    public function testCapabilityCatalogMemoizesDefinitionsAndFeatureIndex(): void
    {
        $catalog = new AtomyAiCapabilityCatalog();

        $definitions = $catalog->all();
        $definitionsAgain = $catalog->all();

        self::assertCount(7, $definitions);
        self::assertSame($definitions[0], $definitionsAgain[0]);
        self::assertSame(
            $definitions[0],
            $catalog->findByFeatureKey($definitions[0]->featureKey),
        );
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
