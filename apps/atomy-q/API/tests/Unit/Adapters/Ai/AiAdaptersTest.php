<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\Ai;

use App\Adapters\Ai\AtomyAiCapabilityCatalog;
use App\Adapters\Ai\ConfiguredAiEndpointRegistry;
use App\Adapters\Ai\DTOs\AwardGuidanceRequest;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\Exceptions\ComparisonAwardAiException;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Tests\TestCase;

final class AiAdaptersTest extends TestCase
{
    public function testCapabilityCatalogMemoizesDefinitionsAndDefinesExpectedCapabilities(): void
    {
        $catalog = new AtomyAiCapabilityCatalog();

        $definitions = $catalog->all();
        $definitionsAgain = $catalog->all();

        self::assertCount(20, $definitions);
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

    public function testAwardGuidanceRequestCanonicalizesIdentifiersBeforePersistingPayload(): void
    {
        $request = new AwardGuidanceRequest(
            tenantId: ' tenant-1 ',
            awardId: ' award-1 ',
            rfqId: ' rfq-1 ',
            comparisonRunId: ' run-1 ',
            award: [],
            comparisonContext: [],
        );

        self::assertSame('tenant-1', $request->tenantId);
        self::assertSame('award-1', $request->awardId);
        self::assertSame('rfq-1', $request->rfqId);
        self::assertSame('run-1', $request->comparisonRunId);
        self::assertSame('tenant-1', $request->toPayload()['tenant_id']);
    }

    public function testComparisonOverlayRequestCanonicalizesAndValidatesMode(): void
    {
        $request = new ComparisonOverlayRequest(
            tenantId: ' tenant-1 ',
            rfqId: ' rfq-1 ',
            mode: ' PREVIEW ',
            comparison: [],
            snapshot: null,
        );

        self::assertSame('tenant-1', $request->tenantId);
        self::assertSame('rfq-1', $request->rfqId);
        self::assertSame('preview', $request->mode);
        self::assertSame('preview', $request->toPayload()['mode']);
    }

    public function testComparisonOverlayRequestRejectsUnknownMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comparison overlay request mode must be one of');

        new ComparisonOverlayRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            mode: 'draft',
            comparison: [],
            snapshot: null,
        );
    }

    public function testComparisonAwardAiExceptionFallsBackWhenContextCannotBeJsonEncoded(): void
    {
        $exception = ComparisonAwardAiException::fromThrowable(
            new \RuntimeException('transport failed'),
            'approval_summary',
            ['payload' => "\xB1\x31"],
        );

        self::assertStringContainsString('approval_summary', $exception->getMessage());
        self::assertStringContainsString('Context encode failed', $exception->getMessage());
        self::assertStringContainsString('payload', $exception->getMessage());
    }
}
